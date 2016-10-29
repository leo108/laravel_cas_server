<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/27
 * Time: 07:08
 */

namespace Leo108\CAS\Services;

use Mockery;
use TestCase;

class TicketGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = Mockery::mock(TicketGenerator::class)
            ->makePartial()
            ->shouldReceive('generateOne')
            ->andReturn('some string')
            ->times(5)
            ->getMock();

        $funcObj = Mockery::mock()
            ->shouldReceive('check')
            ->andReturnValues([false, false, false, false, true])
            ->times(5)
            ->getMock();

        $this->assertNotFalse($generator->generate(32, 'ST-', [$funcObj, 'check'], 10));
    }

    public function testGenerateButAlwaysCheckFailed()
    {
        $generator = Mockery::mock(TicketGenerator::class)
            ->makePartial()
            ->shouldReceive('generateOne')
            ->andReturn('some string')
            ->times(10)
            ->getMock();

        $funcObj = Mockery::mock()
            ->shouldReceive('check')
            ->andReturn(false)
            ->times(10)
            ->getMock();

        $this->assertFalse($generator->generate(32, 'ST-', [$funcObj, 'check'], 10));
    }

    public function testGenerateOne()
    {
        $totalLength = 32;
        $generator   = app(TicketGenerator::class);
        $prefixArr   = ['PGTIOU-', 'PGT-', 'ST-', 'PT-'];
        foreach ($prefixArr as $prefix) {
            $ticket = $generator->generateOne($totalLength, $prefix);
            $this->assertEquals($totalLength, strlen($ticket));
            $this->assertEquals(0, strpos($ticket, $prefix));
        }
    }
}
