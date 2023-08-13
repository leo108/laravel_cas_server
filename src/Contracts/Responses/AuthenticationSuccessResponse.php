<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 15:54
 */

namespace Leo108\Cas\Contracts\Responses;

interface AuthenticationSuccessResponse extends BaseResponse
{
    /**
     * @param  string  $user
     * @return static
     */
    public function setUser(string $user): static;

    /**
     * @param  list<string>  $proxies
     * @return static
     */
    public function setProxies(array $proxies): static;

    /**
     * @param  array<string,mixed>  $attributes
     * @return static
     */
    public function setAttributes(array $attributes): static;

    /**
     * @param  string  $ticket
     * @return static
     */
    public function setProxyGrantingTicket(string $ticket): static;
}
