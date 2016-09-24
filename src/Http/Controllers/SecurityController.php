<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/1
 * Time: 14:50
 */

namespace Leo108\CAS\Http\Controllers;

use Leo108\CAS\Contracts\Interactions\UserLogin;
use Leo108\CAS\Events\CasUserLoginEvent;
use Leo108\CAS\Events\CasUserLogoutEvent;
use Leo108\CAS\Exceptions\CAS\CasException;
use Illuminate\Http\Request;
use Leo108\CAS\Repositories\ServiceRepository;
use Leo108\CAS\Repositories\TicketRepository;

class SecurityController extends Controller
{
    /**
     * @var ServiceRepository
     */
    protected $serviceRepository;

    /**
     * @var TicketRepository
     */
    protected $ticketRepository;

    /**
     * @var UserLogin
     */
    protected $loginInteraction;

    /**
     * SecurityController constructor.
     * @param ServiceRepository $serviceRepository
     * @param TicketRepository  $ticketRepository
     * @param UserLogin         $loginInteraction
     */
    public function __construct(
        ServiceRepository $serviceRepository,
        TicketRepository $ticketRepository,
        UserLogin $loginInteraction
    ) {
        $this->serviceRepository = $serviceRepository;
        $this->ticketRepository  = $ticketRepository;
        $this->loginInteraction  = $loginInteraction;
    }


    public function showLogin(Request $request)
    {
        $service = $request->get('service', '');
        $errors  = [];
        if (!empty($service)) {
            //service not found in white list
            if (!$this->serviceRepository->isUrlValid($service)) {
                $errors[] = (new CasException(CasException::INVALID_SERVICE))->getCasMsg();
            }
        }

        $user = $this->loginInteraction->getCurrentUser($request);
        //user already has sso session
        if ($user) {
            //has errors, should not be redirected to target url
            if (!empty($errors)) {
                return $this->loginInteraction->redirectToHome($errors);
            }

            //must not be transparent
            if ($request->get('warn') === 'true' && !empty($service)) {
                $query = $request->query->all();
                unset($query['warn']);
                $url = cas_route('login_action', $query);

                return $this->loginInteraction->showLoginWarnPage($request, $url, $service);
            }

            return $this->authenticated($request);
        }

        return $this->loginInteraction->showLoginPage($request, $errors);
    }

    public function login(Request $request)
    {
        return $this->loginInteraction->login($request, array($this, 'authenticated'));
    }

    public function authenticated(Request $request)
    {
        $user = $this->loginInteraction->getCurrentUser($request);
        event(new CasUserLoginEvent($request, $user));
        $serviceUrl = $request->get('service', '');
        if (!empty($serviceUrl)) {
            $query = parse_url($serviceUrl, PHP_URL_QUERY);
            try {
                $ticket = $this->ticketRepository->applyTicket($user, $serviceUrl);
            } catch (CasException $e) {
                return $this->loginInteraction->redirectToHome([$e->getCasMsg()]);
            }
            $finalUrl = $serviceUrl.($query ? '&' : '?').'ticket='.$ticket->ticket;

            return redirect($finalUrl);
        }

        return $this->loginInteraction->redirectToHome();
    }

    public function logout(Request $request)
    {
        return $this->loginInteraction->logout(
            $request,
            function (Request $request) {
                event(new CasUserLogoutEvent($request, $this->loginInteraction->getCurrentUser($request)));
            }
        );
    }
}
