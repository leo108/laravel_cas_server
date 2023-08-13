<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/18
 * Time: 11:28
 */

namespace Leo108\Cas\Events;

use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Leo108\Cas\Contracts\Models\UserModel;

class CasUserLogoutEvent extends Event
{
    use SerializesModels;

    public function __construct(protected Request $request, protected UserModel $user)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getUser(): UserModel
    {
        return $this->user;
    }
}
