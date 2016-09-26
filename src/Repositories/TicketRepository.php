<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 20:19
 */

namespace Leo108\CAS\Repositories;

use Carbon\Carbon;
use Leo108\CAS\Contracts\Models\UserModel;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\Ticket;

class TicketRepository
{
    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var ServiceRepository
     */
    protected $serviceRepository;

    /**
     * TicketRepository constructor.
     * @param Ticket            $ticket
     * @param ServiceRepository $serviceRepository
     */
    public function __construct(Ticket $ticket, ServiceRepository $serviceRepository)
    {
        $this->ticket            = $ticket;
        $this->serviceRepository = $serviceRepository;
    }


    /**
     * @param UserModel $user
     * @param string    $serviceUrl
     * @throws CasException
     * @return \Leo108\CAS\Models\Ticket
     */
    public function applyTicket(UserModel $user, $serviceUrl)
    {
        $service = $this->serviceRepository->getServiceByUrl($serviceUrl);
        if (!$service) {
            throw new CasException(CasException::INVALID_SERVICE);
        }
        $ticket = $this->getAvailableTicket(config('cas.ticket_len', 32));
        if (!$ticket) {
            throw new CasException(CasException::INTERNAL_ERROR, 'apply ticket failed');
        }
        $record = $this->ticket->newInstance(
            [
                'ticket'      => $ticket,
                'expire_at'   => new Carbon(sprintf('+%dsec', config('cas.ticket_expire', 300))),
                'created_at'  => new Carbon(),
                'service_url' => $serviceUrl,
            ]
        );
        $record->user()->associate($user->getEloquentModel());
        $record->service()->associate($service);
        $record->save();

        return $record;
    }

    /**
     * @param string $ticket
     * @param bool   $checkExpired
     * @return bool|Ticket
     */
    public function getByTicket($ticket, $checkExpired = true)
    {
        $record = $this->ticket->where('ticket', $ticket)->first();
        if (!$record) {
            return false;
        }

        return ($checkExpired && $record->isExpired()) ? false : $record;
    }

    /**
     * @param Ticket $ticket
     * @return bool|null
     */
    public function invalidTicket(Ticket $ticket)
    {
        return $ticket->delete();
    }

    /**
     * @param $totalLength
     * @return bool|string
     */
    protected function getAvailableTicket($totalLength)
    {
        $prefix = 'ST-';
        $ticket = false;
        $flag   = false;
        for ($i = 0; $i < 10; $i++) {
            $str    = bin2hex(random_bytes($totalLength));
            $ticket = $prefix.substr($str, 0, $totalLength - strlen($prefix));
            if (!$this->getByTicket($ticket, false)) {
                $flag = true;
                break;
            }
        }

        if (!$flag) {
            return false;
        }

        return $ticket;
    }
}