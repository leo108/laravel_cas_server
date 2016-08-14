<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/3
 * Time: 23:07
 */

namespace Leo108\CAS\Response;

use Illuminate\Http\Response;

class JsonResponse
{
    public static function error($msg, $code = -1, $data = [])
    {
        return new Response(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    public static function success($data = [], $msg = '')
    {
        return new Response(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }
}
