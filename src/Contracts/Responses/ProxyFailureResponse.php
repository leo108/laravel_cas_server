<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 17:48
 */

namespace Leo108\CAS\Contracts\Responses;

interface ProxyFailureResponse extends BaseResponse
{
    /**
     * @param string $code
     * @param string $description
     * @return $this
     */
    public function setFailure($code, $description);
}
