<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit\Console;

use ParaTest\Console\VersionProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ParaTest\Console\VersionProvider
 */
class VersionProviderTest extends TestCase
{
    public function testCreation(): void
    {
        $provider = new VersionProvider();
        $this->assertInstanceOf(VersionProvider::class, $provider);
    }

    public function testStaticCall(): void
    {
        $provider = new VersionProvider();
        $this->assertSame($provider::getVersion(), $provider->getParaTestVersion());
    }

    public function testComposerInstalledVersion(): void
    {
        $provider = new VersionProvider();
        $actual   = $provider->getComposerInstalledVersion('phpunit/phpunit');
        $this->assertIsString($actual, 'Version of phpunit package was found installed');

        // dev-master is included here as the phpunit package is checked and there is a dev-master used on travis
        $this->assertMatchesRegularExpression('~^dev-master|\d.\d.(.)+$~', $actual, 'Actual version number');

        $actual = $provider->getComposerInstalledVersion('foooo/barazzoraz');
        $this->assertNull($actual, 'No version for non-existent package');
    }

    public function testGitVersion(): void
    {
        $provider = new VersionProvider();
        $actual   = $provider->getGitVersion();
        $this->assertIsString($actual, 'Git is enabled and works');
        $this->assertMatchesRegularExpression('~^\d.\d(?:.\d+)?(?:-\d+-g[\da-f]+)?$~', $actual, 'Git gives a version');
    }
}
