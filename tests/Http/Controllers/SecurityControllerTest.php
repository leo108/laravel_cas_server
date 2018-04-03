<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/12
 * Time: 14:50
 */

namespace Leo108\CAS {

    use Leo108\CAS\Http\Controllers\SecurityControllerTest;

    //mock function
    function cas_route($name, $query)
    {
        return SecurityControllerTest::$functions->cas_route($name, $query);
    }
}

namespace Leo108\CAS\Http\Controllers {

    use Illuminate\Http\RedirectResponse;
    use Illuminate\Http\Request;
    use Leo108\CAS\Contracts\Interactions\UserLogin;
    use Leo108\CAS\Contracts\Models\UserModel;
    use Leo108\CAS\Events\CasUserLoginEvent;
    use Leo108\CAS\Events\CasUserLogoutEvent;
    use Leo108\CAS\Exceptions\CAS\CasException;
    use Leo108\CAS\Repositories\PGTicketRepository;
    use Leo108\CAS\Repositories\ServiceRepository;
    use Leo108\CAS\Repositories\TicketRepository;
    use TestCase;
    use Mockery;

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
            $request           = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service', ''])
                ->andReturn('what ever')
                ->once()
                ->getMock();
            $serviceRepository = Mockery::mock(ServiceRepository::class)
                ->shouldReceive('isUrlValid')
                ->andReturn(true)
                ->once()
                ->getMock();
            app()->instance(ServiceRepository::class, $serviceRepository);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('showLoginPage')
                ->with($request, [])
                ->andReturn('show login called')
                ->once()
                ->shouldReceive('getCurrentUser')
                ->andReturn(false)
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $this->assertEquals('show login called', app(SecurityController::class)->showLogin($request));
        }

        public function testShowLoginWithInvalidServiceUrl()
        {
            $request           = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service', ''])
                ->andReturn('what ever')
                ->once()
                ->getMock();
            $serviceRepository = Mockery::mock(ServiceRepository::class)
                ->shouldReceive('isUrlValid')
                ->andReturn(false)
                ->once()
                ->getMock();
            app()->instance(ServiceRepository::class, $serviceRepository);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('showLoginPage')
                ->with($request, [CasException::INVALID_SERVICE])
                ->andReturn('show login called')
                ->once()
                ->shouldReceive('getCurrentUser')
                ->andReturn(false)
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);

            $this->assertEquals('show login called', app(SecurityController::class)->showLogin($request));
        }

        public function testShowLoginWhenLoggedInWithValidServiceUrlWithoutWarn()
        {
            $serviceRepository = Mockery::mock(ServiceRepository::class)
                ->shouldReceive('isUrlValid')
                ->andReturn(true)
                ->once()
                ->getMock();
            app()->instance(ServiceRepository::class, $serviceRepository);
            $user             = Mockery::mock(UserModel::class);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('getCurrentUser')
                ->andReturn($user)
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $request    = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service', ''])
                ->andReturn('what ever')
                ->once()
                ->shouldReceive('get')
                ->withArgs(['warn'])
                ->andReturn(false)
                ->once()
                ->getMock();
            $controller = $this->initController()
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
            $this->assertEquals('showLoginWarnPage called', app(SecurityController::class)->showLogin($request));
        }

        public function testShowLoginWhenLoggedInWithInvalidServiceUrl()
        {
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
                ->with([CasException::INVALID_SERVICE])
                ->andReturn('redirectToHome called')
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $request = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service', ''])
                ->andReturn('what ever')
                ->once()
                ->getMock();
            $this->assertEquals('redirectToHome called', app(SecurityController::class)->showLogin($request));
        }

        public function testAuthenticatedWithoutService()
        {
            $user             = Mockery::mock(UserModel::class);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('redirectToHome')
                ->andReturn('redirectToHome called')
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
            $this->assertEquals('redirectToHome called',
                app(SecurityController::class)->authenticated($request, $user));
        }

        public function testAuthenticatedWithService()
        {
            //with service url but apply ticket failed
            $user             = Mockery::mock(UserModel::class);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('redirectToHome')
                ->with([CasException::INTERNAL_ERROR])
                ->andReturn('redirectToHome called')
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
            $this->assertEquals('redirectToHome called',
                app(SecurityController::class)->authenticated($request, $user));

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
            $resp = app(SecurityController::class)->authenticated($request, $user);
            $this->assertInstanceOf(RedirectResponse::class, $resp);
            $this->assertEquals($resp->getTargetUrl(), 'http://leo108.com?ticket=ST-abc');
        }

        public function testLogoutWhenNotLoggedInWithoutService()
        {
            $request          = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service'])
                ->andReturn(null)
                ->once()
                ->getMock();
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('getCurrentUser')
                ->andReturn(false)
                ->once()
                ->shouldReceive('showLoggedOut')
                ->with($request)
                ->andReturn('showLoggedOut called')
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $this->doesntExpectEvents(CasUserLogoutEvent::class);
            $this->assertEquals('showLoggedOut called', app(SecurityController::class)->logout($request));
        }

        public function testLogoutWithoutService()
        {
            $user             = Mockery::mock(UserModel::class);
            $request          = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service'])
                ->andReturn(null)
                ->once()
                ->getMock();
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('logout')
                ->once()
                ->shouldReceive('getCurrentUser')
                ->andReturn($user)
                ->once()
                ->shouldReceive('showLoggedOut')
                ->with($request)
                ->andReturn('showLoggedOut called')
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $pgTicketRepository = Mockery::mock(PGTicketRepository::class)
                ->shouldReceive('invalidTicketByUser')
                ->with($user)
                ->once()
                ->getMock();
            app()->instance(PGTicketRepository::class, $pgTicketRepository);
            $this->expectsEvents(CasUserLogoutEvent::class);
            $this->assertEquals('showLoggedOut called', app(SecurityController::class)->logout($request));
        }

        public function testLogoutWithValidService()
        {
            $user              = Mockery::mock(UserModel::class);
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
                ->andReturn($user)
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $request            = Mockery::mock(Request::class)
                ->shouldReceive('get')
                ->withArgs(['service'])
                ->andReturn('http://leo108.com')
                ->once()
                ->getMock();
            $pgTicketRepository = Mockery::mock(PGTicketRepository::class)
                ->shouldReceive('invalidTicketByUser')
                ->with($user)
                ->once()
                ->getMock();
            app()->instance(PGTicketRepository::class, $pgTicketRepository);
            $this->expectsEvents(CasUserLogoutEvent::class);
            $resp = app(SecurityController::class)->logout($request);
            $this->assertInstanceOf(RedirectResponse::class, $resp);
        }

        public function testLogin()
        {
            $request          = Mockery::mock(Request::class);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('login')
                ->andReturn(null)
                ->once()
                ->shouldReceive('showAuthenticateFailed')
                ->andReturn('showAuthenticateFailed called')
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $this->assertEquals('showAuthenticateFailed called', app(SecurityController::class)->login($request));

            $user             = Mockery::mock(UserModel::class);
            $loginInteraction = Mockery::mock(UserLogin::class)
                ->shouldReceive('login')
                ->andReturn($user)
                ->once()
                ->getMock();
            app()->instance(UserLogin::class, $loginInteraction);
            $request    = Mockery::mock(Request::class);
            $controller = $this->initController()
                ->makePartial()
                ->shouldReceive('authenticated')
                ->withArgs([$request, $user])
                ->andReturn('authenticated called')
                ->once()
                ->getMock();
            $this->assertEquals('authenticated called', $controller->login($request));
        }

        /**
         * @return Mockery\MockInterface
         */
        protected function initController()
        {
            return Mockery::mock(
                SecurityController::class,
                [
                    app(ServiceRepository::class),
                    app(TicketRepository::class),
                    app(PGTicketRepository::class),
                    app(UserLogin::class),
                ]
            );
        }
    }
}
