<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 20:13
 */

namespace Leo108\CAS\Repositories;

use Leo108\CAS\Models\Service;
use Leo108\CAS\Models\ServiceHost;

class ServiceRepository
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * @var ServiceHost;
     */
    protected $serviceHost;

    /**
     * ServiceRepository constructor.
     * @param Service     $service
     * @param ServiceHost $serviceHost
     */
    public function __construct(Service $service, ServiceHost $serviceHost)
    {
        $this->service     = $service;
        $this->serviceHost = $serviceHost;
    }

    /**
     * @param $url
     * @return Service|null
     */
    public function getServiceByUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        $record = $this->serviceHost->where('host', $host)->first();
        if (!$record) {
            return null;
        }

        return $record->service;
    }

    /**
     * @param $url
     * @return bool
     */
    public function isUrlValid($url)
    {
        $service = $this->getServiceByUrl($url);

        return $service !== null && $service->enabled;
    }
}
