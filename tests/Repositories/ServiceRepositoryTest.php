<?php

namespace Leo108\Cas\Tests\Repositories;

use Leo108\Cas\Models\Service;
use Leo108\Cas\Models\ServiceHost;
use Leo108\Cas\Repositories\ServiceRepository;
use Leo108\Cas\Tests\TestCase;

class ServiceRepositoryTest extends TestCase
{
    public function testGetServiceByUrl()
    {
        $this->assertNull(app()->make(ServiceRepository::class)->getServiceByUrl('https://leo108.com'));

        $service = new Service(['name' => 'Test', 'enabled' => true, 'allow_proxy' => true]);
        $service->save();
        $serviceHost = new ServiceHost(['host' => 'leo108.com']);
        $serviceHost->service()->associate($service);
        $serviceHost->save();

        $this->assertEquals($service->id, app()->make(ServiceRepository::class)->getServiceByUrl('https://leo108.com')->id);
    }

    public function testIsUrlValid()
    {
        $this->assertFalse(app()->make(ServiceRepository::class)->isUrlValid('https://leo108.com'));

        $service = new Service(['name' => 'Test', 'enabled' => false, 'allow_proxy' => true]);
        $service->save();
        $serviceHost = new ServiceHost(['host' => 'leo108.com']);
        $serviceHost->service()->associate($service);
        $serviceHost->save();
        $this->assertFalse(app()->make(ServiceRepository::class)->isUrlValid('https://leo108.com'));

        $service->enabled = true;
        $service->save();
        $this->assertTrue(app()->make(ServiceRepository::class)->isUrlValid('https://leo108.com'));
    }
}
