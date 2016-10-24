<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:01
 */

namespace Leo108\CAS\Responses;

use Illuminate\Http\Response;
use Leo108\CAS\Contracts\Responses\AuthenticationSuccessResponse;
use Leo108\CAS\Traits\XmlResponse;
use SimpleXMLElement;

class XmlAuthenticationSuccessResponse implements AuthenticationSuccessResponse
{
    use XmlResponse;

    /**
     * @var SimpleXMLElement
     */
    protected $node;

    /**
     * XmlAuthenticationSuccessResponse constructor.
     */
    public function __construct()
    {
        $this->node = $this->getRootNode();
        $this->node->addChild('cas:authenticationSuccess');
    }

    public function setUser($user)
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:user');
        $authNode->addChild('cas:user', $user);

        return $this;
    }

    public function setProxies($proxies)
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:proxies');
        $proxiesNode = $authNode->addChild('cas:proxies');
        foreach ($proxies as $proxy) {
            $proxiesNode->addChild('cas:proxy', $proxy);
        }

        return $this;
    }

    public function setAttributes($attributes)
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:attributes');
        $attributesNode = $authNode->addChild('cas:attributes');
        foreach ($attributes as $key => $value) {
            $str = $this->stringify($value);
            if (is_string($str)) {
                $attributesNode->addChild('cas:'.$key, $str);
            }
        }

        return $this;
    }

    public function setProxyGrantingTicket($ticket)
    {
        $authNode = $this->getAuthNode();
        $this->removeByXPath($authNode, 'cas:proxyGrantingTicket');
        $authNode->addChild('cas:proxyGrantingTicket', $ticket);

        return $this;
    }

    /**
     * @return Response
     */
    public function toResponse()
    {
        $content = $this->removeXmlFirstLine($this->node->asXML());

        return new Response($content, 200, array('Content-Type' => 'application/xml'));
    }

    /**
     * @return SimpleXMLElement
     */
    protected function getAuthNode()
    {
        $authNodes = $this->node->xpath('cas:authenticationSuccess');
        if (count($authNodes) < 1) {
            return $this->node->addChild('cas:authenticationSuccess');
        }

        return $authNodes[0];
    }
}
