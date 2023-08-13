<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:52
 */

namespace Leo108\Cas\Http\Controllers;

use Illuminate\Http\Request;
use Leo108\Cas\Contracts\TicketLocker;
use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\Ticket;
use Leo108\Cas\Repositories\PGTicketRepository;
use Leo108\Cas\Repositories\TicketRepository;
use Leo108\Cas\Responses\JsonAuthenticationFailureResponse;
use Leo108\Cas\Responses\JsonAuthenticationSuccessResponse;
use Leo108\Cas\Responses\JsonProxyFailureResponse;
use Leo108\Cas\Responses\JsonProxySuccessResponse;
use Leo108\Cas\Responses\XmlAuthenticationFailureResponse;
use Leo108\Cas\Responses\XmlAuthenticationSuccessResponse;
use Leo108\Cas\Responses\XmlProxyFailureResponse;
use Leo108\Cas\Responses\XmlProxySuccessResponse;
use Leo108\Cas\Services\CasConfig;
use Leo108\Cas\Services\PGTCaller;
use Leo108\Cas\Services\TicketGenerator;
use Symfony\Component\HttpFoundation\Response;

class ValidateController extends Controller
{
    protected TicketLocker $ticketLocker;
    protected TicketRepository $ticketRepository;
    protected PGTicketRepository $pgTicketRepository;
    protected TicketGenerator $ticketGenerator;
    protected PGTCaller $pgtCaller;

    public function __construct(
        TicketLocker $ticketLocker,
        TicketRepository $ticketRepository,
        PGTicketRepository $pgTicketRepository,
        TicketGenerator $ticketGenerator,
        PGTCaller $pgtCaller
    ) {
        $this->ticketLocker = $ticketLocker;
        $this->ticketRepository = $ticketRepository;
        $this->pgTicketRepository = $pgTicketRepository;
        $this->ticketGenerator = $ticketGenerator;
        $this->pgtCaller = $pgtCaller;
    }

    public function v1ValidateAction(Request $request): Response
    {
        $service = $this->getStrFromRequest($request, 'service', '');
        $ticket = $this->getStrFromRequest($request, 'ticket', '');

        if ($service === '' || $ticket === '') {
            return new Response('no');
        }

        if (! $this->lockTicket($ticket)) {
            return new Response('no');
        }

        $record = $this->ticketRepository->getByTicket($ticket);

        if ($record === null || $record->service_url != $service) {
            $this->unlockTicket($ticket);

            return new Response('no');
        }

        $this->ticketRepository->invalidTicket($record);

        $this->unlockTicket($ticket);

        return new Response('yes');
    }

    public function v2ServiceValidateAction(Request $request): Response
    {
        return $this->casValidate($request, true, false);
    }

    public function v3ServiceValidateAction(Request $request): Response
    {
        return $this->casValidate($request, true, false);
    }

    public function v2ProxyValidateAction(Request $request): Response
    {
        return $this->casValidate($request, false, true);
    }

    public function v3ProxyValidateAction(Request $request): Response
    {
        return $this->casValidate($request, true, true);
    }

    public function proxyAction(Request $request): Response
    {
        $pgt = $this->getStrFromRequest($request, 'pgt', '');
        $target = $this->getStrFromRequest($request, 'targetService', '');
        $format = strtoupper($this->getStrFromRequest($request, 'format', 'XML'));

        if ($pgt === '' || $target === '') {
            return $this->proxyFailureResponse(
                CasException::INVALID_REQUEST,
                'param pgt and targetService can not be empty',
                $format
            );
        }

        $record = $this->pgTicketRepository->getByTicket($pgt);

        try {
            if ($record === null) {
                throw new CasException(CasException::INVALID_TICKET, 'ticket is not valid');
            }

            $proxies = $record->proxies;
            array_unshift($proxies, $record->pgt_url);
            $ticket = $this->ticketRepository->applyTicket($record->user, $target, $proxies);
        } catch (CasException $e) {
            return $this->proxyFailureResponse($e->getCasErrorCode(), $e->getMessage(), $format);
        }

        return $this->proxySuccessResponse($ticket->ticket, $format);
    }

    protected function casValidate(Request $request, bool $returnAttr, bool $allowProxy): Response
    {
        $service = $this->getStrFromRequest($request, 'service', '');
        $ticket = $this->getStrFromRequest($request, 'ticket', '');
        $format = strtoupper($this->getStrFromRequest($request, 'format', 'XML'));

        if ($service === '' || $ticket === '') {
            return $this->authFailureResponse(
                CasException::INVALID_REQUEST,
                'param service and ticket can not be empty',
                $format
            );
        }

        if (! $this->lockTicket($ticket)) {
            return $this->authFailureResponse(CasException::INTERNAL_ERROR, 'try to lock ticket failed', $format);
        }

        $record = $this->ticketRepository->getByTicket($ticket);

        try {
            if ($record === null || (! $allowProxy && $record->isProxy())) {
                throw new CasException(CasException::INVALID_TICKET, 'ticket is not valid');
            }

            if ($record->service_url !== $service) {
                throw new CasException(CasException::INVALID_SERVICE, 'service is not valid');
            }
        } catch (CasException $e) {
            //invalid ticket if error occur
            if ($record instanceof Ticket) {
                $this->ticketRepository->invalidTicket($record);
            }

            $this->unlockTicket($ticket);

            return $this->authFailureResponse($e->getCasErrorCode(), $e->getMessage(), $format);
        }

        $proxies = [];
        if ($record->isProxy()) {
            $proxies = $record->proxies;
        }

        $user = $record->user;
        $this->ticketRepository->invalidTicket($record);
        $this->unlockTicket($ticket);

        //handle pgt
        $iou = null;
        $pgtUrl = $this->getStrFromRequest($request, 'pgtUrl', '');

        if ($pgtUrl !== '') {
            try {
                $pgTicket = $this->pgTicketRepository->applyTicket($user, $pgtUrl, $proxies);
                $iou = $this->ticketGenerator->generateOne(app(CasConfig::class)->pg_ticket_iou_len, 'PGTIOU-');
                if (! $this->pgtCaller->call($pgtUrl, $pgTicket->ticket, $iou)) {
                    $iou = null;
                }
            } catch (CasException $e) {
                $iou = null;
            }
        }

        $attr = $returnAttr ? $record->user->getCasAttributes() : [];

        return $this->authSuccessResponse($record->user->getName(), $format, $attr, $proxies, $iou);
    }

    /**
     * @param  string  $username
     * @param  string  $format
     * @param  array<string,mixed>  $attributes
     * @param  list<string>  $proxies
     * @param  string|null  $pgt
     * @return Response
     */
    protected function authSuccessResponse(string $username, string $format, array $attributes, array $proxies = [], string $pgt = null): Response
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonAuthenticationSuccessResponse::class);
        } else {
            $resp = app(XmlAuthenticationSuccessResponse::class);
        }
        $resp->setUser($username);

        if (count($attributes) > 0) {
            $resp->setAttributes($attributes);
        }
        if (count($proxies) > 0) {
            $resp->setProxies($proxies);
        }

        if (is_string($pgt)) {
            $resp->setProxyGrantingTicket($pgt);
        }

        return $resp->toResponse();
    }

    protected function authFailureResponse(string $code, string $description, string $format): Response
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonAuthenticationFailureResponse::class);
        } else {
            $resp = app(XmlAuthenticationFailureResponse::class);
        }
        $resp->setFailure($code, $description);

        return $resp->toResponse();
    }

    protected function proxySuccessResponse(string $ticket, string $format): Response
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonProxySuccessResponse::class);
        } else {
            $resp = app(XmlProxySuccessResponse::class);
        }
        $resp->setProxyTicket($ticket);

        return $resp->toResponse();
    }

    protected function proxyFailureResponse(string $code, string $description, string $format): Response
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonProxyFailureResponse::class);
        } else {
            $resp = app(XmlProxyFailureResponse::class);
        }
        $resp->setFailure($code, $description);

        return $resp->toResponse();
    }

    protected function lockTicket(string $ticket): bool
    {
        return $this->ticketLocker->acquireLock($ticket, app(CasConfig::class)->lock_timeout);
    }

    protected function unlockTicket(string $ticket): bool
    {
        return $this->ticketLocker->releaseLock($ticket);
    }
}
