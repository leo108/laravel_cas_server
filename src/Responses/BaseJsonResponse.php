<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 15:16
 */

namespace Leo108\CAS\Responses;

use Symfony\Component\HttpFoundation\Response;

class BaseJsonResponse
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @return Response
     */
    public function toResponse()
    {
        return new Response(json_encode($this->data), 200, ['Content-Type' => 'application/json']);
    }
}
