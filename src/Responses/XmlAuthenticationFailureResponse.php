<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:25
 */

namespace Leo108\CAS\Responses;

use Illuminate\Http\Response;
use Leo108\CAS\Contracts\Responses\AuthenticationFailureResponse;
use Leo108\CAS\Traits\XmlResponse;
use SimpleXMLElement;

class XmlAuthenticationFailureResponse implements AuthenticationFailureResponse
{
    use XmlResponse;
    /**
     * @var SimpleXMLElement
     */
    protected $node;

    /**
     * XmlAuthenticationFailureResponse constructor.
     */
    public function __construct()
    {
        $this->node = $this->getRootNode();
    }

    /**
     * @param string $code
     * @param string $description
     * @return $this
     */
    public function setFailure($code, $description)
    {
        $this->removeByXPath($this->node, 'cas:authenticationFailure');
        $authNode = $this->node->addChild('cas:authenticationFailure', $description);
        $authNode->addAttribute('code', $code);

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
        $authNodes = $this->node->xpath('cas:authenticationFailure');
        if (count($authNodes) < 1) {
            return $this->node->addChild('cas:authenticationFailure');
        }

        return $authNodes[0];
    }
}