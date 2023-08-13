<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 17:48
 */

namespace Leo108\Cas\Responses;

use Leo108\Cas\Contracts\Responses\ProxyFailureResponse;

class XmlProxyFailureResponse extends BaseXmlResponse implements ProxyFailureResponse
{
    /**
     * @param  string  $code
     * @param  string  $description
     * @return static
     */
    public function setFailure(string $code, string $description): static
    {
        $this->removeByXPath($this->node, 'cas:proxyFailure');
        $authNode = $this->node->addChild('cas:proxyFailure', $description);
        $authNode->addAttribute('code', $code);

        return $this;
    }
}
