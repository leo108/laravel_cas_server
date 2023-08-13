<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:19
 */

namespace Leo108\Cas\Contracts\Responses;

interface AuthenticationFailureResponse extends BaseResponse
{
    /**
     * @param  string  $code
     * @param  string  $description
     * @return static
     */
    public function setFailure(string $code, string $description): static;
}
