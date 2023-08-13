<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 15:16
 */

namespace Leo108\Cas\Responses;

use Symfony\Component\HttpFoundation\Response;

class BaseJsonResponse
{
    /**
     * @var array<string,mixed>
     */
    protected array $data;

    public function toResponse(): Response
    {
        return new Response(\Safe\json_encode($this->data), 200, ['Content-Type' => 'application/json']);
    }
}
