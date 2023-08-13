<?php

namespace Leo108\Cas\Tests\Responses;

use Leo108\Cas\Responses\XmlProxyFailureResponse;
use Leo108\Cas\Tests\TestCase;

class XmlProxyFailureResponseTest extends TestCase
{
    public function testSetFailure()
    {
        $resp = new XmlProxyFailureResponse();
        $content = $this->getXML($resp);
        $this->assertStringNotContainsString('cas:proxyFailure', $content);
        $resp->setFailure('code1', 'desc1');
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxyFailure', $content);
        $this->assertStringContainsString('code1', $content);
        $this->assertStringContainsString('desc1', $content);
        $resp->setFailure('code2', 'desc2');
        $content = $this->getXML($resp);
        $this->assertStringContainsString('cas:proxyFailure', $content);
        $this->assertStringNotContainsString('code1', $content);
        $this->assertStringContainsString('code2', $content);
        $this->assertStringNotContainsString('desc1', $content);
        $this->assertStringContainsString('desc2', $content);
    }

    protected function getXML(XmlProxyFailureResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'node');
        $node = $property->getValue($resp);

        return $node->asXML();
    }
}
