<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 16:34
 */

namespace Leo108\Cas\Repositories;

use Leo108\Cas\Contracts\Models\UserModel;
use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\PGTicket;
use Leo108\Cas\Services\CasConfig;
use Leo108\Cas\Services\TicketGenerator;

class PGTicketRepository
{
    protected PGTicket $pgTicket;
    protected ServiceRepository $serviceRepository;
    protected TicketGenerator $ticketGenerator;
    protected CasConfig $casConfig;

    public function __construct(
        PGTicket $pgTicket,
        ServiceRepository $serviceRepository,
        TicketGenerator $ticketGenerator,
        CasConfig $casConfig
    ) {
        $this->pgTicket = $pgTicket;
        $this->serviceRepository = $serviceRepository;
        $this->ticketGenerator = $ticketGenerator;
        $this->casConfig = $casConfig;
    }

    /**
     * @param  string  $ticket
     * @param  bool  $checkExpired
     * @return null|PGTicket
     */
    public function getByTicket(string $ticket, bool $checkExpired = true): ?PGTicket
    {
        $record = $this->pgTicket->newQuery()->where('ticket', $ticket)->first();
        if ($record === null) {
            return null;
        }

        return ($checkExpired && $record->isExpired()) ? null : $record;
    }

    public function invalidTicketByUser(UserModel $user): void
    {
        $this->pgTicket->where('user_id', $user->getEloquentModel()->getKey())->delete();
    }

    /**
     * @param  \Leo108\Cas\Contracts\Models\UserModel  $user
     * @param  string  $pgtUrl
     * @param  list<string>  $proxies
     * @return \Leo108\Cas\Models\PGTicket
     *
     * @throws \Leo108\Cas\Exceptions\CasException
     */
    public function applyTicket(UserModel $user, string $pgtUrl, array $proxies = []): PGTicket
    {
        $service = $this->serviceRepository->getServiceByUrl($pgtUrl);
        if ($service === null || ! $service->allow_proxy) {
            throw new CasException(CasException::UNAUTHORIZED_SERVICE_PROXY);
        }

        $ticket = $this->getAvailableTicket($this->casConfig->pg_ticket_len);
        if ($ticket === false) {
            throw new CasException(CasException::INTERNAL_ERROR, 'apply proxy-granting ticket failed');
        }
        $record = $this->pgTicket->newInstance(
            [
                'ticket' => $ticket,
                'expire_at' => now()->addSeconds($this->casConfig->pg_ticket_expire),
                'created_at' => now(),
                'pgt_url' => $pgtUrl,
                'proxies' => $proxies,
            ]
        );
        $record->user()->associate($user->getEloquentModel());
        $record->service()->associate($service);
        $record->save();

        return $record;
    }

    protected function getAvailableTicket(int $totalLength): string|false
    {
        return $this->ticketGenerator->generate(
            $totalLength,
            'PGT-',
            function (string $ticket): bool {
                return is_null($this->getByTicket($ticket, false));
            },
            10
        );
    }
}
