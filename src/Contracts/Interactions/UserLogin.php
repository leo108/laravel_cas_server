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
     * @param Request $request
     * @return UserModel
     */
    public function login(Request $request);

    /**
     * @param Request $request
     * @return UserModel|null
     */
    public function getCurrentUser(Request $request);

    /**
     * @param Request $request
     * @param string  $jumpUrl
     * @param string  $service
     * @return Response
     */
    public function showLoginWarnPage(Request $request, $jumpUrl, $service);

    /**
     * @param Request $request
     * @param array   $errors
     * @return Response
     */
    public function showLoginPage(Request $request, array $errors = []);

    /**
     * @param array $errors
     * @return Response
     */
    public function redirectToHome(array $errors = []);

    /**
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request);

    /**
     * @param Request $request
     * @return Response
     */
    public function showLoggedOut(Request $request);
}
