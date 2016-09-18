<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/18
 * Time: 11:28
 */

namespace Leo108\CAS\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class CasUserLogoutEvent extends Event
{
    use SerializesModels;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Authenticatable
     */
    protected $user;

    /**
     * CasUserLoginEvent constructor.
     * @param Request         $request
     * @param Authenticatable $user
     */
    public function __construct(Request $request, Authenticatable $user)
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
     * @return Authenticatable
     */
    public function getUser()
    {
        return $this->user;
    }
}
