<?php

namespace Unit;

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

        // Assert
        $this->expectNotToPerformAssertions();
    }
}
