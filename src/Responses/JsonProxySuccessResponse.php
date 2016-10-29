<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 18:19
 */

namespace Leo108\CAS\Responses;

use Leo108\CAS\Contracts\Responses\ProxySuccessResponse;

class JsonProxySuccessResponse extends BaseJsonResponse implements ProxySuccessResponse
{
    /**
     * JsonProxySuccessResponse constructor.
     */
    public function __construct()
    {
        $this->data = ['serviceResponse' => ['proxySuccess' => []]];
    }

    public function setProxyTicket($ticket)
    {
        $this->data['serviceResponse']['proxySuccess']['proxyTicket'] = $ticket;
    }
}
