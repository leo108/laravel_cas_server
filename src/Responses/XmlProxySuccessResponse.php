<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 18:19
 */

namespace Leo108\Cas\Responses;

use Leo108\Cas\Contracts\Responses\ProxySuccessResponse;
use SimpleXMLElement;

class XmlProxySuccessResponse extends BaseXmlResponse implements ProxySuccessResponse
{
    /**
     * XmlProxySuccessResponse constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->node->addChild('cas:proxySuccess');
    }

    /**
     * @param  string  $ticket
     * @return static
     */
    public function setProxyTicket(string $ticket): static
    {
        $proxyNode = $this->getProxyNode();
        $this->removeByXPath($proxyNode, 'cas:proxyTicket');
        $proxyNode->addChild('cas:proxyTicket', $ticket);

        return $this;
    }

    public function getProxyNode(): SimpleXMLElement
    {
        $authNodes = $this->node->xpath('cas:proxySuccess');

        if ($authNodes === null || $authNodes === false || count($authNodes) < 1) {
            return $this->node->addChild('cas:proxySuccess');
        }

        return $authNodes[0];
    }
}
