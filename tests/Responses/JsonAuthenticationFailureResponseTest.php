<?php

namespace Leo108\Cas\Tests\Responses;

use Leo108\Cas\Responses\JsonAuthenticationFailureResponse;
use Leo108\Cas\Tests\TestCase;

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
