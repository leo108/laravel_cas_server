<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/12
 * Time: 14:50
 */

namespace Leo108\CAS\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Leo108\CAS\Contracts\Interactions\UserLogin;
use Leo108\CAS\Events\CasUserLoginEvent;
use Leo108\CAS\Events\CasUserLogoutEvent;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Repositories\ServiceRepository;
use Leo108\CAS\Repositories\TicketRepository;
use TestCase;
use Mockery;
use User;

class SecurityControllerTest extends TestCase
{
    public function testShowLogin()
    {
        //not logged in with valid service url
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('showLoginPage')
            ->andReturnUsing(
                function ($request, $errors) {
                    $this->assertEmpty($errors);

                    return 'show login called';
                }
            )
            ->shouldReceive('getCurrentUser')
            ->andReturn(false)
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->getMock();
        $this->assertEquals('show login called', app()->make(SecurityController::class)->showLogin($request));

        //not logged in with invalid service url
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('showLoginPage')
            ->andReturnUsing(
                function ($request, $errors) {
                    $this->assertNotEmpty($errors);
                    $this->assertEquals(CasException::INVALID_SERVICE, $errors[0]);

                    return 'show login called';
                }
            )
            ->shouldReceive('getCurrentUser')
            ->andReturn(false)
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->getMock();
        $this->assertEquals('show login called', app()->make(SecurityController::class)->showLogin($request));

        //logged in with valid service url without warn parameter
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->getMock();
        $ticketRepository  = Mockery::mock(TicketRepository::class);
        $loginInteraction  = Mockery::mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(true)//just not false is OK
            ->getMock();
        $request           = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->shouldReceive('get')
            ->withArgs(['warn'])
            ->andReturn(false)
            ->getMock();
        $controller        = Mockery::mock(
            SecurityController::class,
            [$serviceRepository, $ticketRepository, $loginInteraction]
        )
            ->makePartial()
            ->shouldReceive('authenticated')
            ->andReturn('authenticated called')
            ->getMock();
        $this->assertEquals('authenticated called', $controller->showLogin($request));
    }

    public function testAuthenticated()
    {
        //without service url
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('redirectToHome')
            ->andReturnUsing(
                function () {
                    return 'redirectToHome called';
                }
            )
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('')
            ->getMock();
        $this->expectsEvents(CasUserLoginEvent::class);
        $this->assertEquals('redirectToHome called', app()->make(SecurityController::class)->authenticated($request));

        //with service url but apply ticket failed
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('redirectToHome')
            ->andReturnUsing(
                function ($errors) {
                    $this->assertNotEmpty($errors);
                    $this->assertEquals(CasException::INTERNAL_ERROR, $errors[0]);

                    return 'redirectToHome called';
                }
            )
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andThrow(new CasException(CasException::INTERNAL_ERROR))
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('http://leo108.com')
            ->getMock();
        $this->expectsEvents(CasUserLoginEvent::class);
        $this->assertEquals('redirectToHome called', app()->make(SecurityController::class)->authenticated($request));

        //with service url
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $ticket           = Mockery::mock();
        $ticket->ticket   = 'ST-abc';
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($ticket)
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('http://leo108.com')
            ->getMock();
        $this->expectsEvents(CasUserLoginEvent::class);
        $resp = app()->make(SecurityController::class)->authenticated($request);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertEquals($resp->getTargetUrl(), 'http://leo108.com?ticket=ST-abc');
    }

    public function testLogout()
    {
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('logout')
            ->andReturnUsing(
                function ($request, $callback) {
                    $this->expectsEvents(CasUserLogoutEvent::class);
                    call_user_func_array($callback, [$request]);

                    return 'logout called';
                }
            )
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class);
        $this->assertEquals('logout called', app()->make(SecurityController::class)->logout($request));
    }

    public function testLogin()
    {
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('login')
            ->andReturnUsing(
                function () {
                    return 'login called';
                }
            )
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class);
        $this->assertEquals('login called', app()->make(SecurityController::class)->login($request));
    }
}
