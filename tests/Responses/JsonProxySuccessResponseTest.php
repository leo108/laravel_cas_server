<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/27
 * Time: 07:24
 */

namespace Leo108\CAS\Responses;


use TestCase;

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
