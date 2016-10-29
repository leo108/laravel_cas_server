<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 15:17
 */

namespace Leo108\CAS\Responses;

use Symfony\Component\HttpFoundation\Response;
use TestCase;

class BaseJsonResponseTest extends TestCase
{
    public function testToResponse()
    {
        $data     = ['something' => 'test'];
        $resp     = new BaseJsonResponse();
        $property = self::getNonPublicProperty($resp, 'data');
        $property->setValue($resp, $data);
        $ret = $resp->toResponse();
        $this->assertInstanceOf(Response::class, $ret);
        $this->assertEquals(200, $ret->getStatusCode());
        $this->assertEquals(json_encode($data), $ret->getContent());
        $this->assertEquals('application/json', $ret->headers->get('Content-Type'));
    }
}
