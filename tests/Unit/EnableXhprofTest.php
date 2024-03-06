<?php

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
    public function test_enable_xhprof_with_no_errors(): void
    {
        // Arrange

        // Act
        Trace::enableXhprof();
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
