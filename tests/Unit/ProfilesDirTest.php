<?php

namespace Unit;

use MarjovanLier\XhprofTrace\Trace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 *
 * @covers \MarjovanLier\XhprofTrace\Trace::profilesDir
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
        $property = (new ReflectionClass(Trace::class))->getProperty('profilesDir');
        $property->setAccessible(true);

        // Assert that the profiles directory was set correctly
        $this->assertEquals($tempDir, $property->getValue());
    }
}
