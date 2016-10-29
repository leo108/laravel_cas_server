<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 17:47
 */

namespace Leo108\CAS\Responses;

use Leo108\CAS\Contracts\Responses\ProxyFailureResponse;

class JsonProxyFailureResponse extends BaseJsonResponse implements ProxyFailureResponse
{
    /**
     * JsonProxyFailureResponse constructor.
     */
    public function __construct()
    {
        $this->data = ['serviceResponse' => ['proxyFailure' => []]];
    }

    /**
     * @param string $code
     * @param string $description
     * @return $this
     */
    public function setFailure($code, $description)
    {
        $this->data['serviceResponse']['proxyFailure']['code']        = $code;
        $this->data['serviceResponse']['proxyFailure']['description'] = $description;

        return $this;
    }
}
