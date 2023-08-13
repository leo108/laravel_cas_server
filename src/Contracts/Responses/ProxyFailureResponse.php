<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 17:48
 */

namespace Leo108\Cas\Contracts\Responses;

interface ProxyFailureResponse extends BaseResponse
{
    /**
     * @param  string  $code
     * @param  string  $description
     * @return static
     */
    public function setFailure(string $code, string $description): static;
}
