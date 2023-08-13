<?php

namespace Leo108\Cas\Tests\Responses;

use Leo108\Cas\Responses\XmlAuthenticationSuccessResponse;
use Leo108\Cas\Tests\TestCase;

class XmlAuthenticationSuccessResponseTest extends TestCase
{
    public function testSetUser()
    {
        $resp = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:user', $content);
        $resp->setUser('test');
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:user', $content);
        $this->assertStringContainsString('test', $content);

        $resp->setUser('username_2');
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:user', $content);
        $this->assertStringNotContainsString('test', $content);
        $this->assertStringContainsString('username_2', $content);
    }

    public function testSetProxies()
    {
        $resp = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:proxies', $content);
        $this->assertStringNotContainsString('cas:proxy', $content);

        $resp->setProxies(['http://proxy1.com']);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxies', $content);
        $this->assertStringContainsString('cas:proxy', $content);
        $this->assertStringContainsString('http://proxy1.com', $content);

        $resp->setProxies(['http://proxy2.com', 'http://proxy3.com']);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxies', $content);
        $this->assertStringContainsString('cas:proxy', $content);
        $this->assertStringNotContainsString('http://proxy1.com', $content);
        $this->assertStringContainsString('http://proxy2.com', $content);
        $this->assertStringContainsString('http://proxy3.com', $content);
    }

    public function testSetAttributes()
    {
        $resp = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:attributes', $content);

        $resp->setAttributes(['key1' => 'value1']);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:attributes', $content);
        $this->assertStringContainsString('cas:key1', $content);

        $resp->setAttributes(['key2' => 'value2', 'key3' => 'value3']);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:attributes', $content);
        $this->assertStringNotContainsString('cas:key1', $content);
        $this->assertStringContainsString('cas:key2', $content);
        $this->assertStringContainsString('cas:key3', $content);
    }

    public function testSetMultiValuedAttributes()
    {
        $resp = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:attributes', $content);

        $resp->setAttributes(['key1' => ['value1', 'value2']]);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:attributes', $content);
        $this->assertStringContainsString('<cas:key1>value1</cas:key1>', $content);
        $this->assertStringContainsString('<cas:key1>value2</cas:key1>', $content);
    }

    public function testSetProxyGrantingTicket()
    {
        $resp = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:proxyGrantingTicket', $content);

        $ticket1 = 'ticket1';
        $ticket2 = 'ticket2';
        $resp->setProxyGrantingTicket($ticket1);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxyGrantingTicket', $content);
        $this->assertStringContainsString($ticket1, $content);

        $resp->setProxyGrantingTicket($ticket2);
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxyGrantingTicket', $content);
        $this->assertStringNotContainsString($ticket1, $content);
        $this->assertStringContainsString($ticket2, $content);
    }

    protected function getXML(XmlAuthenticationSuccessResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'node');
        $node = $property->getValue($resp);

        return $node->asXML();
    }
}
