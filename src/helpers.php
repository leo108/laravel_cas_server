<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/14
 * Time: 11:25
 */

namespace Leo108\Cas;

use Leo108\Cas\Services\CasConfig;

/**
 * @param  string  $name
 * @param  array<string,mixed>  $parameters
 * @param  bool  $absolute
 * @return string
 */
function cas_route(string $name, array $parameters = [], bool $absolute = true): string
{
    $name = app(CasConfig::class)->router['name_prefix'].$name;

    return route($name, $parameters, $absolute);
}
