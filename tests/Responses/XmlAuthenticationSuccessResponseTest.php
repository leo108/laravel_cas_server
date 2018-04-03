<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/24
 * Time: 11:40
 */

namespace Leo108\CAS\Responses;

use Mockery;
use TestCase;

class XmlAuthenticationSuccessResponseTest extends TestCase
{
    public function testSetUser()
    {
        $resp    = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:user', $content);
        $resp->setUser('test');
        $content = $this->getXML($resp);
        $this->assertContains('cas:user', $content);
        $this->assertContains('test', $content);

        $resp->setUser('username_2');
        $content = $this->getXML($resp);
        $this->assertContains('cas:user', $content);
        $this->assertNotContains('test', $content);
        $this->assertContains('username_2', $content);
    }

    public function testSetProxies()
    {
        $resp    = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:proxies', $content);
        $this->assertNotContains('cas:proxy', $content);

        $resp->setProxies(['http://proxy1.com']);
        $content = $this->getXML($resp);
        $this->assertContains('cas:proxies', $content);
        $this->assertContains('cas:proxy', $content);
        $this->assertContains('http://proxy1.com', $content);

        $resp->setProxies(['http://proxy2.com', 'http://proxy3.com']);
        $content = $this->getXML($resp);
        $this->assertContains('cas:proxies', $content);
        $this->assertContains('cas:proxy', $content);
        $this->assertNotContains('http://proxy1.com', $content);
        $this->assertContains('http://proxy2.com', $content);
        $this->assertContains('http://proxy3.com', $content);
    }

    public function testSetAttributes()
    {
        $resp    = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:attributes', $content);

        $resp->setAttributes(['key1' => 'value1']);
        $content = $this->getXML($resp);
        $this->assertContains('cas:attributes', $content);
        $this->assertContains('cas:key1', $content);

        $resp->setAttributes(['key2' => 'value2', 'key3' => 'value3']);
        $content = $this->getXML($resp);
        $this->assertContains('cas:attributes', $content);
        $this->assertNotContains('cas:key1', $content);
        $this->assertContains('cas:key2', $content);
        $this->assertContains('cas:key3', $content);
    }

    public function testSetMultiValuedAttributes()
    {
        $resp    = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:attributes', $content);

        $resp->setAttributes(['key1' => ['value1', 'value2']]);
        $content = $this->getXML($resp);
        $this->assertContains('cas:attributes', $content);
        $this->assertContains('<cas:key1>value1</cas:key1>', $content);
        $this->assertContains('<cas:key1>value2</cas:key1>', $content);
    }

    public function testSetProxyGrantingTicket()
    {
        $resp    = new XmlAuthenticationSuccessResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:proxyGrantingTicket', $content);

        $ticket1 = 'ticket1';
        $ticket2 = 'ticket2';
        $resp->setProxyGrantingTicket($ticket1);
        $content = $this->getXML($resp);
        $this->assertContains('cas:proxyGrantingTicket', $content);
        $this->assertContains($ticket1, $content);

        $resp->setProxyGrantingTicket($ticket2);
        $content = $this->getXML($resp);
        $this->assertContains('cas:proxyGrantingTicket', $content);
        $this->assertNotContains($ticket1, $content);
        $this->assertContains($ticket2, $content);
    }

    protected function getXML(XmlAuthenticationSuccessResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'node');
        $node     = $property->getValue($resp);

        return $node->asXML();
    }
}
