<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:23
 */

namespace Leo108\CAS\Responses;

use Illuminate\Http\Response;
use Leo108\CAS\Contracts\Responses\AuthenticationFailureResponse;

class JsonAuthenticationFailureResponse implements AuthenticationFailureResponse
{
    /**
     * @var array
     */
    protected $data;

    /**
     * JsonAuthenticationFailureResponse constructor.
     */
    public function __construct()
    {
        $this->data = ['serviceResponse' => ['authenticationFailure' => []]];
    }

    /**
     * @param string $code
     * @param string $description
     * @return $this
     */
    public function setFailure($code, $description)
    {
        $this->data['serviceResponse']['authenticationFailure']['code']        = $code;
        $this->data['serviceResponse']['authenticationFailure']['description'] = $description;

        return $this;
    }

    /**
     * @return Response
     */
    public function toResponse()
    {
        return new Response($this->data);
    }
}
