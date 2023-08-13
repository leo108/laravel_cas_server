<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/20
 * Time: 12:40
 */

namespace Leo108\Cas\Contracts;

interface TicketLocker
{
    /**
     * @param  string  $key
     * @param  int  $timeout
     * @return bool
     */
    public function acquireLock(string $key, int $timeout): bool;

    /**
     * @param  string  $key
     * @return bool
     */
    public function releaseLock(string $key): bool;
}
