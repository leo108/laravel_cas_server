<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 15:12
 */

namespace Leo108\CAS\Responses;

use Mockery;
use SerializableModel;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use TestCase;

function method_exists($obj, $method)
{
    return BaseXmlResponseTest::$functions->method_exists($obj, $method);
}

class BaseXmlResponseTest extends TestCase
{
    protected $testObj;
    public static $functions;

    public function setUp()
    {
        $this->testObj   = new BaseXmlResponse();
        self::$functions = Mockery::mock();
    }

    public function testStringify()
    {
        $method = self::getNonPublicMethod($this->testObj, 'stringify');

        $objWithToString = Mockery::mock()->shouldReceive('__toString')->andReturn('string from __toString');
        self::$functions
            ->shouldReceive('method_exists')
            ->with($objWithToString, '__toString')
            ->andReturn(true)
            ->shouldReceive('method_exists')
            ->andReturn(false);
        $serializableModel = new SerializableModel();
        $resource          = fopen(__FILE__, 'a');
        $this->assertEquals('string', $method->invoke($this->testObj, 'string'));
        $this->assertEquals(json_encode([1, 2, 3]), $method->invoke($this->testObj, [1, 2, 3]));
        $this->assertEquals(json_encode(['key' => 'value']), $method->invoke($this->testObj, ['key' => 'value']));
        $this->assertEquals(
            json_encode(['key' => 'value']),
            $method->invoke($this->testObj, (object) ['key' => 'value'])
        );
        $this->assertEquals($objWithToString->__toString(), $method->invoke($this->testObj, $objWithToString));
        $this->assertEquals(serialize($serializableModel), $method->invoke($this->testObj, $serializableModel));
        $this->assertFalse($method->invoke($this->testObj, $resource));
    }

    public function testRemoveXmlFirstLine()
    {
        $xml    = new SimpleXMLElement('<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>');
        $method = self::getNonPublicMethod($this->testObj, 'removeXmlFirstLine');
        $this->assertNotContains('<?xml version="1.0"?>', $method->invoke($this->testObj, $xml->asXML()));

        $normalStr = 'some string';
        $this->assertEquals($normalStr, $method->invoke($this->testObj, $normalStr));
    }

    public function testRemoveByXPath()
    {
        $xml = new SimpleXMLElement('<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>');
        $xml->addChild('cas:tag', '123');
        $this->assertContains('cas:tag', $xml->asXML());
        $this->assertContains('123', $xml->asXML());
        $method = self::getNonPublicMethod($this->testObj, 'removeByXPath');
        $method->invoke($this->testObj, $xml, 'cas:tag');
        $this->assertNotContains('cas:tag', $xml->asXML());
        $this->assertNotContains('123', $xml->asXML());
    }

    public function testGetRootNode()
    {
        $method = self::getNonPublicMethod($this->testObj, 'getRootNode');
        $this->assertContains(
            '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>',
            $method->invoke($this->testObj)->asXML()
        );
    }

    public function testToResponse()
    {
        $resp = Mockery::mock(BaseXmlResponse::class, [])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('removeXmlFirstLine')
            ->andReturn('some string')
            ->getMock();

        $ret = $resp->toResponse();
        $this->assertInstanceOf(Response::class, $ret);
        $this->assertEquals(200, $ret->getStatusCode());
        $this->assertEquals('some string', $ret->getContent());
        $this->assertEquals('application/xml', $ret->headers->get('Content-Type'));
    }
}
