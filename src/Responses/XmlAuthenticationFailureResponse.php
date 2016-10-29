<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:25
 */

namespace Leo108\CAS\Responses;

use Leo108\CAS\Contracts\Responses\AuthenticationFailureResponse;

class XmlAuthenticationFailureResponse extends BaseXmlResponse implements AuthenticationFailureResponse
{
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
}
