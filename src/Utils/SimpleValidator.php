<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/8
 * Time: 12:46
 */

namespace Leo108\CAS\Utils;

use Illuminate\Validation\ValidationException;

class SimpleValidator
{
    public static function validate($data, $rule, $attr = [], $throws = true)
    {
        $validator = \Validator::make($data, $rule, [], $attr);
        if (!$validator->fails()) {
            return [];
        }

        if ($throws) {
            throw new ValidationException($validator);
        }

        return $validator->errors();
    }
}
