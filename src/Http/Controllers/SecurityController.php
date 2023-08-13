<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:50
 */

namespace Leo108\Cas\Http\Controllers;

use Illuminate\Http\Request;
use function Leo108\Cas\cas_route;
use Leo108\Cas\Contracts\Interactions\UserLogin;
use Leo108\Cas\Contracts\Models\UserModel;
use Leo108\Cas\Events\CasUserLoginEvent;
use Leo108\Cas\Events\CasUserLogoutEvent;
use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Repositories\PGTicketRepository;
use Leo108\Cas\Repositories\ServiceRepository;
use Leo108\Cas\Repositories\TicketRepository;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{
    protected ServiceRepository $serviceRepository;
    protected TicketRepository $ticketRepository;
    protected PGTicketRepository $pgTicketRepository;
    protected UserLogin $loginInteraction;

    public function __construct(
        ServiceRepository $serviceRepository,
        TicketRepository $ticketRepository,
        PGTicketRepository $pgTicketRepository,
        UserLogin $loginInteraction
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->ticketRepository = $ticketRepository;
        $this->loginInteraction = $loginInteraction;
        $this->pgTicketRepository = $pgTicketRepository;
    }

    public function showLogin(Request $request): Response
    {
        $serviceUrl = $this->getStrFromRequest($request, 'service', '');
        $errors = [];

        if ($serviceUrl !== '' && ! $this->serviceRepository->isUrlValid($serviceUrl)) {
            $errors[] = (new CasException(CasException::INVALID_SERVICE))->getCasMsg();
        }

        $user = $this->loginInteraction->getCurrentUser($request);
        //user already has sso session
        if ($user !== null) {
            //has errors, should not be redirected to target url
            if (count($errors) > 0) {
                return $this->loginInteraction->redirectToHome($errors);
            }

            //must not be transparent
            if ($this->getStrFromRequest($request, 'warn') === 'true' && $serviceUrl !== '') {
                $query = $request->query->all();
                unset($query['warn']);
                $url = cas_route('login.get', $query);

                return $this->loginInteraction->showLoginWarnPage($request, $url, $serviceUrl);
            }

            return $this->authenticated($request, $user, $serviceUrl);
        }

        return $this->loginInteraction->showLoginPage($request, $errors);
    }

    public function login(Request $request): Response
    {
        $user = $this->loginInteraction->login($request);

        if (is_null($user)) {
            return $this->loginInteraction->showAuthenticateFailed($request);
        }

        $serviceUrl = $this->getStrFromRequest($request, 'service', '');

        if ($serviceUrl !== '' && ! $this->serviceRepository->isUrlValid($serviceUrl)) {
            return $this->loginInteraction->redirectToHome([(new CasException(CasException::INVALID_SERVICE))->getCasMsg()]);
        }

        return $this->authenticated($request, $user, $serviceUrl);
    }

    protected function authenticated(Request $request, UserModel $user, string $serviceUrl): Response
    {
        event(new CasUserLoginEvent($request, $user));

        if ($serviceUrl !== '') {
            $query = \Safe\parse_url($serviceUrl, PHP_URL_QUERY);

            try {
                $ticket = $this->ticketRepository->applyTicket($user, $serviceUrl);
            } catch (CasException $e) {
                return $this->loginInteraction->redirectToHome([$e->getCasMsg()]);
            }

            $finalUrl = $serviceUrl.($query !== '' && $query !== null ? '&' : '?').'ticket='.$ticket->ticket;

            return response()->redirectTo($finalUrl);
        }

        return $this->loginInteraction->redirectToHome();
    }

    public function logout(Request $request): Response
    {
        $user = $this->loginInteraction->getCurrentUser($request);

        if ($user !== null) {
            $this->loginInteraction->logout($request);
            $this->pgTicketRepository->invalidTicketByUser($user);
            event(new CasUserLogoutEvent($request, $user));
        }

        $service = $this->getStrFromRequest($request, 'service');

        if (is_string($service) && $service !== '' && $this->serviceRepository->isUrlValid($service)) {
            return response()->redirectTo($service);
        }

        return $this->loginInteraction->showLoggedOut($request);
    }
}
