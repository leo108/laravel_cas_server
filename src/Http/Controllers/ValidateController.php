<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:52
 */

namespace Leo108\CAS\Http\Controllers;

use Illuminate\Support\Str;
use Leo108\CAS\Contracts\TicketLocker;
use Leo108\CAS\Repositories\TicketRepository;
use Leo108\CAS\Exceptions\CAS\CasException;
use Leo108\CAS\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Leo108\CAS\Responses\JsonAuthenticationFailureResponse;
use Leo108\CAS\Responses\JsonAuthenticationSuccessResponse;
use Leo108\CAS\Responses\XmlAuthenticationFailureResponse;
use Leo108\CAS\Responses\XmlAuthenticationSuccessResponse;
use SimpleXMLElement;

class ValidateController extends Controller
{
    /**
     * @var TicketLocker
     */
    protected $ticketLocker;
    /**
     * @var TicketRepository
     */
    protected $ticketRepository;

    const BASE_XML = '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"></cas:serviceResponse>';

    /**
     * ValidateController constructor.
     * @param TicketLocker     $ticketLocker
     * @param TicketRepository $ticketRepository
     */
    public function __construct(TicketLocker $ticketLocker, TicketRepository $ticketRepository)
    {
        $this->ticketLocker     = $ticketLocker;
        $this->ticketRepository = $ticketRepository;
    }

    public function v1ValidateAction(Request $request)
    {
        $service = $request->get('service', '');
        $ticket  = $request->get('ticket', '');
        if (empty($service) || empty($ticket)) {
            return new Response('no');
        }

        if (!$this->lockTicket($ticket)) {
            return new Response('no');
        }
        $record = $this->ticketRepository->getByTicket($ticket);
        if (!$record || $record->service_url != $service) {
            $this->unlockTicket($ticket);

            return new Response('no');
        }
        $this->ticketRepository->invalidTicket($record);

        $this->unlockTicket($ticket);

        return new Response('yes');
    }

    public function v2ValidateAction(Request $request)
    {
        return $this->casValidate($request, false);
    }

    public function v3ValidateAction(Request $request)
    {
        return $this->casValidate($request, true);
    }

    /**
     * @param Request $request
     * @param bool    $returnAttr
     * @return Response
     */
    protected function casValidate(Request $request, $returnAttr)
    {
        $service = $request->get('service', '');
        $ticket  = $request->get('ticket', '');
        $format  = strtoupper($request->get('format', 'XML'));
        if (empty($service) || empty($ticket)) {
            return $this->failureResponse(
                CasException::INVALID_REQUEST,
                'param service and ticket can not be empty',
                $format
            );
        }

        if (!$this->lockTicket($ticket)) {
            return $this->failureResponse(CasException::INTERNAL_ERROR, 'try to lock ticket failed', $format);
        }

        $record = $this->ticketRepository->getByTicket($ticket);
        try {
            if (!$record) {
                throw new CasException(CasException::INVALID_TICKET, 'ticket is not valid');
            }

            if ($record->service_url != $service) {
                throw new CasException(CasException::INVALID_SERVICE, 'service is not valid');
            }
        } catch (CasException $e) {
            //invalid ticket if error occur
            $record instanceof Ticket && $this->ticketRepository->invalidTicket($record);
            $this->unlockTicket($ticket);

            return $this->failureResponse($e->getCasErrorCode(), $e->getMessage(), $format);
        }
        $this->ticketRepository->invalidTicket($record);
        $this->unlockTicket($ticket);

        $attr = $returnAttr ? $record->user->getCASAttributes() : [];

        return $this->successResponse($record->user->getName(), $format, $attr);
    }

    /**
     * @param string      $username
     * @param string      $format
     * @param array       $attributes
     * @param array       $proxies
     * @param string|null $pgt
     * @return Response
     */
    protected function successResponse($username, $format, $attributes, $proxies = [], $pgt = null)
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonAuthenticationSuccessResponse::class);
        } else {
            $resp = app(XmlAuthenticationSuccessResponse::class);
        }
        $resp->setUser($username);
        if (!empty($attributes)) {
            $resp->setAttributes($attributes);
        }
        if (!empty($proxies)) {
            $resp->setProxies($proxies);
        }

        if (is_string($pgt)) {
            $resp->setProxyGrantingTicket($pgt);
        }

        return $resp->toResponse();
    }

    /**
     * @param string $code
     * @param string $description
     * @param string $format
     * @return Response
     */
    protected function failureResponse($code, $description, $format)
    {
        if (strtoupper($format) === 'JSON') {
            $resp = app(JsonAuthenticationFailureResponse::class);
        } else {
            $resp = app(XmlAuthenticationFailureResponse::class);
        }
        $resp->setFailure($code, $description);

        return $resp->toResponse();
    }

    /**
     * @param string $ticket
     * @return bool
     */
    protected function lockTicket($ticket)
    {
        return $this->ticketLocker->acquireLock($ticket, config('cas.lock_timeout'));
    }

    /**
     * @param string $ticket
     * @return bool
     */
    protected function unlockTicket($ticket)
    {
        return $this->ticketLocker->releaseLock($ticket);
    }
}
