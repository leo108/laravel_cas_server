<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 21:35
 */

namespace Leo108\Cas\Contracts\Interactions;

use Illuminate\Http\Request;
use Leo108\Cas\Contracts\Models\UserModel;
use Symfony\Component\HttpFoundation\Response;

interface UserLogin
{
    /**
     * Retrieve user from credential in request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Leo108\Cas\Contracts\Models\UserModel|null
     */
    public function login(Request $request): ?UserModel;

    /**
     * Get current logged in user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Leo108\Cas\Contracts\Models\UserModel|null
     */
    public function getCurrentUser(Request $request): ?UserModel;

    /**
     * Show failed message when authenticate failed
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAuthenticateFailed(Request $request): Response;

    /**
     * Show login page with warning message
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $jumpUrl
     * @param  string  $service
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showLoginWarnPage(Request $request, string $jumpUrl, string $service): Response;

    /**
     * Show login page
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  list<string>  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showLoginPage(Request $request, array $errors = []): Response;

    /**
     * Redirect to home page
     *
     * @param  list<string>  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectToHome(array $errors = []): Response;

    /**
     * Execute logout logic (clear session / cookie etc)
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function logout(Request $request): void;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showLoggedOut(Request $request): Response;
}
