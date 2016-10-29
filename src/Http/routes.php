<?php

$options = [
    'prefix'    => config('cas.router.prefix'),
    'namespace' => '\Leo108\CAS\Http\Controllers',
];

if (config('cas.middleware.common')) {
    $options['middleware'] = config('cas.middleware.common');
}

Route::group(
    $options,
    function () {
        $auth = config('cas.middleware.auth');
        $p    = config('cas.router.name_prefix');
        Route::get('login', 'SecurityController@showLogin')->name($p.'login.get');
        Route::post('login', 'SecurityController@login')->name($p.'login.post');
        Route::get('logout', 'SecurityController@logout')->name($p.'logout')->middleware($auth);
        Route::any('validate', 'ValidateController@v1ValidateAction')->name($p.'v1.validate');
        Route::any('serviceValidate', 'ValidateController@v2ServiceValidateAction')->name($p.'v2.validate.service');
        Route::any('proxyValidate', 'ValidateController@v2ProxyValidateAction')->name($p.'v2.validate.proxy');
        Route::any('proxy', 'ValidateController@proxyAction')->name($p.'proxy');
        Route::any('p3/serviceValidate', 'ValidateController@v3ServiceValidateAction')->name($p.'v3.validate.service');
        Route::any('p3/proxyValidate', 'ValidateController@v3ProxyValidateAction')->name($p.'v3.validate.proxy');
    }
);
