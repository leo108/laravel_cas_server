<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/26
 * Time: 21:39
 */

namespace Leo108\CAS\Repositories;


use Exception;
use Leo108\CAS\Contracts\Models\UserModel;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\PGTicket;
use Leo108\CAS\Models\Service;
use Leo108\CAS\Services\TicketGenerator;
use Mockery;
use TestCase;

class PGTicketRepositoryTest extends TestCase
{
    public function testApplyTicketWithInvalidSerivce()
    {
        $user              = Mockery::mock(UserModel::class);
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn(false)
            ->once()
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        try {
            app()->make(PGTicketRepository::class)->applyTicket($user, 'what ever');
        } catch (Exception $e) {
            $this->assertInstanceOf(CasException::class, $e);
            $this->assertEquals(CasException::UNAUTHORIZED_SERVICE_PROXY, $e->getCasErrorCode());
        }
    }

    public function testApplyTicketWithNonProxyService()
    {
        $user              = Mockery::mock(UserModel::class);
        $service           = Mockery::mock(Service::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['allow_proxy'])
            ->andReturn(false)
            ->once()
            ->getMock();
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);

        try {
            app()->make(PGTicketRepository::class)->applyTicket($user, 'what ever');
        } catch (Exception $e) {
            $this->assertInstanceOf(CasException::class, $e);
            $this->assertEquals(CasException::UNAUTHORIZED_SERVICE_PROXY, $e->getCasErrorCode());
        }
    }

    public function testApplyTicketWithValidServiceButApplyTicketFailed()
    {
        $user              = Mockery::mock(UserModel::class);
        $service           = Mockery::mock(Service::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['allow_proxy'])
            ->andReturn(true)
            ->once()
            ->getMock();
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service)
            ->once()
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $pgTicketRepository = $this->initPGTicketRepository()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getAvailableTicket')
            ->andReturn(false)
            ->once()
            ->getMock();

        try {
            $pgTicketRepository->applyTicket($user, 'what ever');
        } catch (Exception $e) {
            $this->assertInstanceOf(CasException::class, $e);
            $this->assertEquals(CasException::INTERNAL_ERROR, $e->getCasErrorCode());
            $this->assertEquals('apply proxy-granting ticket failed', $e->getMessage());
        }
    }

    public function testApplyTicketWithValidServiceAndApplyTicketOK()
    {
        $user              = Mockery::mock(UserModel::class)
            ->shouldReceive('getEloquentModel')
            ->andReturn(Mockery::self())
            ->once()
            ->getMock();
        $ticketStr         = 'ST-abc';
        $pgtUrl            = 'what ever';
        $proxies           = ['http://proxy2.com', 'http://proxy1.com'];
        $service           = Mockery::mock(Service::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['allow_proxy'])
            ->andReturn(true)
            ->once()
            ->getMock();
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $ticket = Mockery::mock(PGTicket::class)
            ->shouldReceive('newInstance')
            ->andReturnUsing(
                function ($param) {
                    $obj = Mockery::mock();
                    $obj->shouldReceive('user->associate');
                    $obj->shouldReceive('service->associate');
                    $obj->shouldReceive('save');
                    $obj->ticket  = $param['ticket'];
                    $obj->pgt_url = $param['pgt_url'];
                    $obj->proxies = $param['proxies'];

                    return $obj;
                }
            )
            ->getMock();
        app()->instance(PGTicket::class, $ticket);
        $ticketRepository = $this->initPGTicketRepository()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getAvailableTicket')
            ->andReturn($ticketStr)
            ->getMock();

        $record = $ticketRepository->applyTicket($user, $pgtUrl, $proxies);
        $this->assertEquals($ticketStr, $record->ticket);
        $this->assertEquals($pgtUrl, $record->pgt_url);
        $this->assertEquals($proxies, $record->proxies);
    }

    public function testGetByTicket()
    {
        $ticket = Mockery::mock(PGTicket::class);
        $ticket->shouldReceive('where->first')->andReturn(null);
        app()->instance(PGTicket::class, $ticket);
        $this->assertNull(app()->make(PGTicketRepository::class)->getByTicket('what ever'));

        $mockTicket = Mockery::mock(PGTicket::class)
            ->shouldReceive('isExpired')
            ->andReturnValues([false, true])
            ->getMock();

        $ticket = Mockery::mock(PGTicket::class);
        $ticket->shouldReceive('where->first')->andReturn($mockTicket);
        app()->instance(PGTicket::class, $ticket);
        $this->assertNotNull(app()->make(PGTicketRepository::class)->getByTicket('what ever', false));
        $this->assertNotNull(app()->make(PGTicketRepository::class)->getByTicket('what ever'));
        $this->assertNull(app()->make(PGTicketRepository::class)->getByTicket('what ever'));
    }

    public function testGetAvailableTicket()
    {
        $length          = 32;
        $prefix          = 'PGT-';
        $ticket          = 'ticket string';
        $ticketGenerator = Mockery::mock(TicketGenerator::class)
            ->shouldReceive('generate')
            ->andReturnUsing(
                function ($totalLength, $paramPrefix, callable $checkFunc, $maxRetry) use ($length, $prefix, $ticket) {
                    $this->assertEquals($length, $totalLength);
                    $this->assertEquals($prefix, $paramPrefix);
                    $this->assertTrue(call_user_func_array($checkFunc, [$ticket]));
                    $this->assertEquals(10, $maxRetry);

                    return 'generate called';
                }
            )
            ->once()
            ->getMock();
        app()->instance(TicketGenerator::class, $ticketGenerator);
        $ticketRepository = $this->initPGTicketRepository()
            ->makePartial()
            ->shouldReceive('getByTicket')
            ->with($ticket, false)
            ->andReturn(null)
            ->once()
            ->getMock();

        $this->assertEquals(
            'generate called',
            self::getNonPublicMethod($ticketRepository, 'getAvailableTicket')->invoke(
                $ticketRepository,
                $length,
                $prefix
            )
        );
    }

    /**
     * @return Mockery\MockInterface
     */
    protected function initPGTicketRepository()
    {
        return Mockery::mock(
            PGTicketRepository::class,
            [app(PGTicket::class), app(ServiceRepository::class), app(TicketGenerator::class)]
        );
    }
}
