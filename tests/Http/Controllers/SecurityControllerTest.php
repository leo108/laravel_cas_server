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

//mock function
function cas_route($name, $query)
{
    return SecurityControllerTest::$functions->cas_route($name, $query);
}

class SecurityControllerTest extends TestCase
{
    public static $functions;

    public function setUp()
    {
        parent::setUp();
        self::$functions = Mockery::mock();
    }

    public function testShowLoginWithValidServiceUrl()
    {
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once()
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
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn(false)
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->once()
            ->getMock();
        $this->assertEquals('show login called', app()->make(SecurityController::class)->showLogin($request));

    }

    public function testShowLoginWithInvalidServiceUrl()
    {
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->once()
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
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn(false)
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->once()
            ->getMock();
        $this->assertEquals('show login called', app()->make(SecurityController::class)->showLogin($request));
    }

    public function testShowLoginWhenLoggedInWithValidServiceUrlWithoutWarn()
    {
        //logged in with valid service url without warn parameter
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once()
            ->getMock();
        $ticketRepository  = Mockery::mock(TicketRepository::class);
        $user              = new User();
        $loginInteraction  = Mockery::mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn($user)
            ->once()
            ->getMock();
        $request           = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->once()
            ->shouldReceive('get')
            ->withArgs(['warn'])
            ->andReturn(false)
            ->once()
            ->getMock();
        $controller        = Mockery::mock(
            SecurityController::class,
            [$serviceRepository, $ticketRepository, $loginInteraction]
        )
            ->makePartial()
            ->shouldReceive('authenticated')
            ->withArgs([$request, $user])
            ->andReturn('authenticated called')
            ->once()
            ->getMock();
        $this->assertEquals('authenticated called', $controller->showLogin($request));
    }

    public function testShowLoginWhenLoggedInWithValidServiceUrlWithWarn()
    {
        //logged in with valid service url with warn parameter
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once()
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $ticketRepository = Mockery::mock(TicketRepository::class);
        app()->instance(TicketRepository::class, $ticketRepository);
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(true)//just not false is OK
            ->once()
            ->shouldReceive('showLoginWarnPage')
            ->andReturn('showLoginWarnPage called')
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request        = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->once()
            ->shouldReceive('get')
            ->withArgs(['warn'])
            ->andReturn('true')
            ->once()
            ->getMock();
        $request->query = Mockery::mock()
            ->shouldReceive('all')
            ->andReturn([])
            ->once()
            ->getMock();
        self::$functions->shouldReceive('cas_route')->andReturn('some string')->once();
        $controller = app()->make(SecurityController::class);
        $this->assertEquals('showLoginWarnPage called', $controller->showLogin($request));
    }

    public function testShowLoginWhenLoggedInWithInvalidServiceUrl()
    {
        //logged in with invalid service url
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(false)
            ->once()
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $ticketRepository = Mockery::mock(TicketRepository::class);
        app()->instance(TicketRepository::class, $ticketRepository);
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('getCurrentUser')
            ->andReturn(true)//just not false is OK
            ->once()
            ->shouldReceive('redirectToHome')
            ->andReturnUsing(
                function ($errors) {
                    $this->assertNotEmpty($errors);
                    $this->assertEquals(CasException::INVALID_SERVICE, $errors[0]);

                    return 'redirectToHome called';
                }
            )
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request    = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('what ever')
            ->once()
            ->getMock();
        $controller = app()->make(SecurityController::class);
        $this->assertEquals('redirectToHome called', $controller->showLogin($request));
    }

    public function testAuthenticatedWithoutService()
    {
        //without service url
        $user             = new User();
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('redirectToHome')
            ->andReturnUsing(
                function () {
                    return 'redirectToHome called';
                }
            )
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('')
            ->once()
            ->getMock();
        $this->expectsEvents(CasUserLoginEvent::class);
        $this->assertEquals(
            'redirectToHome called',
            app()->make(SecurityController::class)->authenticated($request, $user)
        );
    }

    public function testAuthenticatedWithService()
    {
        //with service url but apply ticket failed
        $user             = new User();
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('redirectToHome')
            ->andReturnUsing(
                function ($errors) {
                    $this->assertNotEmpty($errors);
                    $this->assertEquals(CasException::INTERNAL_ERROR, $errors[0]);

                    return 'redirectToHome called';
                }
            )
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andThrow(new CasException(CasException::INTERNAL_ERROR))
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('http://leo108.com')
            ->once()
            ->getMock();
        $this->expectsEvents(CasUserLoginEvent::class);
        $this->assertEquals(
            'redirectToHome called',
            app()->make(SecurityController::class)->authenticated($request, $user)
        );

        //with service url
        $ticket           = Mockery::mock();
        $ticket->ticket   = 'ST-abc';
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($ticket)
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('http://leo108.com')
            ->once()
            ->getMock();
        $this->expectsEvents(CasUserLoginEvent::class);
        $resp = app()->make(SecurityController::class)->authenticated($request, $user);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertEquals($resp->getTargetUrl(), 'http://leo108.com?ticket=ST-abc');
    }

    public function testLogoutWithoutService()
    {
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('logout')
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())//just not false is OK
            ->once()
            ->shouldReceive('showLoggedOut')
            ->andReturnUsing(
                function ($request) {
                    return 'showLoggedOut called';
                }
            )
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service'])
            ->andReturn(null)
            ->once()
            ->getMock();
        $this->expectsEvents(CasUserLogoutEvent::class);
        $this->assertEquals('showLoggedOut called', app()->make(SecurityController::class)->logout($request));
    }

    public function testLogoutWithValidService()
    {
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->shouldReceive('isUrlValid')
            ->andReturn(true)
            ->once()
            ->getMock();
        app()->instance(ServiceRepository::class, $serviceRepository);
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('logout')
            ->once()
            ->shouldReceive('getCurrentUser')
            ->andReturn(new User())
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['service'])
            ->andReturn('http://leo108.com')
            ->once()
            ->getMock();
        $this->expectsEvents(CasUserLogoutEvent::class);
        $resp = app()->make(SecurityController::class)->logout($request);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
    }

    public function testLogin()
    {
        $user             = new User();
        $loginInteraction = Mockery::mock(UserLogin::class)
            ->shouldReceive('login')
            ->andReturn($user)
            ->once()
            ->getMock();
        app()->instance(UserLogin::class, $loginInteraction);
        $request           = Mockery::mock(Request::class);
        $serviceRepository = Mockery::mock(ServiceRepository::class);
        $ticketRepository  = Mockery::mock(TicketRepository::class);
        $controller        = Mockery::mock(
            SecurityController::class,
            [$serviceRepository, $ticketRepository, $loginInteraction]
        )
            ->makePartial()
            ->shouldReceive('authenticated')
            ->withArgs([$request, $user])
            ->andReturn('authenticated called')
            ->once()
            ->getMock();
        $this->assertEquals('authenticated called', $controller->login($request));
    }
}
