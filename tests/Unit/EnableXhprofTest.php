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
        $this->assertArrayNotHasKey(
            self::MAIN . '==>xhprof_disable',
            $xhprofDisable,
            'The main() function should not be in the trace'
        );
        $this->assertArrayHasKey(self::MAIN, $xhprofDisable, 'The main() function should be in the trace');
        $this->assertArrayHasKey('ct', $xhprofDisable[self::MAIN], 'CPU time should be in the trace');
        $this->assertGreaterThan(0, $xhprofDisable[self::MAIN]['ct'], 'CPU time should be greater than 0');
        $this->assertArrayHasKey('wt', $xhprofDisable[self::MAIN], 'Wall time should be in the trace');
        $this->assertGreaterThan(0, $xhprofDisable[self::MAIN]['wt'], 'Wall time should be greater than 0');
        $this->assertArrayHasKey('cpu', $xhprofDisable[self::MAIN], 'CPU time should be in the trace');
        $this->assertGreaterThan(0, $xhprofDisable[self::MAIN]['cpu'], 'CPU time should be greater than 0');
        $this->assertArrayHasKey('mu', $xhprofDisable[self::MAIN], 'Memory usage should be in the trace');
        $this->assertGreaterThan(0, $xhprofDisable[self::MAIN]['mu'], 'Memory usage should be greater than 0');
        $this->assertArrayHasKey('pmu', $xhprofDisable[self::MAIN], 'Peak memory usage should be in the trace');
        $this->assertGreaterThanOrEqual(
            0,
            $xhprofDisable[self::MAIN]['pmu'],
            'Peak memory usage should be greater than 0'
        );
    }
}
