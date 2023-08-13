<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:25
 */

namespace Leo108\Cas\Responses;

use Leo108\Cas\Contracts\Responses\AuthenticationFailureResponse;

class XmlAuthenticationFailureResponse extends BaseXmlResponse implements AuthenticationFailureResponse
{
    /**
     * @param  string  $code
     * @param  string  $description
     * @return static
     */
    public function setFailure(string $code, string $description): static
    {
        $this->removeByXPath($this->node, 'cas:authenticationFailure');
        $authNode = $this->node->addChild('cas:authenticationFailure', $description);
        $authNode->addAttribute('code', $code);

        return $this;
    }
}
