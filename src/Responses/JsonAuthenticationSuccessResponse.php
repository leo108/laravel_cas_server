<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 15:57
 */

namespace Leo108\CAS\Responses;

use Leo108\CAS\Contracts\Responses\AuthenticationSuccessResponse;

class JsonAuthenticationSuccessResponse extends BaseJsonResponse implements AuthenticationSuccessResponse
{
    /**
     * JsonAuthenticationSuccessResponse constructor.
     */
    public function __construct()
    {
        $this->data = ['serviceResponse' => ['authenticationSuccess' => []]];
    }

    public function setUser($user)
    {
        $this->data['serviceResponse']['authenticationSuccess']['user'] = $user;

        return $this;
    }

    public function setProxies($proxies)
    {
        $this->data['serviceResponse']['authenticationSuccess']['proxies'] = $proxies;

        return $this;
    }

    public function setAttributes($attributes)
    {
        $this->data['serviceResponse']['authenticationSuccess']['attributes'] = $attributes;

        return $this;
    }

    public function setProxyGrantingTicket($ticket)
    {
        $this->data['serviceResponse']['authenticationSuccess']['proxyGrantingTicket'] = $ticket;

        return $this;
    }
}
