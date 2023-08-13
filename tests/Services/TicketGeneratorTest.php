<?php

namespace Leo108\Cas\Tests\Services;

use Leo108\Cas\Services\TicketGenerator;
use Leo108\Cas\Tests\TestCase;

class TicketGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $count = 0;
        $generator = new TicketGenerator();

        $this->assertNotFalse($generator->generate(32, 'ST-', function () use (&$count): bool {
            $count++;

            return $count > 5;
        }, 10));

        $this->assertEquals(6, $count);
    }

    public function testGenerateButAlwaysCheckFailed()
    {
        $generator = new TicketGenerator();

        $this->assertFalse($generator->generate(32, 'ST-', fn (): bool => false, 10));
    }

    public function testGenerateOne()
    {
        $totalLength = 32;
        $generator = app(TicketGenerator::class);
        $prefixArr = ['PGTIOU-', 'PGT-', 'ST-', 'PT-'];
        foreach ($prefixArr as $prefix) {
            $ticket = $generator->generateOne($totalLength, $prefix);
            $this->assertEquals($totalLength, strlen($ticket));
            $this->assertEquals(0, strpos($ticket, $prefix));
        }
    }
}
