<?php

namespace Leo108\Cas\Services;

use Illuminate\Support\Facades\Validator;

/**
 * @property-read int $lock_timeout
 * @property-read int $ticket_expire
 * @property-read int $ticket_len
 * @property-read int $pg_ticket_expire
 * @property-read int $pg_ticket_len
 * @property-read int $pg_ticket_iou_len
 * @property-read bool $verify_ssl
 * @property-read array{id:string,name:string,model:class-string} $user_table
 * @property-read array{prefix:string,name_prefix:string} $router
 * @property-read array{common:string} $middleware
 */
class CasConfig
{
    /**
     * @var array<string,mixed>
     */
    protected array $config;

    /**
     * @param  array<string,mixed>  $config
     */
    public function __construct(array $config)
    {
        $validator = Validator::make($config, [
            'lock_timeout' => ['required', 'int'],
            'ticket_expire' => ['required', 'int'],
            'ticket_len' => ['required', 'int'],
            'pg_ticket_expire' => ['required', 'int'],
            'pg_ticket_len' => ['required', 'int'],
            'pg_ticket_iou_len' => ['required', 'int'],
            'verify_ssl' => ['required', 'boolean'],
            'user_table' => ['required', 'array'],
            'user_table.id' => ['required', 'string'],
            'user_table.name' => ['required', 'string'],
            'user_table.model' => ['required', 'string'],
            'router' => ['required', 'array'],
            'router.prefix' => ['required', 'string'],
            'router.name_prefix' => ['required', 'string'],
            'middleware' => ['required', 'array'],
            'middleware.common' => ['required', 'string'],
        ]);

        $validator->validate();
        $this->config = $config;
    }

    public function __get(string $name): mixed
    {
        if (! array_key_exists($name, $this->config)) {
            throw new \InvalidArgumentException("Invalid config name: {$name}");
        }

        return $this->config[$name];
    }
}
