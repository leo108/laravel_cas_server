<?php

namespace Leo108\CAS\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class CasUserLoginEvent extends Event
{
    use SerializesModels;

    protected $request;
    protected $user;

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
