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
use Leo108\CAS\Repositories\PGTicketRepository;
use Leo108\CAS\Repositories\TicketRepository;
use Leo108\CAS\Responses\JsonAuthenticationFailureResponse;
use Leo108\CAS\Responses\JsonAuthenticationSuccessResponse;
use Leo108\CAS\Responses\XmlAuthenticationFailureResponse;
use Leo108\CAS\Responses\XmlAuthenticationSuccessResponse;
use Leo108\CAS\Services\PGTCaller;
use Leo108\CAS\Services\TicketGenerator;
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

    public function testV1ValidateActionWithInvalidRequest()
    {
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
    }

    public function testV1ValidateActionWithLockFailed()
    {
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
    }

    public function testV1ValidateActionWithInvalidTicket()
    {
        $request          = $this->getValidRequest();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturnNull()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->getMock();
        $resp       = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('no', $resp->getOriginalContent());
    }

    public function testV1ValidateActionWithValidTicketButServiceMismatch()
    {
        $request             = $this->getValidRequest();
        $ticket              = Mockery::mock();
        $ticket->service_url = 'http//google.com';
        $ticketRepository    = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->getMock();
        $resp       = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('no', $resp->getOriginalContent());
    }

    public function testV1ValidateActionWithValidTicketAndService()
    {
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
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->shouldReceive('unlockTicket')
            ->getMock();
        $resp       = $controller->v1ValidateAction($request);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals('yes', $resp->getOriginalContent());
    }

    public function testV2ServiceValidateAction()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('casValidate')
            ->andReturnUsing(
                function ($request, $returnAttr, $allowProxy) {
                    $this->assertFalse($returnAttr);
                    $this->assertFalse($allowProxy);

                    return 'casValidate called';
                }
            )
            ->once()
            ->getMock();
        $request    = Mockery::mock(Request::class);
        $this->assertEquals('casValidate called', $controller->v2ServiceValidateAction($request));
    }

    public function testV2ProxyValidateAction()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('casValidate')
            ->andReturnUsing(
                function ($request, $returnAttr, $allowProxy) {
                    $this->assertFalse($returnAttr);
                    $this->assertTrue($allowProxy);

                    return 'casValidate called';
                }
            )
            ->once()
            ->getMock();
        $request    = Mockery::mock(Request::class);
        $this->assertEquals('casValidate called', $controller->v2ProxyValidateAction($request));
    }

    public function testV3ServiceValidateAction()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('casValidate')
            ->andReturnUsing(
                function ($request, $returnAttr, $allowProxy) {
                    $this->assertTrue($returnAttr);
                    $this->assertFalse($allowProxy);

                    return 'casValidate called';
                }
            )
            ->once()
            ->getMock();
        $request    = Mockery::mock(Request::class);
        $this->assertEquals('casValidate called', $controller->v3ServiceValidateAction($request));
    }

    public function testV3ProxyValidateAction()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('casValidate')
            ->andReturnUsing(
                function ($request, $returnAttr, $allowProxy) {
                    $this->assertTrue($returnAttr);
                    $this->assertTrue($allowProxy);

                    return 'casValidate called';
                }
            )
            ->once()
            ->getMock();
        $request    = Mockery::mock(Request::class);
        $this->assertEquals('casValidate called', $controller->v3ProxyValidateAction($request));
    }

    public function testProxyActionWithInvalidRequest()
    {
        $request    = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->with('pgt', '')
            ->andReturn('')
            ->once()
            ->shouldReceive('get')
            ->with('targetService', '')
            ->andReturn('')
            ->once()
            ->shouldReceive('get')
            ->with('format', 'XML')
            ->andReturn('XML')
            ->once()
            ->getMock();
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('proxyFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_REQUEST, $code);
                    $this->assertEquals('param pgt and targetService can not be empty', $desc);
                    $this->assertEquals('XML', $format);

                    return 'proxyFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $this->assertEquals('proxyFailureResponse called', $controller->proxyAction($request));
    }

    public function testProxyActionWithInvalidTicket()
    {
        $request       = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->with('pgt', '')
            ->andReturn('pgt string')
            ->once()
            ->shouldReceive('get')
            ->with('targetService', '')
            ->andReturn('http://target.com')
            ->once()
            ->shouldReceive('get')
            ->with('format', 'XML')
            ->andReturn('XML')
            ->once()
            ->getMock();
        $pgtRepository = Mockery::mock(PGTicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn(false)
            ->once()
            ->getMock();
        app()->instance(PGTicketRepository::class, $pgtRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('proxyFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_TICKET, $code);
                    $this->assertEquals('ticket is not valid', $desc);
                    $this->assertEquals('XML', $format);

                    return 'proxyFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $this->assertEquals('proxyFailureResponse called', $controller->proxyAction($request));
    }

    public function testCasValidateWithInvalidRequest()
    {
        $request = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['ticket', ''])
            ->andReturn('')
            ->once()
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('')
            ->once()
            ->shouldReceive('get')
            ->withArgs(['format', 'XML'])
            ->andReturn('JSON')
            ->once()
            ->getMock();

        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('authFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_REQUEST, $code);
                    $this->assertEquals('param service and ticket can not be empty', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'authFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $method     = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authFailureResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateAndLockTicketFailed()
    {
        $request    = $this->getValidRequest();
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(false)
            ->once()
            ->shouldReceive('authFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INTERNAL_ERROR, $code);
                    $this->assertEquals('try to lock ticket failed', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'authFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $method     = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authFailureResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithInvalidTicket()
    {
        $request          = $this->getValidRequest();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturnNull()
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('authFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_TICKET, $code);
                    $this->assertEquals('ticket is not valid', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'authFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $method     = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authFailureResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidTicketButServiceMismatch()
    {
        $request          = $this->getValidRequest();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://github.com')
            ->once()
            ->shouldReceive('isProxy')
            ->andReturn(false)
            ->once()
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('invalidTicket')
            ->once()
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('authFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_SERVICE, $code);
                    $this->assertEquals('service is not valid', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'authFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $method     = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authFailureResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidProxyTicketButNotAllowProxy()
    {
        $request          = $this->getValidRequest();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(true)
            ->once()
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('invalidTicket')
            ->once()
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('authFailureResponse')
            ->andReturnUsing(
                function ($code, $desc, $format) {
                    $this->assertEquals(CasException::INVALID_TICKET, $code);
                    $this->assertEquals('ticket is not valid', $desc);
                    $this->assertEquals('JSON', $format);

                    return 'authFailureResponse called';
                }
            )
            ->once()
            ->getMock();
        $method     = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authFailureResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidProxyTicketAndAllowProxy()
    {
        $proxies          = ['http://proxy1.com', 'http://proxy2.com'];
        $request          = $this->getValidRequest('');
        $user             = Mockery::mock(User::class)
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->once()
            ->getMock();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(true)
            ->once()
            ->shouldReceive('getAttribute')
            ->with('proxies')
            ->andReturn($proxies)
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->times(2)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('invalidTicket')
            ->once()
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('authSuccessResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes, $proxiesParam, $iou) use ($proxies) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);
                    $this->assertEquals($proxies, $proxiesParam);
                    $this->assertNull($iou);

                    return 'authSuccessResponse called';
                }
            )
            ->once()
            ->getMock();
        $method     = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authSuccessResponse called', $method->invokeArgs($controller, [$request, false, true]));
    }

    public function testCasValidateWithValidTicketAndServiceAndNoPgt()
    {
        $request          = $this->getValidRequest('');
        $user             = Mockery::mock(User::class)
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->once()
            ->getMock();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(false)
            ->times(2)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->times(2)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->shouldReceive('invalidTicket')
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('authSuccessResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes, $proxies, $iou) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);
                    $this->assertEmpty($proxies);
                    $this->assertNull($iou);

                    return 'authSuccessResponse called';
                }
            )
            ->once()
            ->getMock();

        $method = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authSuccessResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidTicketAndServiceAndPgtButApplyPGTFailed()
    {
        $request          = $this->getValidRequest('http://app1.com/pgtCallback');
        $user             = Mockery::mock(User::class)
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->once()
            ->getMock();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(false)
            ->times(2)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->times(2)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->shouldReceive('invalidTicket')
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $pgTicketRepository = Mockery::mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andThrow(new CasException(CasException::INTERNAL_ERROR))
            ->once()
            ->getMock();
        app()->instance(PGTicketRepository::class, $pgTicketRepository);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('authSuccessResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes, $proxies, $iou) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);
                    $this->assertEmpty($proxies);
                    $this->assertNull($iou);

                    return 'authSuccessResponse called';
                }
            )
            ->once()
            ->getMock();

        $method = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authSuccessResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidTicketAndServiceAndPgtButCallPgtUrlFailed()
    {
        $request          = $this->getValidRequest('http://app1.com/pgtCallback');
        $user             = Mockery::mock(User::class)
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->once()
            ->getMock();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(false)
            ->times(2)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->times(2)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->shouldReceive('invalidTicket')
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $pgTicketRepository = Mockery::mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn('some string')
            ->once()
            ->getMock();
        app()->instance(PGTicketRepository::class, $pgTicketRepository);
        $ticketGenerator = Mockery::mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->once()
            ->getMock();
        app()->instance(TicketGenerator::class, $ticketGenerator);
        $pgtCaller = Mockery::mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(false)
            ->once()
            ->getMock();
        app()->instance(PGTCaller::class, $pgtCaller);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('authSuccessResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes, $proxies, $iou) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);
                    $this->assertEmpty($proxies);
                    $this->assertNull($iou);

                    return 'authSuccessResponse called';
                }
            )
            ->once()
            ->getMock();

        $method = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authSuccessResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidTicketAndServiceAndPgtButCallPgtUrlSuccess()
    {
        $request          = $this->getValidRequest('http://app1.com/pgtCallback');
        $user             = Mockery::mock(User::class)
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->once()
            ->getMock();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(false)
            ->times(2)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->times(2)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->shouldReceive('invalidTicket')
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $pgTicketRepository = Mockery::mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn('some string')
            ->once()
            ->getMock();
        app()->instance(PGTicketRepository::class, $pgTicketRepository);
        $ticketGenerator = Mockery::mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->once()
            ->getMock();
        app()->instance(TicketGenerator::class, $ticketGenerator);
        $pgtCaller = Mockery::mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(true)
            ->once()
            ->getMock();
        app()->instance(PGTCaller::class, $pgtCaller);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('authSuccessResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes, $proxies, $iou) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);
                    $this->assertEmpty($proxies);
                    $this->assertEquals('pgtiou string', $iou);

                    return 'authSuccessResponse called';
                }
            )
            ->once()
            ->getMock();

        $method = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authSuccessResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testCasValidateWithValidProxyTicketAndServiceAndPgtButCallPgtUrlSuccess()
    {
        $request          = $this->getValidRequest('http://app1.com/pgtCallback');
        $user             = Mockery::mock(User::class)
            ->shouldReceive('getName')
            ->andReturn('test_user')
            ->once()
            ->getMock();
        $ticket           = Mockery::mock(Ticket::class)
            ->shouldReceive('isProxy')
            ->andReturn(false)
            ->times(2)
            ->shouldReceive('getAttribute')
            ->withArgs(['service_url'])
            ->andReturn('http://leo108.com')
            ->once()
            ->shouldReceive('getAttribute')
            ->withArgs(['user'])
            ->andReturn($user)
            ->times(2)
            ->getMock();
        $ticketRepository = Mockery::mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticket)
            ->once()
            ->shouldReceive('invalidTicket')
            ->once()
            ->getMock();
        app()->instance(TicketRepository::class, $ticketRepository);
        $pgTicketRepository = Mockery::mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn('some string')
            ->once()
            ->getMock();
        app()->instance(PGTicketRepository::class, $pgTicketRepository);
        $ticketGenerator = Mockery::mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->once()
            ->getMock();
        app()->instance(TicketGenerator::class, $ticketGenerator);
        $pgtCaller = Mockery::mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(true)
            ->once()
            ->getMock();
        app()->instance(PGTCaller::class, $pgtCaller);
        $controller = $this->initController()
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('lockTicket')
            ->andReturn(true)
            ->once()
            ->shouldReceive('unlockTicket')
            ->once()
            ->shouldReceive('authSuccessResponse')
            ->andReturnUsing(
                function ($name, $format, $attributes, $proxies, $iou) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attributes);
                    $this->assertEquals('JSON', $format);
                    $this->assertEmpty($proxies);
                    $this->assertEquals('pgtiou string', $iou);

                    return 'authSuccessResponse called';
                }
            )
            ->once()
            ->getMock();

        $method = self::getNonPublicMethod($controller, 'casValidate');
        $this->assertEquals('authSuccessResponse called', $method->invokeArgs($controller, [$request, false, false]));
    }

    public function testLockTicket()
    {
        $locker = Mockery::mock(TicketLocker::class)
            ->shouldReceive('acquireLock')
            ->andReturn('acquireLock called')
            ->once()
            ->getMock();
        app()->instance(TicketLocker::class, $locker);
        $controller = $this->initController()
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'lockTicket');
        $this->assertEquals('acquireLock called', $method->invokeArgs($controller, ['str', 30]));
    }

    public function testUnlockTicket()
    {
        $locker = Mockery::mock(TicketLocker::class)
            ->shouldReceive('releaseLock')
            ->andReturn('releaseLock called')
            ->once()
            ->getMock();
        app()->instance(TicketLocker::class, $locker);
        $controller = $this->initController()
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'unlockTicket');
        $this->assertEquals('releaseLock called', $method->invokeArgs($controller, ['str', 30]));
    }

    public function testAuthSuccessResponse()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'authSuccessResponse');

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

    public function testAuthFailureResponse()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial();
        $method     = self::getNonPublicMethod($controller, 'authFailureResponse');
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

    protected function getValidRequest($pgt = null)
    {
        $mock = Mockery::mock(Request::class)
            ->shouldReceive('get')
            ->withArgs(['ticket', ''])
            ->andReturn('ticket')
            ->shouldReceive('get')
            ->withArgs(['service', ''])
            ->andReturn('http://leo108.com')
            ->shouldReceive('get')
            ->withArgs(['format', 'XML'])
            ->andReturn('JSON');
        if (!is_null($pgt)) {
            $mock->shouldReceive('get')
                ->withArgs(['pgtUrl', ''])
                ->andReturn($pgt);
        }

        return $mock->getMock();
    }

    /**
     * @return Mockery\MockInterface
     */
    protected function initController()
    {
        return Mockery::mock(
            ValidateController::class,
            [
                app(TicketLocker::class),
                app(TicketRepository::class),
                app(PGTicketRepository::class),
                app(TicketGenerator::class),
                app(PGTCaller::class),
            ]
        );
    }
}
