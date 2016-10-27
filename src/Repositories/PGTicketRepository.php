<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 16:34
 */

namespace Leo108\CAS\Repositories;

use Carbon\Carbon;
use Leo108\CAS\Contracts\Models\UserModel;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\PGTicket;
use Leo108\CAS\Services\TicketGenerator;

class PGTicketRepository
{
    /**
     * @var PGTicket
     */
    protected $pgTicket;
    /**
     * @var ServiceRepository
     */
    protected $serviceRepository;

    /**
     * @var TicketGenerator
     */
    protected $ticketGenerator;

    /**
     * PGTicketRepository constructor.
     * @param PGTicket          $pgTicket
     * @param ServiceRepository $serviceRepository
     * @param TicketGenerator   $ticketGenerator
     */
    public function __construct(
        PGTicket $pgTicket,
        ServiceRepository $serviceRepository,
        TicketGenerator $ticketGenerator
    ) {
        $this->pgTicket          = $pgTicket;
        $this->serviceRepository = $serviceRepository;
        $this->ticketGenerator   = $ticketGenerator;
    }

    /**
     * @param string $ticket
     * @param bool   $checkExpired
     * @return null|PGTicket
     */
    public function getByTicket($ticket, $checkExpired = true)
    {
        $record = $this->pgTicket->where('ticket', $ticket)->first();
        if (!$record) {
            return null;
        }

        return ($checkExpired && $record->isExpired()) ? null : $record;
    }

    /**
     * @param UserModel $user
     * @param string    $pgtUrl
     * @param array     $proxies
     * @return PGTicket
     * @throws CasException
     */
    public function applyTicket(UserModel $user, $pgtUrl, $proxies = [])
    {
        $service = $this->serviceRepository->getServiceByUrl($pgtUrl);
        if (!$service || !$service->allow_proxy) {
            throw new CasException(CasException::UNAUTHORIZED_SERVICE_PROXY);
        }

        $ticket = $this->getAvailableTicket(config('cas.ticket_len', 32));
        if ($ticket === false) {
            throw new CasException(CasException::INTERNAL_ERROR, 'apply proxy-granting ticket failed');
        }
        $record = $this->pgTicket->newInstance(
            [
                'ticket'     => $ticket,
                'expire_at'  => new Carbon(sprintf('+%dsec', config('cas.ticket_expire', 300))),
                'created_at' => new Carbon(),
                'pgt_url'    => $pgtUrl,
                'proxies'    => $proxies,
            ]
        );
        $record->user()->associate($user->getEloquentModel());
        $record->service()->associate($service);
        $record->save();

        return $record;
    }

    /**
     * @param string $totalLength
     * @return string|false
     */
    protected function getAvailableTicket($totalLength)
    {
        return $this->ticketGenerator->generate(
            $totalLength,
            'PGT-',
            function ($ticket) {
                return is_null($this->getByTicket($ticket, false));
            },
            10
        );
    }
}
