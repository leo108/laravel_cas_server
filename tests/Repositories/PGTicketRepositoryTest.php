<?php

namespace Leo108\Cas\Tests\Repositories;

use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\PGTicket;
use Leo108\Cas\Models\Service;
use Leo108\Cas\Repositories\PGTicketRepository;
use Leo108\Cas\Repositories\ServiceRepository;
use Leo108\Cas\Services\TicketGenerator;
use Leo108\Cas\Tests\Support\User;
use Leo108\Cas\Tests\TestCase;

class PGTicketRepositoryTest extends TestCase
{
    public function testApplyTicketWithInvalidService()
    {
        $this->expectException(CasException::class);
        $this->expectExceptionMessage(CasException::UNAUTHORIZED_SERVICE_PROXY);
        app()->make(PGTicketRepository::class)->applyTicket(new User(), 'what ever');
    }

    public function testApplyTicketWithNonProxyService()
    {
        $user = new User();
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => false]);
        $service->save();

        $this->mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service);

        $this->expectException(CasException::class);
        $this->expectExceptionMessage(CasException::UNAUTHORIZED_SERVICE_PROXY);
        app()->make(PGTicketRepository::class)->applyTicket($user, 'what ever');
    }

    public function testApplyTicketWithValidServiceButApplyTicketFailed()
    {
        $user = new User();
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();

        $this->mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service)
            ->once();

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generate')
            ->andReturnFalse()
            ->once();

        $this->expectException(CasException::class);
        $this->expectExceptionMessage('apply proxy-granting ticket failed');
        app()->make(PGTicketRepository::class)->applyTicket($user, 'what ever');
    }

    public function testApplyTicketSuccess()
    {
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();

        $ticketStr = 'ST-abc';
        $pgtUrl = 'what ever';
        $proxies = ['https://proxy2.com', 'https://proxy1.com'];

        $this->mock(ServiceRepository::class)
            ->shouldReceive('getServiceByUrl')
            ->andReturn($service);

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generate')
            ->andReturn($ticketStr)
            ->once();

        $record = app()->make(PGTicketRepository::class)->applyTicket($user, $pgtUrl, $proxies);
        $this->assertEquals($ticketStr, $record->ticket);
        $this->assertEquals($pgtUrl, $record->pgt_url);
        $this->assertEquals($proxies, $record->proxies);
    }

    public function testGetByTicket()
    {
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();
        $ticket = new PGTicket();
        $ticket->ticket = 'ST-abc';
        $ticket->pgt_url = 'https://leo108.com';
        $ticket->expire_at = now()->subSeconds(100);
        $ticket->created_at = now()->subSeconds(600);
        $ticket->user()->associate($user);
        $ticket->service()->associate($service);
        $ticket->save();
        $this->assertNull(app()->make(PGTicketRepository::class)->getByTicket('ST-not-exist'));
        $this->assertNull(app()->make(PGTicketRepository::class)->getByTicket('ST-abc'));

        $ticket->expire_at = now()->addSeconds(100);
        $ticket->save();
        $this->assertEquals($ticket->id, app()->make(PGTicketRepository::class)->getByTicket('ST-abc')->id);
    }

    public function testInvalidTicketByUser()
    {
        $service = new Service(['id' => 1, 'name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();

        $ticket = new PGTicket();
        $ticket->ticket = 'ST-abc';
        $ticket->pgt_url = 'https://leo108.com';
        $ticket->expire_at = now()->addSeconds(100);
        $ticket->created_at = now()->subSeconds(600);
        $ticket->user()->associate($user);
        $ticket->service()->associate($service);
        $ticket->save();

        $this->assertDatabaseHas(PGTicket::class, ['id' => $ticket->id]);
        app()->make(PGTicketRepository::class)->invalidTicketByUser($user);
        $this->assertDatabaseMissing(PGTicket::class, ['id' => $ticket->id]);
    }
}
