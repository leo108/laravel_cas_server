<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:23
 */

namespace Leo108\Cas\Responses;

use Leo108\Cas\Contracts\Responses\AuthenticationFailureResponse;

class JsonAuthenticationFailureResponse extends BaseJsonResponse implements AuthenticationFailureResponse
{
    public function __construct()
    {
        $this->data = ['serviceResponse' => ['authenticationFailure' => []]];
    }

    /**
     * @return static
     */
    public function setFailure(string $code, string $description): static
    {
        $this->data['serviceResponse']['authenticationFailure']['code'] = $code;
        $this->data['serviceResponse']['authenticationFailure']['description'] = $description;

        return $this;
    }
}
