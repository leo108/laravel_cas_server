<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 18:17
 */

namespace Leo108\Cas\Contracts\Responses;

interface ProxySuccessResponse extends BaseResponse
{
    /**
     * @param  string  $ticket
     * @return static
     */
    public function setProxyTicket(string $ticket): static;
}
