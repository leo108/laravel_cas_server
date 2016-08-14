<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 21:35
 */

namespace Leo108\CAS\Contracts\Interactions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface UserLogin
{
    /**
     * @param Request  $request
     * @param callable $authenticated
     * @return Response
     */
    public function handle(Request $request, callable $authenticated);

    /**
     * @param Request $request
     * @return Authenticatable|null
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
}
