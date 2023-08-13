<?php

namespace Leo108\Cas\Tests\Responses;

use Leo108\Cas\Responses\BaseXmlResponse;
use Leo108\Cas\Tests\TestCase;
use Mockery;
use Safe\Exceptions\JsonException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;

class BaseXmlResponseTest extends TestCase
{
    protected BaseXmlResponse $testObj;

    public function setUp(): void
    {
        parent::setUp();
        $this->testObj = new BaseXmlResponse();
    }

    public function testStringify()
    {
        $method = self::getNonPublicMethod($this->testObj, 'stringify');

        $resource = fopen(__FILE__, 'a');
        $this->assertEquals('string', $method->invoke($this->testObj, 'string'));
        $this->assertEquals(json_encode([1, 2, 3]), $method->invoke($this->testObj, [1, 2, 3]));
        $this->assertEquals(json_encode(['key' => 'value']), $method->invoke($this->testObj, ['key' => 'value']));
        $this->assertEquals(
            json_encode(['key' => 'value']),
            $method->invoke($this->testObj, (object) ['key' => 'value'])
        );

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Type is not supported');
        $method->invoke($this->testObj, $resource);
    }

    public function testRemoveXmlFirstLine()
    {
        $xml = new SimpleXMLElement('<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>');
        $method = self::getNonPublicMethod($this->testObj, 'removeXmlFirstLine');
        $this->assertStringNotContainsString('<?xml version="1.0"?>', $method->invoke($this->testObj, $xml->asXML()));

        $normalStr = 'some string';
        $this->assertEquals($normalStr, $method->invoke($this->testObj, $normalStr));
    }

    public function testRemoveByXPath()
    {
        $xml = new SimpleXMLElement('<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>');
        $xml->addChild('cas:tag', '123');
        $this->assertStringContainsString('cas:tag', $xml->asXML());
        $this->assertStringContainsString('123', $xml->asXML());
        $method = self::getNonPublicMethod($this->testObj, 'removeByXPath');
        $method->invoke($this->testObj, $xml, 'cas:tag');
        $this->assertStringNotContainsString('cas:tag', $xml->asXML());
        $this->assertStringNotContainsString('123', $xml->asXML());
    }

    public function testGetRootNode()
    {
        $method = self::getNonPublicMethod($this->testObj, 'getRootNode');
        $this->assertStringContainsString(
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
