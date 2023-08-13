<?php

namespace Leo108\Cas\Tests\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use function Leo108\Cas\cas_route;
use Leo108\Cas\Contracts\Interactions\UserLogin;
use Leo108\Cas\Events\CasUserLoginEvent;
use Leo108\Cas\Events\CasUserLogoutEvent;
use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\Ticket;
use Leo108\Cas\Repositories\PGTicketRepository;
use Leo108\Cas\Repositories\ServiceRepository;
use Leo108\Cas\Repositories\TicketRepository;
use Leo108\Cas\Tests\Support\User;
use Leo108\Cas\Tests\TestCase;
use Mockery;

class SecurityControllerTest extends TestCase
{
    public function testShowLoginWithValidServiceUrl()
    {
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();

        $this->mock(UserLogin::class)
            ->shouldReceive('showLoginPage')
            ->withAnyArgs()
            ->andReturn(new Response('show login page'))
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn(null)
            ->once();
        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com']));
        $this->assertEquals('show login page', $resp->getContent());
    }

    public function testShowLoginWithInvalidServiceUrl()
    {
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('showLoginPage')
            ->with(Mockery::any(), [CasException::INVALID_SERVICE])
            ->andReturn(new Response('show login page'))
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn(null)
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com']));
        $this->assertEquals('show login page', $resp->getContent());
    }

    public function testShowLoginWhenLoggedInWithValidServiceUrlWithoutWarn()
    {
        Event::fake();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();

        $user = new User();
        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn($user)
            ->once()
            ->getMock();

        $ticket = new Ticket(['ticket' => '123456']);

        $this->mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->with($user, 'https://leo108.com/?foo=bar')
            ->andReturn($ticket)
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com/?foo=bar', 'warn' => 'false']));
        $resp->assertRedirect('https://leo108.com/?foo=bar&ticket=123456');

        Event::assertDispatched(CasUserLoginEvent::class);
    }

    public function testShowLoginWhenLoggedInWithValidServiceUrlWithWarn()
    {
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();

        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->once()
            ->shouldReceive('showLoginWarnPage')
            ->with(Mockery::any(), cas_route('login.get', ['service' => 'https://leo108.com/?foo=bar']), 'https://leo108.com/?foo=bar')
            ->andReturn(new Response('showLoginWarnPage'))
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com/?foo=bar', 'warn' => 'true']));
        $this->assertEquals('showLoginWarnPage', $resp->getContent());
    }

    public function testShowLoginWhenLoggedInWithInvalidServiceUrl()
    {
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->once();

        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->once()
            ->shouldReceive('redirectToHome')
            ->with([CasException::INVALID_SERVICE])
            ->andReturn(new Response('redirectToHome'))
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com/']));
        $this->assertEquals('redirectToHome', $resp->getContent());
    }

    public function testAuthenticatedWithoutService()
    {
        Event::fake();

        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->once()
            ->shouldReceive('redirectToHome')
            ->andReturn(new Response('redirectToHome'))
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => '']));
        $this->assertEquals('redirectToHome', $resp->getContent());

        Event::assertDispatched(CasUserLoginEvent::class);
    }

    public function testAuthenticatedWithServiceFailed()
    {
        Event::fake();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->once()
            ->shouldReceive('redirectToHome')
            ->with([CasException::INTERNAL_ERROR])
            ->andReturn(new Response('redirectToHome'))
            ->once();
        $this->mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andThrow(new CasException(CasException::INTERNAL_ERROR))
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com']));
        $this->assertEquals('redirectToHome', $resp->getContent());

        Event::assertDispatched(CasUserLoginEvent::class);
    }

    public function testAuthenticatedWithServiceSuccess()
    {
        Event::fake();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->once();
        $ticket = new Ticket();
        $ticket->ticket = 'ST-abc';
        $this->mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($ticket)
            ->once();

        $resp = $this->get(cas_route('login.get', ['service' => 'https://leo108.com']));
        $resp->assertRedirect('https://leo108.com?ticket=ST-abc');

        Event::assertDispatched(CasUserLoginEvent::class);
    }

    public function testLogoutWhenNotLoggedInWithoutService()
    {
        $this->mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(null)
            ->once()
            ->shouldReceive('showLoggedOut')
            ->andReturn(new Response('showLoggedOut'))
            ->once();

        Event::fake();
        $resp = $this->get(cas_route('logout'));
        $this->assertEquals('showLoggedOut', $resp->getContent());
        Event::assertNotDispatched(CasUserLogoutEvent::class);
    }

    public function testLogoutWithoutService()
    {
        $user = $this->initUser();
        $this->mock(UserLogin::class)
            ->shouldReceive('logout')
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn($user)
            ->once()
            ->shouldReceive('showLoggedOut')
            ->andReturn(new Response('showLoggedOut'))
            ->once();

        $this->mock(PGTicketRepository::class)
            ->shouldReceive('invalidTicketByUser')
            ->with($user)
            ->once();

        Event::fake();
        $resp = $this->get(cas_route('logout'));
        $this->assertEquals('showLoggedOut', $resp->getContent());
        Event::assertDispatched(CasUserLogoutEvent::class);
    }

    public function testLogoutWithValidService()
    {
        $user = $this->initUser();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('logout')
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn($user)
            ->once();
        $this->mock(PGTicketRepository::class)
            ->shouldReceive('invalidTicketByUser')
            ->with($user)
            ->once()
            ->getMock();
        Event::fake();
        $resp = $this->get(cas_route('logout', ['service' => 'https://leo108.com']));
        $resp->assertRedirect('https://leo108.com');
        Event::assertDispatched(CasUserLogoutEvent::class);
    }

    public function testLogoutWithInvalidService()
    {
        $user = $this->initUser();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('logout')
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn($user)
            ->once()
            ->shouldReceive('showLoggedOut')
            ->andReturn(new Response('showLoggedOut'))
            ->once();
        $this->mock(PGTicketRepository::class)
            ->shouldReceive('invalidTicketByUser')
            ->with($user)
            ->once()
            ->getMock();
        Event::fake();
        $resp = $this->get(cas_route('logout', ['service' => 'https://leo108.com']));
        $this->assertEquals('showLoggedOut', $resp->getContent());
        Event::assertDispatched(CasUserLogoutEvent::class);
    }

    public function testLoginFailed()
    {
        $this->mock(UserLogin::class)
            ->shouldReceive('login')
            ->andReturn(null)
            ->once()
            ->shouldReceive('showAuthenticateFailed')
            ->andReturn(new Response('showAuthenticateFailed'))
            ->once();
        $resp = $this->post(cas_route('login.post'));
        $this->assertEquals('showAuthenticateFailed', $resp->getContent());
    }

    public function testLoginSuccessWithInvalidService()
    {
        $user = $this->initUser();
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('redirectToHome')
            ->with([CasException::INVALID_SERVICE])
            ->andReturn(new Response('redirectToHome'))
            ->once()
            ->shouldReceive('login')
            ->andReturn($user)
            ->once();
        $resp = $this->post(cas_route('login.post', ['service' => 'https://leo108.com']));
        $this->assertEquals('redirectToHome', $resp->getContent());
    }

    public function testLoginSuccessWithValidService()
    {
        $user = $this->initUser();
        $ticket = new Ticket(['ticket' => '123456']);
        $this->mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once();
        $this->mock(UserLogin::class)
            ->shouldReceive('login')
            ->andReturn($user)
            ->once();
        $this->mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($ticket)
            ->once();

        Event::fake();
        $resp = $this->post(cas_route('login.post', ['service' => 'https://leo108.com']));
        $resp->assertRedirect('https://leo108.com?ticket=123456');
        Event::assertDispatched(CasUserLoginEvent::class);
    }

    protected function initUser(): User
    {
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();

        return $user;
    }
}
