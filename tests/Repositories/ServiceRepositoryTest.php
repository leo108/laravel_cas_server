<?php

namespace Repositories;

use Mockery;
use TestCase;
use Illuminate\Database\Eloquent\Collection;
use Leo108\CAS\Models\Service;
use Leo108\CAS\Models\ServiceHost;
use Leo108\CAS\Repositories\ServiceRepository;

/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 10:15
 */
class ServiceRepositoryTest extends TestCase
{
    public function testGetServiceByUrl()
    {
        $serviceHost = Mockery::mock(ServiceHost::class);
        $serviceHost->shouldReceive('where->first')->andReturn(null);
        app()->instance(ServiceHost::class, $serviceHost);
        $this->assertNull(app()->make(ServiceRepository::class)->getServiceByUrl('http://www.baidu.com'));

        $service     = Mockery::mock(Service::class);
        $serviceHost = Mockery::mock(ServiceHost::class);
        $serviceHost->shouldReceive('where->first')->andReturn((object) ['service' => $service]);
        app()->instance(ServiceHost::class, $serviceHost);
        $this->assertEquals($service, app()->make(ServiceRepository::class)->getServiceByUrl('http://www.baidu.com'));
    }

    public function testIsUrlValid()
    {
        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->makePartial()
            ->shouldReceive('getServiceByUrl')
            ->andReturn(null)
            ->getMock();
        $this->assertFalse($serviceRepository->isUrlValid('http://www.baidu.com'));

        $serviceRepository = Mockery::mock(ServiceRepository::class)
            ->makePartial()
            ->shouldReceive('getServiceByUrl')
            ->andReturn((object) ['enabled' => true])
            ->getMock();
        $this->assertTrue($serviceRepository->isUrlValid('http://www.baidu.com'));
    }
}
