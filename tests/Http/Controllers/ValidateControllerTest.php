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

        $objWithToString = Mockery::mock()->shouldReceive('__toString')->andReturn('string from __toString');
        self::$functions
            ->shouldReceive('method_exists')
            ->with($objWithToString, '__toString')
            ->andReturn(true)
            ->shouldReceive('method_exists')
            ->andReturn(false);

        $attributes = [
            'string'             => 'real_name',
            'simple_array'       => [1, 2, 3],
            'kv_array'           => ['key' => 'value'],
            'simple_object'      => (object) ['key' => 'value'],
            'obj_with_to_string' => $objWithToString,
            'serializable'       => new SerializableModel(),
            'resource'           => fopen(__FILE__, 'a'),
        ];
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('returnXML')
            ->andReturnUsing(
                function ($xml) use ($attributes) {
                    $this->assertInstanceOf(SimpleXMLElement::class, $xml);
                    /* @var SimpleXMLElement $xml */
                    $children = $xml->xpath('cas:authenticationSuccess');
                    $this->assertCount(1, $children);
                    $user = $children[0]->xpath('cas:user');
                    $this->assertCount(1, $user);
                    $this->assertEquals('test_name', $user[0]->__toString());
                    $attr = $children[0]->xpath('cas:attributes');
                    $this->assertCount(1, $attr);

                    $str = $attr[0]->xpath('cas:string');
                    $this->assertCount(1, $str);
                    $this->assertEquals($attributes['string'], $str[0]->__toString());

                    $str = $attr[0]->xpath('cas:simple_array');
                    $this->assertCount(1, $str);
                    $this->assertEquals(json_encode($attributes['simple_array']), $str[0]->__toString());

                    $str = $attr[0]->xpath('cas:kv_array');
                    $this->assertCount(1, $str);
                    $this->assertEquals(json_encode($attributes['kv_array']), $str[0]->__toString());

                    $str = $attr[0]->xpath('cas:simple_object');
                    $this->assertCount(1, $str);
                    $this->assertEquals(json_encode($attributes['simple_object']), $str[0]->__toString());

                    $str = $attr[0]->xpath('cas:obj_with_to_string');
                    $this->assertCount(1, $str);
                    $this->assertEquals($attributes['obj_with_to_string']->__toString(), $str[0]->__toString());

                    $str = $attr[0]->xpath('cas:serializable');
                    $this->assertCount(1, $str);
                    $this->assertEquals(serialize($attributes['serializable']), $str[0]->__toString());

                    $str = $attr[0]->xpath('cas:resource');
                    $this->assertCount(0, $str);

                    return 'returnXML called';
                }
            )
            ->getMock();
        $method     = self::getMethod($controller, 'successResponse');
        $this->assertEquals('returnXML called', $method->invokeArgs($controller, ['test_name', $attributes, 'XML',]));
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

    public function testRemoveXmlFirstLine()
    {
        $xml        = new SimpleXMLElement(ValidateController::BASE_XML);
        $controller = Mockery::mock(ValidateController::class);
        $method     = self::getMethod($controller, 'removeXmlFirstLine');
        $this->assertNotContains('<?xml version="1.0"?>', $method->invoke($controller, $xml->asXML()));

        $normalStr = 'some string';
        $this->assertEquals($normalStr, $method->invoke($controller, $normalStr));
    }

    public function testReturnXML()
    {
        $xml        = new SimpleXMLElement(ValidateController::BASE_XML);
        $controller = Mockery::mock(ValidateController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('removeXmlFirstLine')
            ->andReturn('parsed string')
            ->getMock();

        $method = self::getMethod($controller, 'returnXML');
        $resp   = $method->invoke($controller, $xml);
        $this->assertInstanceOf(Response::class, $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertTrue($resp->headers->has('Content-Type'));
        $this->assertEquals('application/xml', $resp->headers->get('Content-Type'));
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
