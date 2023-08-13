<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 20:13
 */

namespace Leo108\Cas\Repositories;

use Leo108\Cas\Models\Service;
use Leo108\Cas\Models\ServiceHost;

class ServiceRepository
{
    public function __construct(protected Service $service, protected ServiceHost $serviceHost)
    {
    }

    public function getServiceByUrl(string $url): ?Service
    {
        $host = \Safe\parse_url($url, PHP_URL_HOST);

        /** @var \Leo108\Cas\Models\ServiceHost|null $record */
        $record = $this->serviceHost->newQuery()->where('host', $host)->first();

        return $record?->service;
    }

    public function isUrlValid(string $url): bool
    {
        $service = $this->getServiceByUrl($url);

        return $service !== null && $service->enabled;
    }
}
