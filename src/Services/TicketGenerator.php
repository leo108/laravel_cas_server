<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/26
 * Time: 10:22
 */

namespace Leo108\CAS\Services;

use Illuminate\Support\Str;

class TicketGenerator
{
    /**
     * @param integer  $totalLength
     * @param string   $prefix
     * @param callable $checkFunc
     * @param integer  $maxRetry
     * @return string|false
     */
    public function generate($totalLength, $prefix, callable $checkFunc, $maxRetry)
    {
        $ticket = false;
        $flag   = false;
        for ($i = 0; $i < $maxRetry; $i++) {
            $ticket = $this->generateOne($totalLength, $prefix);
            if (call_user_func_array($checkFunc, [$ticket])) {
                $flag = true;
                break;
            }
        }

        if (!$flag) {
            return false;
        }

        return $ticket;
    }

    /**
     * @param integer $totalLength
     * @param string  $prefix
     * @return string
     */
    public function generateOne($totalLength, $prefix)
    {
        return $prefix.Str::random($totalLength - strlen($prefix));
    }
}
