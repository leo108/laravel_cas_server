# CAS Server for Laravel

`CAS Server for Laravel` is a Laravel package that implements the server part of [CAS protocol](https://apereo.github.io/cas/4.2.x/protocol/CAS-Protocol-Specification.html) v1/v2/v3 without proxy.

Currently this package works for Laravel 5.1/5.2/5.3 .

[![Latest Version](http://img.shields.io/github/release/leo108/laravel_cas_server.svg)](https://github.com/leo108/laravel_cas_server/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/leo108/laravel_cas_server/master.svg)](https://travis-ci.org/leo108/laravel_cas_server)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/leo108/laravel_cas_server/master.svg)](https://scrutinizer-ci.com/g/leo108/laravel_cas_server/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/leo108/laravel_cas_server.svg)](https://packagist.org/packages/leo108/laravel_cas_server)

## Requirements

- PHP >=5.5.9

## Installation && Usage

1. `composer require leo108/laravel_cas_server`
2. add `Leo108\CAS\CASServerServiceProvider::class` to the `providers` field in `config/app.php`
3. `php artisan vendor:publish --provider="Leo108\CAS\CASServerServiceProvider"`
4. modify `config/cas.php`, fields in config file are all self-described
5. make your `App\User` implement `Leo108\CAS\Contracts\Models\UserModel`
6. create a class implements `Leo108\CAS\Contracts\TicketLocker`
7. create a class implements `Leo108\CAS\Contracts\Interactions\UserLogin`
8. visit `http://your-domain/cas/login` to see the login page (assume that you didn't change the `router.prefix` value in `config/cas.php`)
