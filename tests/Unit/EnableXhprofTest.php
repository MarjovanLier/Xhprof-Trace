<?php

declare(strict_types=1);

namespace MarjovanLier\XhprofTrace\Tests\Unit\Unit;

use MarjovanLier\XhprofTrace\Trace;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \MarjovanLier\XhprofTrace\Trace::enableXhprof
 */
final class EnableXhprofTest extends TestCase
{
    private const MAIN = 'main()';

    /**
     * This test case is designed to test the functionality of the `enableXhprof` method in the `Trace` class.
     */
    public function testEnableXhprofWithNoErrors(): void
    {
        // Arrange
        // Act
        Trace::enableXhprof();
        /**
         * @var array<string, array<string, int|string>> $xhprofDisable
         */
        $xhprofDisable = xhprof_disable();

        // Assert
        $this->assertCount(1, $xhprofDisable);
        $this->assertArrayNotHasKey(self::MAIN . '==>xhprof_disable', $xhprofDisable);
        $this->assertArrayHasKey(self::MAIN, $xhprofDisable);
        $this->assertArrayHasKey('ct', $xhprofDisable[self::MAIN]);
        $this->assertArrayHasKey('wt', $xhprofDisable[self::MAIN]);
        $this->assertArrayHasKey('cpu', $xhprofDisable[self::MAIN]);
        $this->assertArrayHasKey('mu', $xhprofDisable[self::MAIN]);
        $this->assertArrayHasKey('pmu', $xhprofDisable[self::MAIN]);
    }
}
