<?php

namespace Leo108\Cas\Tests\Repositories;

use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\Service;
use Leo108\Cas\Models\Ticket;
use Leo108\Cas\Repositories\ServiceRepository;
use Leo108\Cas\Repositories\TicketRepository;
use Leo108\Cas\Services\TicketGenerator;
use Leo108\Cas\Tests\Support\User;
use Leo108\Cas\Tests\TestCase;

class TicketRepositoryTest extends TestCase
{
    public function testApplyTicketWithInvalidService()
    {
        $this->expectException(CasException::class);
        $this->expectExceptionMessage(CasException::INVALID_SERVICE);
        app()->make(TicketRepository::class)->applyTicket(new User(), 'what ever');
    }

    public function testApplyTicketButFindAvailableTicketFailed()
    {
        $this->mock(TicketGenerator::class)
            ->shouldReceive('generate')
            ->andReturnFalse()
            ->once();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn(new Service())
            ->once();

        $this->expectException(CasException::class);
        $this->expectExceptionMessage('apply ticket failed');

        app()->make(TicketRepository::class)->applyTicket(new User(), 'https://leo108.com');
    }

    public function testApplyTicketSuccess()
    {
        $ticketStr = 'ST-abc';
        $serviceUrl = 'https://leo108.com';

        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();

        $this->mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->with($serviceUrl)
            ->andReturn($service)
            ->getMock();

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generate')
            ->andReturn($ticketStr)
            ->once();

        $record = app(TicketRepository::class)->applyTicket($user, $serviceUrl);
        $this->assertEquals($ticketStr, $record->ticket);
        $this->assertEquals($serviceUrl, $record->service_url);
    }

    public function testGetByTicket()
    {
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();
        $ticket = new Ticket();
        $ticket->ticket = 'ST-abc';
        $ticket->service_url = 'https://leo108.com';
        $ticket->proxies = [];
        $ticket->expire_at = now()->subSeconds(100);
        $ticket->created_at = now()->subSeconds(600);
        $ticket->user()->associate($user);
        $ticket->service()->associate($service);
        $ticket->save();

        $this->assertNull(app()->make(TicketRepository::class)->getByTicket('ST-not-exist'));
        $this->assertNull(app()->make(TicketRepository::class)->getByTicket('ST-abc'));

        $ticket->expire_at = now()->addSeconds(100);
        $ticket->save();
        $this->assertEquals($ticket->id, app()->make(TicketRepository::class)->getByTicket('ST-abc')->id);
    }

    public function testInvalidTicket()
    {
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();
        $ticket = new Ticket();
        $ticket->ticket = 'ST-abc';
        $ticket->service_url = 'https://leo108.com';
        $ticket->proxies = [];
        $ticket->expire_at = now()->addSeconds(100);
        $ticket->created_at = now();
        $ticket->user()->associate($user);
        $ticket->service()->associate($service);
        $ticket->save();

        $this->assertDatabaseHas(Ticket::class, ['id' => $ticket->id]);
        app()->make(TicketRepository::class)->invalidTicket($ticket);
        $this->assertDatabaseMissing(Ticket::class, ['id' => $ticket->id]);
    }
}
