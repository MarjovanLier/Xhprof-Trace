<?php

declare(strict_types=1);

namespace MarjovanLier\XhprofTrace\Tests\Unit\Unit;

use MarjovanLier\XhprofTrace\Trace;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \MarjovanLier\XhprofTrace\Trace::setProfilesDir
 */
final class ProfilesDirTest extends TestCase
{
    /**
     * This method tests the functionality of setting the profiles directory in the Trace class.
     *
     * @return void
     */
    public function testSetProfilesDir(): void
    {
        // Set a temporary directory path for testing
        $tempDir = sys_get_temp_dir();

        // Call the method to set the profiles directory
        Trace::setProfilesDir($tempDir);

        // Assert that the profiles directory was set correctly
        $this->assertEquals($tempDir, (new Trace())->getProfilesDir());
    }
}
