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
        $this->assertArrayNotHasKey('main()==>xhprof_disable', $xhprofDisable);
        $this->assertArrayHasKey('main()', $xhprofDisable);
        $this->assertArrayHasKey('ct', $xhprofDisable['main()']);
        $this->assertArrayHasKey('wt', $xhprofDisable['main()']);
        $this->assertArrayHasKey('cpu', $xhprofDisable['main()']);
        $this->assertArrayHasKey('mu', $xhprofDisable['main()']);
        $this->assertArrayHasKey('pmu', $xhprofDisable['main()']);
    }
}
