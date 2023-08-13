<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 20:19
 */

namespace Leo108\Cas\Repositories;

use Leo108\Cas\Contracts\Models\UserModel;
use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\Ticket;
use Leo108\Cas\Services\CasConfig;
use Leo108\Cas\Services\TicketGenerator;

class TicketRepository
{
    protected Ticket $ticket;
    protected ServiceRepository $serviceRepository;
    protected TicketGenerator $ticketGenerator;
    protected CasConfig $casConfig;

    public function __construct(
        Ticket $ticket,
        ServiceRepository $serviceRepository,
        TicketGenerator $ticketGenerator,
        CasConfig $casConfig
    ) {
        $this->ticket = $ticket;
        $this->serviceRepository = $serviceRepository;
        $this->ticketGenerator = $ticketGenerator;
        $this->casConfig = $casConfig;
    }

    /**
     * @param  \Leo108\Cas\Contracts\Models\UserModel  $user
     * @param  string  $serviceUrl
     * @param  list<string>  $proxies
     * @return \Leo108\Cas\Models\Ticket
     *
     * @throws \Leo108\Cas\Exceptions\CasException
     */
    public function applyTicket(UserModel $user, string $serviceUrl, array $proxies = []): Ticket
    {
        $service = $this->serviceRepository->getServiceByUrl($serviceUrl);

        if ($service === null) {
            throw new CasException(CasException::INVALID_SERVICE);
        }

        $ticket = $this->getAvailableTicket($this->casConfig->ticket_len, count($proxies) === 0 ? 'ST-' : 'PT-');

        if ($ticket === false) {
            throw new CasException(CasException::INTERNAL_ERROR, 'apply ticket failed');
        }

        $record = $this->ticket->newInstance(
            [
                'ticket' => $ticket,
                'expire_at' => now()->addSeconds($this->casConfig->pg_ticket_expire),
                'created_at' => now(),
                'service_url' => $serviceUrl,
                'proxies' => $proxies,
            ]
        );
        $record->user()->associate($user->getEloquentModel());
        $record->service()->associate($service);
        $record->save();

        return $record;
    }

    /**
     * @param  string  $ticket
     * @param  bool  $checkExpired
     * @return null|Ticket
     */
    public function getByTicket(string $ticket, bool $checkExpired = true): ?Ticket
    {
        $record = $this->ticket->newQuery()->where('ticket', $ticket)->first();

        if ($record === null) {
            return null;
        }

        return ($checkExpired && $record->isExpired()) ? null : $record;
    }

    public function invalidTicket(Ticket $ticket): void
    {
        $ticket->delete();
    }

    /**
     * @param  int  $totalLength
     * @param  string  $prefix
     * @return string|false
     */
    protected function getAvailableTicket(int $totalLength, string $prefix): string|false
    {
        return $this->ticketGenerator->generate(
            $totalLength,
            $prefix,
            function (string $ticket): bool {
                return is_null($this->getByTicket($ticket, false));
            },
            10
        );
    }
}
