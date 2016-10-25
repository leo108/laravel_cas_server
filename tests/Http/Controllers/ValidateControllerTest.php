<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/12
 * Time: 15:56
 */

namespace Leo108\CAS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Leo108\CAS\Contracts\TicketLocker;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\Ticket;
use Leo108\CAS\Repositories\TicketRepository;
use Leo108\CAS\Responses\JsonAuthenticationFailureResponse;
use Leo108\CAS\Responses\JsonAuthenticationSuccessResponse;
use Leo108\CAS\Responses\XmlAuthenticationFailureResponse;
use Leo108\CAS\Responses\XmlAuthenticationSuccessResponse;
use SerializableModel;
use SimpleXMLElement;
use TestCase;
use Mockery;
use User;

function method_exists($obj, $method)
{
    return ValidateControllerTest::$functions->method_exists($obj, $method);
}

class ValidateControllerTest extends TestCase
{
    public static $functions;

    public function setUp()
    {
        parent::setUp();
        self::$functions = Mockery::mock();
        app()->instance(TicketLocker::class, Mockery::mock(TicketLocker::class));
    }

    public function testV1ValidateAction()
    {
        //invalid input
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['ticket', ''])
            ->andReturnNull()
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturnNull()
            ->getMock();
        $resp    = app()->make(ValidateController::class)->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('no', $resp->getOriginalContent());

        //lock failed
        $request    = $this->getValidRequest();
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(false)
            ->getMock();
        $resp       = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('no', $resp->getOriginalContent());

        //ticket not exists
        $request          = $this->getValidRequest();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturnNull()
            ->getMock();
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->getMock();
        $resp             = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('no', $resp->getOriginalContent());

        //ticket exists but service url mismatch
        $request             = $this->getValidRequest();
        $ticket              = Mockery::mock();
        $ticket->service_url = 'http//google.com';
        $ticketRepository    = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->getMock();
        $controller          = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->getMock();
        $resp                = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('no', $resp->getOriginalContent());

        //valid
        $request          = $this->getValidRequest();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->shouldReceive('invalidTicket')
            ->getMock();
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->getMock();
        $resp             = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('yes', $resp->getOriginalContent());
    }

    public function testV2ValidateAction()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('casValidate')
            ->andReturnUsing(
                function ($request, $returnAttr) {
                    $this->assertFalse($returnAttr);

                    return 'casValidate called';
                }
            )
            ->getMock();
        $request    = Mockery::mock(Request::class);
        $this->assertEquals('casValidate called', $controller->v2ValidateAction($request));
    }

    public function testV3ValidateAction()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('casValidate')
            ->andReturnUsing(
                function ($request, $returnAttr) {
                    $this->assertTrue($returnAttr);

                    return 'casValidate called';
                }
            )
            ->getMock();
        $request    = Mockery::mock(Request::class);
        $this->assertEquals('casValidate called', $controller->v3ValidateAction($request));
    }

    public function testCasValidate()
    {
        //invalid input
        $request          = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['ticket', ''])
            ->andReturn('')
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('')
            ->shouldReceive('get')
            ->withArgs(['format', 'XML'])
            ->andReturn('JSON')
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class);
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('failureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_REQUEST, $code);
                    $this->assertEquals('param service and ticket can not be empty', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'failureResponse called';
                }
            )
            ->getMock();
        $method           = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('failureResponse called', $method->invokeArgs($controller, [$request, false]));

        //lock ticket failed
        $request          = $this->getValidRequest();
        $ticketRepository = Mockery::mock(TicketRepository::class);
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(false)
            ->shouldReceive('failureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INTERNAL_ERROR, $code);
                    $this->assertEquals('try to lock ticket failed', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'failureResponse called';
                }
            )
            ->getMock();
        $method           = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('failureResponse called', $method->invokeArgs($controller, [$request, false]));

        //ticket not exists
        $request          = $this->getValidRequest();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturnNull()
            ->getMock();
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('unlockTicket')
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('failureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_TICKET, $code);
                    $this->assertEquals('ticket is not valid', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'failureResponse called';
                }
            )
            ->getMock();
        $method           = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('failureResponse called', $method->invokeArgs($controller, [$request, false]));

        //ticket exists but service url mismatch
        $request          = $this->getValidRequest();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://github.com')
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('invalidTicket')
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->getMock();
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('unlockTicket')
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('failureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_SERVICE, $code);
                    $this->assertEquals('service is not valid', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'failureResponse called';
                }
            )
            ->getMock();
        $method           = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('failureResponse called', $method->invokeArgs($controller, [$request, false]));

        //normal
        $request = $this->getValidRequest();
        $user    = Mockery::mock(User::class)
            ->shouldReceive('getCASAttributes')
            ->andReturn([])
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->getMock();
        $ticket  = Mockery::mock(Ticket::class);
        $ticket->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->shouldReceive('invalidTicket')
            ->getMock();
        $controller       = Mockery::mock(ValidateController::class, [app(TicketLocker::class), $ticketRepository])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->shouldReceive('successResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);

                    return 'successResponse called';
                }
            )
            ->getMock();

        $method = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('successResponse called', $method->invokeArgs($controller, [$request, false]));
    }

    public function testLockTicket()
    {
        $locker     = Mockery::mock(TicketLocker::class)
            ->shouldReceive('acquireLock')
            ->andReturn('acquireLock called')
            ->getMock();
        $controller = Mockery::mock(ValidateController::class, [$locker, Mockery::mock(TicketRepository::class)])
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'lockTicket');
        $this->assertEquals('acquireLock called', $method->invokeArgs($controller, ['str', 30]));
    }

    public function testUnlockTicket()
    {
        $locker = Mockery::mock(TicketLocker::class)
            ->shouldReceive('releaseLock')
            ->andReturn('releaseLock called')
            ->getMock();
        app()->instance(TicketLocker::class, $locker);
        $controller = Mockery::mock(ValidateController::class, [$locker, Mockery::mock(TicketRepository::class)])
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'unlockTicket');
        $this->assertEquals('releaseLock called', $method->invokeArgs($controller, ['str', 30]));
    }

    public function testSuccessResponse()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'successResponse');

        $name       = 'test_name';
        $attributes = [
            'real_name' => 'real_name',
        ];
        $proxies    = ['http://proxy1.com'];
        $pgt        = 'ticket';
        $jsonResp   = Mockery::mock(JsonAuthenticationSuccessResponse::class)
            ->shouldReceive('setUser')
            ->with($name)
            ->once()
            ->shouldReceive('setAttributes')
            ->with($attributes)
            ->once()
            ->shouldReceive('setProxies')
            ->with($proxies)
            ->once()
            ->shouldReceive('toResponse')
            ->once()
            ->getMock();
        app()->instance(JsonAuthenticationSuccessResponse::class, $jsonResp);
        $method->invokeArgs($controller, ['test_name', 'JSON', $attributes, $proxies, []]);

        $xmlResp = Mockery::mock(XmlAuthenticationSuccessResponse::class)
            ->shouldReceive('setUser')
            ->with($name)
            ->once()
            ->shouldReceive('setAttributes')
            ->with($attributes)
            ->once()
            ->shouldReceive('setProxies')
            ->with($proxies)
            ->once()
            ->shouldReceive('setProxyGrantingTicket')
            ->with($pgt)
            ->once()
            ->shouldReceive('toResponse')
            ->once()
            ->getMock();
        app()->instance(XmlAuthenticationSuccessResponse::class, $xmlResp);
        $method->invokeArgs($controller, ['test_name', 'XML', $attributes, $proxies, $pgt]);
    }

    public function testFailureResponse()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'failureResponse');
        $code       = 'code';
        $desc       = 'desc';
        $jsonResp   = Mockery::mock(JsonAuthenticationFailureResponse::class)
            ->shouldReceive('setFailure')
            ->withArgs([$code, $desc])
            ->once()
            ->shouldReceive('toResponse')
            ->once()
            ->getMock();
        app()->instance(JsonAuthenticationFailureResponse::class, $jsonResp);
        $method->invokeArgs($controller, [$code, $desc, 'JSON']);

        $xmlResp = Mockery::mock(XmlAuthenticationFailureResponse::class)
            ->shouldReceive('setFailure')
            ->withArgs([$code, $desc])
            ->once()
            ->shouldReceive('toResponse')
            ->once()
            ->getMock();
        app()->instance(XmlAuthenticationFailureResponse::class, $xmlResp);
        $method->invokeArgs($controller, [$code, $desc, 'XML']);
    }

    protected function getValidRequest()
    {
        return Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['ticket', ''])
            ->andReturn('ticket')
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('http://leo108.com')
            ->shouldReceive('get')
            ->withArgs(['format', 'XML'])
            ->andReturn('JSON')
            ->getMock();
    }
}
