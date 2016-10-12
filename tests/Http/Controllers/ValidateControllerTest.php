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
use ReflectionClass;
use SimpleXMLElement;
use TestCase;
use Mockery;
use User;

class ValidateControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
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
        $method           = self::getMethod($controller, 'casValidate');
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
        $method           = self::getMethod($controller, 'casValidate');
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
        $method           = self::getMethod($controller, 'casValidate');
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
        $method           = self::getMethod($controller, 'casValidate');
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
                function ($name, $attr, $format) {
                    $this->assertEquals('test_user', $name);
                    $this->assertEmpty($attr);
                    $this->assertEquals('JSON', $format);

                    return 'successResponse called';
                }
            )
            ->getMock();

        $method = self::getMethod($controller, 'casValidate');
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
        $method     = self::getMethod($controller, 'lockTicket');
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
        $method     = self::getMethod($controller, 'unlockTicket');
        $this->assertEquals('releaseLock called', $method->invokeArgs($controller, ['str', 30]));
    }

    public function testSuccessResponse()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial();
        $method     = self::getMethod($controller, 'successResponse');
        $resp       = $method->invokeArgs($controller, ['test_name', [], 'JSON']);

        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals(
            [
                'serviceResponse' => [
                    'authenticationSuccess' => [
                        'user' => 'test_name',
                    ],
                ],
            ],
            $resp->getOriginalContent()
        );

        $resp = $method->invokeArgs($controller, ['test_name', ['real_name' => 'real_name'], 'JSON']);

        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals(
            [
                'serviceResponse' => [
                    'authenticationSuccess' => [
                        'user'       => 'test_name',
                        'attributes' => ['real_name' => 'real_name'],
                    ],
                ],
            ],
            $resp->getOriginalContent()
        );

        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('returnXML')
            ->andReturnUsing(
                function ($xml) {
                    $this->assertInstanceOf(SimpleXMLElement::class, $xml);
                    /* @var SimpleXMLElement $xml */
                    $children = $xml->xpath('cas:authenticationSuccess');
                    $this->assertCount(1, $children);
                    $children = $children[0]->xpath('cas:user');
                    $this->assertCount(1, $children);
                    $this->assertEquals('test_name', $children[0]->__toString());

                    return 'returnXML called';
                }
            )
            ->getMock();
        $method     = self::getMethod($controller, 'successResponse');
        $this->assertEquals('returnXML called', $method->invokeArgs($controller, ['test_name', [], 'XML']));

        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('returnXML')
            ->andReturnUsing(
                function ($xml) {
                    $this->assertInstanceOf(SimpleXMLElement::class, $xml);
                    /* @var SimpleXMLElement $xml */
                    $children = $xml->xpath('cas:authenticationSuccess');
                    $this->assertCount(1, $children);
                    $user = $children[0]->xpath('cas:user');
                    $this->assertCount(1, $user);
                    $this->assertEquals('test_name', $user[0]->__toString());
                    $attr = $children[0]->xpath('cas:attributes');
                    $this->assertCount(1, $attr);
                    $rn = $attr[0]->xpath('cas:real_name');
                    $this->assertCount(1, $rn);
                    $this->assertEquals('real_name', $rn[0]->__toString());

                    return 'returnXML called';
                }
            )
            ->getMock();
        $method     = self::getMethod($controller, 'successResponse');
        $this->assertEquals(
            'returnXML called',
            $method->invokeArgs($controller, ['test_name', ['real_name' => 'real_name'], 'XML'])
        );
    }

    public function testFailureResponse()
    {
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial();
        $method     = self::getMethod($controller, 'failureResponse');
        $resp       = $method->invokeArgs($controller, ['code', 'desc', 'JSON']);

        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals(
            [
                'serviceResponse' => [
                    'authenticationFailure' => [
                        'code'        => 'code',
                        'description' => 'desc',
                    ],
                ],
            ],
            $resp->getOriginalContent()
        );

        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('returnXML')
            ->andReturnUsing(
                function ($xml) {
                    $this->assertInstanceOf(SimpleXMLElement::class, $xml);
                    /* @var SimpleXMLElement $xml */
                    $children = $xml->xpath('cas:authenticationFailure');
                    $this->assertCount(1, $children);
                    $this->assertEquals('desc', $children[0]->__toString());
                    $this->assertEquals('code', $children[0]['code']);

                    return 'returnXML called';
                }
            )
            ->getMock();
        $method     = self::getMethod($controller, 'failureResponse');
        $this->assertEquals('returnXML called', $method->invokeArgs($controller, ['code', 'desc', 'XML']));
    }

    protected static function getMethod($obj, $name)
    {
        $class  = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
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

    protected function prepareCASXml(SimpleXMLElement $xml)
    {
        $xml->registerXPathNamespace('cas', 'http://www.yale.edu/tp/cas');

        return $xml;
    }
}
