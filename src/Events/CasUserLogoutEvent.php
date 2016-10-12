<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/18
 * Time: 11:28
 */

namespace Leo108\CAS\Events;

use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Leo108\CAS\Contracts\Models\UserModel;

class CasUserLogoutEvent extends Event
{
    use SerializesModels;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var UserModel
     */
    protected $user;

    /**
     * CasUserLoginEvent constructor.
     * @param Request   $request
     * @param UserModel $user
     */
    public function __construct(Request $request, UserModel $user)
    {
        $this->request = $request;
        $this->user    = $user;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return UserModel
     */
    public function getUser()
    {
        return $this->user;
    }
}
