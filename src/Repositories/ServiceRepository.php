<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 16/9/17
 * Time: 20:13
 */

namespace Leo108\CAS\Repositories;

use Leo108\CAS\Exceptions\UserException;
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
     * @return \Leo108\CAS\Models\Service|null
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

    /**
     * @param $name
     * @param $hostArr
     * @param $enabled
     * @param $id
     * @return \Leo108\CAS\Models\Service
     */
    public function createOrUpdate($name, $hostArr, $enabled = true, $id = 0)
    {
        \DB::beginTransaction();
        if ($id == 0) {
            if ($this->service->where('name', $name)->count() > 0) {
                throw new UserException(trans('cas::message.service.name_duplicated'));
            }

            $service = $this->service->create(
                [
                    'name'       => $name,
                    'enabled'    => boolval($enabled),
                ]
            );
        } else {
            $service          = $this->service->find($id);
            $service->enabled = boolval($enabled);
            $service->save();
            $this->serviceHost->where('service_id', $id)->delete();
        }

        foreach ($hostArr as $host) {
            $host = trim($host);
            if ($this->serviceHost->where('host', $host)->count() > 0) {
                throw new UserException(trans('cas::message.service.host_occupied', ['host' => $host]));
            }
            $hostModel = $this->serviceHost->newInstance(['host' => $host]);
            $hostModel->service()->associate($service);
            $hostModel->save();
        }
        \DB::commit();

        return $service;
    }

    public function getList($search, $page, $limit)
    {
        /* @var \Illuminate\Database\Query\Builder $query */
        $like = '%'.$search.'%';
        if (!empty($search)) {
            $query = $this->service->whereHas(
                'hosts',
                function ($query) use ($like) {
                    $query->where('host', 'like', $like);
                }
            )->orWhere('name', 'like', $like)->with('hosts');
        } else {
            $query = $this->service->with('hosts');
        }

        return $query->orderBy('id', 'desc')->paginate($limit, ['*'], 'page', $page);
    }
}
