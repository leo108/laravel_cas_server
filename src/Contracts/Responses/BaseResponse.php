<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:20
 */

namespace Leo108\CAS\Contracts\Responses;

use Symfony\Component\HttpFoundation\Response;

interface BaseResponse
{
    /**
     * @return Response
     */
    public function toResponse();
}
