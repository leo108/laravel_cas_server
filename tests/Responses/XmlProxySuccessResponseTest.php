<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/27
 * Time: 07:24
 */

namespace Leo108\CAS\Responses;


use TestCase;

class XmlProxySuccessResponseTest extends TestCase
{
    public function testSetProxyTicket()
    {
        $resp    = new XmlProxySuccessResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:proxyTicket', $content);
        $resp->setProxyTicket('proxy ticket1');
        $content = $this->getXML($resp);
        $this->assertContains('cas:proxyTicket', $content);
        $this->assertContains('proxy ticket1', $content);

        $resp->setProxyTicket('proxy ticket2');
        $content = $this->getXML($resp);
        $this->assertContains('cas:proxyTicket', $content);
        $this->assertNotContains('proxy ticket1', $content);
        $this->assertContains('proxy ticket2', $content);
    }

    protected function getXML(XmlProxySuccessResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'node');
        $node     = $property->getValue($resp);

        return $node->asXML();
    }
}
