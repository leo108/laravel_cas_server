<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 09:53
 */
namespace Leo108\CAS\Repositories;

use Exception;
use Leo108\CAS\Contracts\Models\UserModel;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\Service;
use Leo108\CAS\Models\Ticket;
use Leo108\CAS\Services\TicketGenerator;
use Mockery;
use TestCase;

class TicketRepositoryTest extends TestCase
{
    public function testApplyTicket()
    {
        //test if service url is not valid
        $user = Mockery::mock(UserModel::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['id'])
            ->andReturn(1)
            ->shouldReceive('getEloquentModel')
            ->andReturn(Mockery::self())
            ->getMock();

        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn(false)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        try {
            app()->make(TicketRepository::class)->applyTicket($user, 'what ever');
        } catch (Exception $e) {
            $this->assertInstanceOf(CasException::class, $e);
            $this->assertEquals($e->getCasErrorCode(), CasException::INVALID_SERVICE);
        }

        //test if get available ticket failed
        $service           = Mockery::mock(Service::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['id'])
            ->andReturn(1)
            ->getMock();
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $ticketRepository = $this->initTicketRepository()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getAvailableTicket')
            ->andReturn(false)
            ->getMock();

        try {
            $ticketRepository->applyTicket($user, 'what ever');
        } catch (Exception $e) {
            $this->assertInstanceOf(CasException::class, $e);
            $this->assertEquals($e->getCasErrorCode(), CasException::INTERNAL_ERROR);
            $this->assertEquals($e->getMessage(), 'apply ticket failed');
        }

        //normal
        $ticketStr         = 'ST-abc';
        $serviceUrl        = 'what ever';
        $service           = Mockery::mock(Service::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['id'])
            ->andReturn(1)
            ->getMock();
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $ticket = Mockery::mock(Ticket::class)
            ->shouldReceive('newInstance')
            ->andReturnUsing(
                function ($param) {
                    $obj = Mockery::mock();
                    $obj->shouldReceive('user->associate');
                    $obj->shouldReceive('service->associate');
                    $obj->shouldReceive('save');
                    $obj->ticket      = $param['ticket'];
                    $obj->service_url = $param['service_url'];

                    return $obj;
                }
            )
            ->getMock();
        app()->instance(Ticket::class, $ticket);
        $ticketRepository = $this->initTicketRepository()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('getAvailableTicket')
            ->andReturn($ticketStr)
            ->getMock();

        $record = $ticketRepository->applyTicket($user, $serviceUrl);
        $this->assertEquals($ticketStr, $record->ticket);
        $this->assertEquals($serviceUrl, $record->service_url);
    }

    public function testGetByTicket()
    {
        $ticket = Mockery::mock(Ticket::class);
        $ticket->shouldReceive('where->first')->andReturn(null);
        app()->instance(Ticket::class, $ticket);
        $this->assertNull(app()->make(TicketRepository::class)->getByTicket('what ever'));

        $mockTicket = Mockery::mock(Ticket::class)
            ->shouldReceive('isExpired')
            ->andReturnValues([false, true])
            ->getMock();

        $ticket = Mockery::mock(Ticket::class);
        $ticket->shouldReceive('where->first')->andReturn($mockTicket);
        app()->instance(Ticket::class, $ticket);
        $this->assertNotNull(app()->make(TicketRepository::class)->getByTicket('what ever', false));
        $this->assertNotNull(app()->make(TicketRepository::class)->getByTicket('what ever'));
        $this->assertNull(app()->make(TicketRepository::class)->getByTicket('what ever'));
    }

    public function testInvalidTicket()
    {
        $mockTicket = Mockery::mock(Ticket::class)
            ->shouldReceive('delete')
            ->andReturn(true)
            ->getMock();
        $this->assertTrue(app()->make(TicketRepository::class)->invalidTicket($mockTicket));
    }

    public function testGetAvailableTicket()
    {
        $length          = 32;
        $prefix          = 'ST-';
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
        $ticketRepository = $this->initTicketRepository()
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
    protected function initTicketRepository()
    {
        return Mockery::mock(
            TicketRepository::class,
            [app(Ticket::class), app(ServiceRepository::class), app(TicketGenerator::class)]
        );
    }
}
