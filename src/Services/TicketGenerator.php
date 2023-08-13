<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/26
 * Time: 10:22
 */

namespace Leo108\Cas\Services;

use Illuminate\Support\Str;

class TicketGenerator
{
    /**
     * @param  int  $totalLength
     * @param  string  $prefix
     * @param  callable(string):bool  $checkFunc
     * @param  int  $maxRetry
     * @return string|false
     */
    public function generate(int $totalLength, string $prefix, callable $checkFunc, int $maxRetry)
    {
        $ticket = false;
        $flag = false;
        for ($i = 0; $i < $maxRetry; $i++) {
            $ticket = $this->generateOne($totalLength, $prefix);

            if (call_user_func($checkFunc, $ticket)) {
                $flag = true;
                break;
            }
        }

        if (! $flag) {
            return false;
        }

        return $ticket;
    }

    /**
     * @param  int  $totalLength
     * @param  string  $prefix
     * @return string
     */
    public function generateOne(int $totalLength, string $prefix): string
    {
        return $prefix.Str::random($totalLength - strlen($prefix));
    }
}
