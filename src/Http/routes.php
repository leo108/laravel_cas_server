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
        Route::get('login', ['as' => $p.'login_page', 'uses' => 'SecurityController@showLogin']);
        Route::post('login', ['as' => $p.'login_action', 'uses' => 'SecurityController@login']);
        Route::get('logout', ['as' => $p.'logout', 'uses' => 'SecurityController@logout'])->middleware($auth);
        Route::any('validate', ['as' => $p.'v1validate', 'uses' => 'ValidateController@v1ValidateAction']);
        Route::any('serviceValidate', ['as' => $p.'v2validate', 'uses' => 'ValidateController@v2ValidateAction']);
        Route::any('p3/serviceValidate', ['as' => $p.'v3validate', 'uses' => 'ValidateController@v3ValidateAction']);
    }
);
