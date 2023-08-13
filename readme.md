# CAS Server for Laravel

laravel_cas_server is a Laravel package that implements the server part of [CAS protocol](https://apereo.github.io/cas/4.2.x/protocol/CAS-Protocol-Specification.html) v1/v2/v3.

This package works for Laravel 9/10 .

[![Latest Version](http://img.shields.io/github/release/leo108/laravel_cas_server.svg)](https://github.com/leo108/laravel_cas_server/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/leo108/laravel_cas_server/master.svg)](https://travis-ci.org/leo108/laravel_cas_server)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/leo108/laravel_cas_server/master.svg)](https://scrutinizer-ci.com/g/leo108/laravel_cas_server/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/leo108/laravel_cas_server.svg)](https://packagist.org/packages/leo108/laravel_cas_server)

## Requirements

- PHP >=8.0

## Installation && Usage

- `composer require leo108/laravel_cas_server`
- `php artisan vendor:publish --provider="Leo108\Cas\CASServerServiceProvider"`
- update `config/cas.php`, fields in config file are all self-described
- `php artisan migrate`
- make your `App\User` implement `Leo108\Cas\Contracts\Models\UserModel`
- create a class implements `Leo108\Cas\Contracts\TicketLocker`
- create a class implements `Leo108\Cas\Contracts\Interactions\UserLogin`
- visit `http://your-domain/cas/login` to see the login page (assume that you didn't change the `router.prefix` value in `config/cas.php`)

## Example

If you are looking for an out of box solution of CAS Server powered by PHP, you can check [php_cas_server](https://github.com/leo108/php_cas_server)
