<?php

namespace Leo108\Cas\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Leo108\Cas\Services\CasConfig;

trait TicketTrait
{
    /**
     * @return list<string>
     *
     * @throws \Safe\Exceptions\JsonException
     */
    public function getProxiesAttribute(): array
    {
        /** @var list<string> */
        return \Safe\json_decode($this->attributes['proxies'], true);
    }

    /**
     * @param  list<string>  $value
     * @return void
     *
     * @throws \Safe\Exceptions\JsonException
     */
    public function setProxiesAttribute(array $value): void
    {
        // can not modify an existing record
        if ($this->exists) {
            return;
        }

        $this->attributes['proxies'] = \Safe\json_encode($value);
    }

    public function isExpired(): bool
    {
        return $this->expire_at->getTimestamp() < Carbon::now()->getTimestamp();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        $userTable = app(CasConfig::class)->user_table;

        return $this->belongsTo($userTable['model'], 'user_id', $userTable['id']);
    }
}
