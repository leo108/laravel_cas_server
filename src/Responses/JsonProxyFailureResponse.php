<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 17:47
 */

namespace Leo108\Cas\Responses;

use Leo108\Cas\Contracts\Responses\ProxyFailureResponse;

class JsonProxyFailureResponse extends BaseJsonResponse implements ProxyFailureResponse
{
    /**
     * JsonProxyFailureResponse constructor.
     */
    public function __construct()
    {
        $this->data = ['serviceResponse' => ['proxyFailure' => []]];
    }

    public function setFailure(string $code, string $description): static
    {
        $this->data['serviceResponse']['proxyFailure']['code'] = $code;
        $this->data['serviceResponse']['proxyFailure']['description'] = $description;

        return $this;
    }
}
