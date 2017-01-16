<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 21:35
 */

namespace Leo108\CAS\Contracts\Interactions;

use Illuminate\Http\Request;
use Leo108\CAS\Contracts\Models\UserModel;
use Symfony\Component\HttpFoundation\Response;

interface UserLogin
{
    /**
     * Retrive user from credential in request
     *
     * @param Request $request
     * @return UserModel|null
     */
    public function login(Request $request);

    /**
     * Get current logged in user
     *
     * @param Request $request
     * @return UserModel|null
     */
    public function getCurrentUser(Request $request);

    /**
     * Show failed message when authenticate failed
     *
     * @param Request $request
     * @return Response
     */
    public function showAuthenticateFailed(Request $request);

    /**
     * Show login page with warning message
     *
     * @param Request $request
     * @param string  $jumpUrl
     * @param string  $service
     * @return Response
     */
    public function showLoginWarnPage(Request $request, $jumpUrl, $service);

    /**
     * Show login page
     *
     * @param Request $request
     * @param array   $errors
     * @return Response
     */
    public function showLoginPage(Request $request, array $errors = []);

    /**
     * Redirect to home page
     *
     * @param array $errors
     * @return Response
     */
    public function redirectToHome(array $errors = []);

    /**
     * Execute logout logic (clear session / cookie etc)
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request);

    /**
     *
     * @param Request $request
     * @return Response
     */
    public function showLoggedOut(Request $request);
}
