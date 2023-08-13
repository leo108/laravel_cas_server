<?php

namespace Leo108\Cas\Tests\Responses;

use Leo108\Cas\Responses\XmlProxySuccessResponse;
use Leo108\Cas\Tests\TestCase;

class XmlProxySuccessResponseTest extends TestCase
{
    public function testSetProxyTicket()
    {
        $resp = new XmlProxySuccessResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:proxyTicket', $content);
        $resp->setProxyTicket('proxy ticket1');
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxyTicket', $content);
        $this->assertStringContainsString('proxy ticket1', $content);

        $resp->setProxyTicket('proxy ticket2');
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxyTicket', $content);
        $this->assertStringNotContainsString('proxy ticket1', $content);
        $this->assertStringContainsString('proxy ticket2', $content);
    }

    protected function getXML(XmlProxySuccessResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'node');
        $node = $property->getValue($resp);

        return $node->asXML();
    }
}
