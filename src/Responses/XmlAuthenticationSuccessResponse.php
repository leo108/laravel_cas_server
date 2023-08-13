<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:01
 */

namespace Leo108\Cas\Responses;

use Leo108\Cas\Contracts\Responses\AuthenticationSuccessResponse;
use SimpleXMLElement;

class XmlAuthenticationSuccessResponse extends BaseXmlResponse implements AuthenticationSuccessResponse
{
    /**
     * XmlAuthenticationSuccessResponse constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->node->addChild('cas:authenticationSuccess');
    }

    public function setUser(string $user): static
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:user');
        $authNode->addChild('cas:user', $user);

        return $this;
    }

    public function setProxies(array $proxies): static
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:proxies');
        $proxiesNode = $authNode->addChild('cas:proxies');
        foreach ($proxies as $proxy) {
            $proxiesNode->addChild('cas:proxy', $proxy);
        }

        return $this;
    }

    public function setAttributes(array $attributes): static
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:attributes');
        $attributesNode = $authNode->addChild('cas:attributes');
        foreach ($attributes as $key => $value) {
            $valueArr = (array) $value;

            foreach ($valueArr as $v) {
                $attributesNode->addChild('cas:'.$key, $this->stringify($v));
            }
        }

        return $this;
    }

    public function setProxyGrantingTicket(string $ticket): static
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:proxyGrantingTicket');
        $authNode->addChild('cas:proxyGrantingTicket', $ticket);

        return $this;
    }

    protected function getAuthNode(): SimpleXMLElement
    {
        $authNodes = $this->node->xpath('cas:authenticationSuccess');

        if ($authNodes === null || $authNodes === false || count($authNodes) < 1) {
            return $this->node->addChild('cas:authenticationSuccess');
        }

        return $authNodes[0];
    }
}
