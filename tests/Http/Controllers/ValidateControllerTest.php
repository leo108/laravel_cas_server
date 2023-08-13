<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/12
 * Time: 15:56
 */

namespace Leo108\Cas\Tests\Http\Controllers;

use function Leo108\Cas\cas_route;
use Leo108\Cas\Contracts\TicketLocker;
use Leo108\Cas\Exceptions\CasException;
use Leo108\Cas\Models\PGTicket;
use Leo108\Cas\Models\Ticket;
use Leo108\Cas\Repositories\PGTicketRepository;
use Leo108\Cas\Repositories\TicketRepository;
use Leo108\Cas\Services\PGTCaller;
use Leo108\Cas\Services\TicketGenerator;
use Leo108\Cas\Tests\Support\User;
use Leo108\Cas\Tests\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ValidateControllerTest extends TestCase
{
    use MatchesSnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(TicketLocker::class);
    }

    public function testV1ValidateActionWithInvalidRequest()
    {
        $resp = $this->post(cas_route('v1.validate'));
        $this->assertEquals('no', $resp->getContent());
    }

    public function testV1ValidateActionWithLockFailed()
    {
        $this->mockTicketLocker('ST-123456');
        $resp = $this->post(cas_route('v1.validate', ['ticket' => 'ST-123456', 'service' => 'https://leo108.com']));
        $this->assertEquals('no', $resp->getContent());
    }

    public function testV1ValidateActionWithInvalidTicket()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket);

        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->with($ticket)
            ->andReturnNull();

        $resp = $this->post(cas_route('v1.validate', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $this->assertEquals('no', $resp->getContent());
    }

    public function testV1ValidateActionWithValidTicketButServiceMismatch()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket);
        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->with($ticket)
            ->andReturn(new Ticket(['service_url' => 'https://google.com']));

        $resp = $this->post(cas_route('v1.validate', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $this->assertEquals('no', $resp->getContent());
    }

    public function testV1ValidateActionWithValidTicketAndService()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket);
        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->with($ticket)
            ->andReturn(new Ticket(['service_url' => 'https://leo108.com']))
            ->once()
            ->shouldReceive('invalidTicket')
            ->once();

        $resp = $this->post(cas_route('v1.validate', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $this->assertEquals('yes', $resp->getContent());
    }

    public function testV2ServiceValidateActionWithInvalidRequest()
    {
        $resp = $this->post(cas_route('v2.validate.service'));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithLockTicketFailed()
    {
        $this->mock(TicketLocker::class)
            ->shouldReceive('acquireLock')
            ->andReturn(false)
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => 'ST-123456', 'service' => 'https://leo108.com']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => 'ST-123456', 'service' => 'https://leo108.com', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithInvalidTicket()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket, times: 2);
        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->with($ticket)
            ->andReturnNull()
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithValidTicketButServiceMismatch()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket, times: 2);
        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->with($ticket)
            ->andReturn(new Ticket(['service_url' => 'https://google.com', 'proxies' => []]))
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithValidProxyTicketButNotAllowProxy()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket, times: 2);
        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->with($ticket)
            ->andReturn(new Ticket(['service_url' => 'https://leo108.com', 'proxies' => ['https://proxy1.com']]))
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithValidTicketAndServiceAndNoPgt()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket, times: 2);
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => [],
        ]);
        $ticketModel->user()->associate($user);
        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithValidTicketAndServiceAndPgtButApplyPGTFailed()
    {
        $ticket = 'ST-123456';
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => [],
        ]);
        $ticketModel->user()->associate($user);
        $this->mockTicketLocker($ticket, times: 2);

        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $this->mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andThrow(new CasException(CasException::INTERNAL_ERROR))
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithValidTicketAndServiceAndPgtButCallPgtUrlFailed()
    {
        $ticket = 'ST-123456';
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => [],
        ]);
        $ticketModel->user()->associate($user);
        $this->mockTicketLocker($ticket, times: 2);

        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $pgtTicketModel = new PGTicket([
            'ticket' => 'PGT-123456',
        ]);

        $this->mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($pgtTicketModel)
            ->twice();

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->twice();
        $this->mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(false)
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ServiceValidateActionWithValidTicketAndServiceAndPgtAndCallPgtUrlSuccess()
    {
        $ticket = 'ST-123456';
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => [],
        ]);
        $ticketModel->user()->associate($user);
        $this->mockTicketLocker($ticket, times: 2);

        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $pgtTicketModel = new PGTicket([
            'ticket' => 'PGT-123456',
        ]);

        $this->mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($pgtTicketModel)
            ->twice();

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->twice();

        $this->mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(true)
            ->twice();

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.service', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ProxyValidateActionWithValidProxyTicketAndAllowProxy()
    {
        $ticket = 'ST-123456';
        $this->mockTicketLocker($ticket, times: 2);
        $proxies = ['https://proxy1.com', 'https://proxy2.com'];
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => $proxies,
        ]);
        $ticketModel->user()->associate($user);
        $this->mock(TicketRepository::class)
            ->shouldReceive('invalidTicket')
            ->twice()
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice();

        $resp = $this->post(cas_route('v2.validate.proxy', ['ticket' => $ticket, 'service' => 'https://leo108.com']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.proxy', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV2ProxyValidateActionWithValidProxyTicketAndServiceAndPgtAndCallPgtUrlSuccess()
    {
        $ticket = 'ST-123456';
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => [
                'https://proxy1.com',
            ],
        ]);
        $ticketModel->user()->associate($user);
        $this->mockTicketLocker($ticket, times: 2);

        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $pgtTicketModel = new PGTicket([
            'ticket' => 'PGT-123456',
        ]);

        $this->mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($pgtTicketModel)
            ->twice();

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->twice();

        $this->mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(true)
            ->twice();

        $resp = $this->post(cas_route('v2.validate.proxy', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v2.validate.proxy', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    public function testV3ProxyValidateActionWithValidProxyTicketAndServiceAndPgtAndCallPgtUrlSuccess()
    {
        $ticket = 'ST-123456';
        $user = $this->initUser();
        $ticketModel = new Ticket([
            'ticket' => $ticket,
            'service_url' => 'https://leo108.com',
            'proxies' => [
                'https://proxy1.com',
            ],
        ]);
        $ticketModel->user()->associate($user);
        $this->mockTicketLocker($ticket, times: 2);

        $this->mock(TicketRepository::class)
            ->shouldReceive('getByTicket')
            ->andReturn($ticketModel)
            ->twice()
            ->shouldReceive('invalidTicket')
            ->twice();

        $pgtTicketModel = new PGTicket([
            'ticket' => 'PGT-123456',
        ]);

        $this->mock(PGTicketRepository::class)
            ->shouldReceive('applyTicket')
            ->andReturn($pgtTicketModel)
            ->twice();

        $this->mock(TicketGenerator::class)
            ->shouldReceive('generateOne')
            ->andReturn('pgtiou string')
            ->twice();

        $this->mock(PGTCaller::class)
            ->shouldReceive('call')
            ->andReturn(true)
            ->twice();

        $resp = $this->post(cas_route('v3.validate.proxy', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback']));
        $resp->assertHeader('Content-Type', 'application/xml');
        $this->assertMatchesXmlSnapshot($resp->getContent());

        $resp = $this->post(cas_route('v3.validate.proxy', ['ticket' => $ticket, 'service' => 'https://leo108.com', 'pgtUrl' => 'https://app1.com/pgtCallback', 'format' => 'JSON']));
        $resp->assertHeader('Content-Type', 'application/json');
        $this->assertMatchesJsonSnapshot($resp->getContent());
    }

    protected function mockTicketLocker(string $ticket, bool $release = true, int $times = 1)
    {
        $mock = $this->mock(TicketLocker::class)
            ->shouldReceive('acquireLock')
            ->with($ticket, 5000)
            ->andReturn(true)
            ->times($times);

        if ($release) {
            $mock->shouldReceive('releaseLock')
                ->with($ticket)
                ->times($times);
        }
    }

    protected function initUser(): User
    {
        $user = new User(['first_name' => 'Leo', 'last_name' => 'Chen', 'email' => 'root@leo108.com']);
        $user->save();

        return $user;
    }
}
