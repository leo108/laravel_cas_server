<?php

namespace Leo108\Cas\Tests\Responses;

use Leo108\Cas\Responses\JsonProxySuccessResponse;
use Leo108\Cas\Tests\TestCase;

class JsonProxySuccessResponseTest extends TestCase
{
    public function testSetProxyTicket()
    {
        $resp = new JsonProxySuccessResponse();
        $resp->setProxyTicket('proxy ticket');
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['proxySuccess' => ['proxyTicket' => 'proxy ticket']]], $data);
        $resp->setProxyTicket('proxy ticket2');
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['proxySuccess' => ['proxyTicket' => 'proxy ticket2']]], $data);
    }

    protected function getData(JsonProxySuccessResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'data');

        return $property->getValue($resp);
    }
}
