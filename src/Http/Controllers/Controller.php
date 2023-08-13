<?php

namespace Leo108\Cas\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $field
     * @param  string|null  $default
     * @return string|null
     *
     * @phpstan-return ($default is null ? string|null : string)
     */
    protected function getStrFromRequest(Request $request, string $field, string $default = null): ?string
    {
        $value = $request->get($field);

        return is_string($value) ? $value : $default;
    }
}
