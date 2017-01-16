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
use Leo108\CAS\Services\TicketGenerator;

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
     * @var TicketGenerator
     */
    protected $ticketGenerator;

    /**
     * TicketRepository constructor.
     * @param Ticket            $ticket
     * @param ServiceRepository $serviceRepository
     * @param TicketGenerator   $ticketGenerator
     */
    public function __construct(Ticket $ticket, ServiceRepository $serviceRepository, TicketGenerator $ticketGenerator)
    {
        $this->ticket            = $ticket;
        $this->serviceRepository = $serviceRepository;
        $this->ticketGenerator   = $ticketGenerator;
    }

    /**
     * @param UserModel $user
     * @param string    $serviceUrl
     * @param array     $proxies
     * @throws CasException
     * @return Ticket
     */
    public function applyTicket(UserModel $user, $serviceUrl, $proxies = [])
    {
        $service = $this->serviceRepository->getServiceByUrl($serviceUrl);
        if (!$service) {
            throw new CasException(CasException::INVALID_SERVICE);
        }
        $ticket = $this->getAvailableTicket(config('cas.ticket_len', 32), empty($proxies) ? 'ST-' : 'PT-');
        if ($ticket === false) {
            throw new CasException(CasException::INTERNAL_ERROR, 'apply ticket failed');
        }
        $record = $this->ticket->newInstance(
            [
                'ticket'      => $ticket,
                'expire_at'   => new Carbon(sprintf('+%dsec', config('cas.ticket_expire', 300))),
                'created_at'  => new Carbon(),
                'service_url' => $serviceUrl,
                'proxies'     => $proxies,
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
     * @return null|Ticket
     */
    public function getByTicket($ticket, $checkExpired = true)
    {
        $record = $this->ticket->where('ticket', $ticket)->first();
        if (!$record) {
            return null;
        }

        return ($checkExpired && $record->isExpired()) ? null : $record;
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
     * @param integer $totalLength
     * @param string  $prefix
     * @return string|false
     */
    protected function getAvailableTicket($totalLength, $prefix)
    {
        return $this->ticketGenerator->generate(
            $totalLength,
            $prefix,
            function ($ticket) {
                return is_null($this->getByTicket($ticket, false));
            },
            10
        );
    }
}
