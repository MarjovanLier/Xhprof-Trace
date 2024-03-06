<?php

declare(strict_types=1);

namespace MarjovanLier\XhprofTrace\Tests\Unit\Unit;

use MarjovanLier\XhprofTrace\Trace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 *
 * @covers \MarjovanLier\XhprofTrace\Trace::setProfilesDir
 */
final class ProfilesDirTest extends TestCase
{
    public function testSetProfilesDir(): void
    {
        // Set a temporary directory path for testing
        $tempDir = sys_get_temp_dir();

        // Call the method to set the profiles directory
        Trace::setProfilesDir($tempDir);

        // Use reflection to access the private property
        $reflectionProperty = (new ReflectionClass(Trace::class))->getProperty('profilesDir');
        $reflectionProperty->setAccessible(true);

        // Assert that the profiles directory was set correctly
        $this->assertEquals($tempDir, $reflectionProperty->getValue());
    }
}
