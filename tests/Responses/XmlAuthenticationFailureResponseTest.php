<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 13:29
 */

namespace Leo108\CAS\Responses;

use TestCase;

class XmlAuthenticationFailureResponseTest extends TestCase
{
    public function testSetFailure()
    {
        $resp    = new XmlAuthenticationFailureResponse();
        $content = $this->getXML($resp);
        $this->assertNotContains('cas:authenticationFailure', $content);
        $resp->setFailure('code1', 'desc1');
        $content = $this->getXML($resp);
        $this->assertContains('cas:authenticationFailure', $content);
        $this->assertContains('code1', $content);
        $this->assertContains('desc1', $content);
        $resp->setFailure('code2', 'desc2');
        $content = $this->getXML($resp);
        $this->assertContains('cas:authenticationFailure', $content);
        $this->assertNotContains('code1', $content);
        $this->assertContains('code2', $content);
        $this->assertNotContains('desc1', $content);
        $this->assertContains('desc2', $content);
    }

    protected function getXML(XmlAuthenticationFailureResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'node');
        $node     = $property->getValue($resp);

        return $node->asXML();
    }
}
