<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 09:53
 */
namespace Repositories;

use Exception;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\Service;
use Leo108\CAS\Models\Ticket;
use Leo108\CAS\Repositories\ServiceRepository;
use Leo108\CAS\Repositories\TicketRepository;
use Mockery;
use ReflectionClass;
use TestCase;
use User;

class TicketRepositoryTest extends TestCase
{
    public function testApplyTicket()
    {
        //test if service url is not valid
        $user = Mockery::mock(User::class)
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
        $ticketRepository  = Mockery::mock(TicketRepository::class, [new Ticket(), $serviceRepository])
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
        $ticket            = Mockery::mock(Ticket::class)
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
        $ticketRepository  = Mockery::mock(TicketRepository::class, [$ticket, $serviceRepository])
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
        $this->assertFalse(app()->make(TicketRepository::class)->getByTicket('what ever'));

        $mockTicket = Mockery::mock(Ticket::class)
            ->shouldReceive('isExpired')
            ->andReturnValues([false, true])
            ->getMock();

        $ticket = Mockery::mock(Ticket::class);
        $ticket->shouldReceive('where->first')->andReturn($mockTicket);
        app()->instance(Ticket::class, $ticket);
        $this->assertNotFalse(app()->make(TicketRepository::class)->getByTicket('what ever', false));
        $this->assertNotFalse(app()->make(TicketRepository::class)->getByTicket('what ever'));
        $this->assertFalse(app()->make(TicketRepository::class)->getByTicket('what ever'));
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
        //normal
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->makePartial()
            ->shouldReceive('getByTicket')
            ->andReturn(false)
            ->getMock();

        $length = 32;
        $ticket = self::getMethod($ticketRepository, 'getAvailableTicket')->invoke($ticketRepository, $length);
        $this->assertNotFalse($ticket);
        $this->assertEquals($length, strlen($ticket));

        //always get occupied ticket
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->makePartial()
            ->shouldReceive('getByTicket')
            ->andReturn(true)
            ->getMock();
        $this->assertFalse(self::getMethod($ticketRepository, 'getAvailableTicket')->invoke($ticketRepository, 32));
    }

    protected static function getMethod($obj, $name)
    {
        $class  = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
