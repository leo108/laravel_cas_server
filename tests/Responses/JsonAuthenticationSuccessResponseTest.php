<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 14:40
 */

namespace Leo108\CAS\Responses;

use TestCase;

class JsonAuthenticationSuccessResponseTest extends TestCase
{
    public function testSetUser()
    {
        $resp = new JsonAuthenticationSuccessResponse();
        $resp->setUser('test name');
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['user' => 'test name']]], $data);
        $resp->setUser('test name2');
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['user' => 'test name2']]], $data);
    }

    public function testSetProxyGrantingTicket()
    {
        $resp = new JsonAuthenticationSuccessResponse();
        $resp->setProxyGrantingTicket('ticket1');
        $data = $this->getData($resp);
        $this->assertEquals(
            ['serviceResponse' => ['authenticationSuccess' => ['proxyGrantingTicket' => 'ticket1']]],
            $data
        );
        $resp->setProxyGrantingTicket('ticket2');
        $data = $this->getData($resp);
        $this->assertEquals(
            ['serviceResponse' => ['authenticationSuccess' => ['proxyGrantingTicket' => 'ticket2']]],
            $data
        );
    }

    public function testSetProxies()
    {
        $resp     = new JsonAuthenticationSuccessResponse();
        $proxies1 = ['http://proxy1.com', 'http://proxy2.com'];
        $resp->setProxies($proxies1);
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['proxies' => $proxies1]]], $data);

        $proxies2 = ['http://proxy3.com', 'http://proxy4.com'];
        $resp->setProxies($proxies2);
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['proxies' => $proxies2]]], $data);
    }

    public function testSetAttributes()
    {
        $resp  = new JsonAuthenticationSuccessResponse();
        $attr1 = ['key1' => 'value1', 'key2' => 'value2'];
        $resp->setAttributes($attr1);
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['attributes' => $attr1]]], $data);

        $attr2 = ['key3' => 'value3', 'key4' => 'value4'];
        $resp->setAttributes($attr2);
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['attributes' => $attr2]]], $data);
    }

    public function testSetMultiValuedAttributes()
    {
        $resp  = new JsonAuthenticationSuccessResponse();
        $attr1 = ['key1' => ['value1', 'value2']];
        $resp->setAttributes($attr1);
        $data = $this->getData($resp);
        $this->assertEquals(['serviceResponse' => ['authenticationSuccess' => ['attributes' => $attr1]]], $data);
    }

    protected function getData(JsonAuthenticationSuccessResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'data');

        return $property->getValue($resp);
    }
}
