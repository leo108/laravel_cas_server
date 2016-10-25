<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 14:40
 */

namespace Leo108\CAS\Responses;

use TestCase;

class JsonAuthenticationFailureResponseTest extends TestCase
{
    public function testSetFailure()
    {
        $resp = new JsonAuthenticationFailureResponse();
        $resp->setFailure('code1', 'desc1');
        $data = $this->getData($resp);
        $this->assertEquals(
            ['serviceResponse' => ['authenticationFailure' => ['code' => 'code1', 'description' => 'desc1']]],
            $data
        );

        $resp->setFailure('code2', 'desc2');
        $data = $this->getData($resp);
        $this->assertEquals(
            ['serviceResponse' => ['authenticationFailure' => ['code' => 'code2', 'description' => 'desc2']]],
            $data
        );
    }

    protected function getData(JsonAuthenticationFailureResponse $resp)
    {
        $property = self::getNonPublicProperty($resp, 'data');

        return $property->getValue($resp);
    }
}
