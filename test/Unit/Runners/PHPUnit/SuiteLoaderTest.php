<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit\Runners\PHPUnit;

use InvalidArgumentException;
use ParaTest\Runners\PHPUnit\Options;
use ParaTest\Runners\PHPUnit\Suite;
use ParaTest\Runners\PHPUnit\SuiteLoader;
use ParaTest\Tests\TestBase;
use RuntimeException;

use function array_keys;
use function array_shift;
use function count;
use function strstr;

class SuiteLoaderTest extends TestBase
{
    public function testConstructor(): void
    {
        $options = new Options(['group' => 'group1']);
        $loader  = new SuiteLoader($options);
        $this->assertEquals($options, $this->getObjectValue($loader, 'options'));
    }

    public function testOptionsCanBeNull(): void
    {
        $loader = new SuiteLoader();
        $this->assertNull($this->getObjectValue($loader, 'options'));
    }

    public function testLoadThrowsExceptionWithInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $loader = new SuiteLoader();
        $loader->load('/path/to/nowhere');
    }

    public function testLoadBarePathWithNoPathAndNoConfiguration(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No path or configuration provided (tests must end with Test.php)');

        $loader = new SuiteLoader();
        $loader->load();
    }

    public function testLoadTestsuiteFileFromConfig(): void
    {
        $options = new Options(
            ['configuration' => $this->fixture('phpunit-file.xml'), 'testsuite' => ['ParaTest Fixtures']]
        );
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = 1;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteFilesFromConfigWhileIgnoringExcludeTag(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-excluded-including-file.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = 1;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteFilesFromDirFromConfigWhileRespectingExcludeTag(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-excluded-including-dir.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = 2;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteFilesFromConfigWhileIncludingAndExcludingTheSameDirectory(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-excluded-including-excluding-same-dir.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = 1;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteFilesFromConfig(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-multifile.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = 2;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteWithDirectory(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-passing.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'passing-tests'));
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteWithDirectories(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-multidir.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'passing-tests')) +
            count($this->findTests(FIXTURES . DS . 'failing-tests'));
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteWithFilesDirsMixed(): void
    {
        $options = new Options(
            ['configuration' => $this->fixture('phpunit-files-dirs-mix.xml'), 'testsuite' => ['ParaTest Fixtures']]
        );
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'failing-tests')) + 2;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteWithNestedSuite(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-files-dirs-mix-nested.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'passing-tests')) +
            count($this->findTests(FIXTURES . DS . 'failing-tests')) + 1;
        $this->assertCount($expected, $files);
    }

    public function testLoadTestsuiteWithDuplicateFilesDirMixed(): void
    {
        $options = new Options([
            'configuration' => $this->fixture('phpunit-files-dirs-mix-duplicates.xml'),
            'testsuite' => ['ParaTest Fixtures'],
        ]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'passing-tests')) + 1;
        $this->assertCount($expected, $files);
    }

    public function testLoadSuiteFromConfig(): void
    {
        $options = new Options(['configuration' => $this->fixture('phpunit-passing.xml')]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'passing-tests'));
        $this->assertCount($expected, $files);
    }

    public function testLoadSuiteFromConfigWithMultipleDirs(): void
    {
        $options = new Options(['configuration' => $this->fixture('phpunit-multidir.xml')]);
        $loader  = new SuiteLoader($options);
        $loader->load();
        $files = $this->getObjectValue($loader, 'files');

        $expected = count($this->findTests(FIXTURES . DS . 'passing-tests')) +
            count($this->findTests(FIXTURES . DS . 'failing-tests'));
        $this->assertCount($expected, $files);
    }

    public function testLoadSuiteFromConfigWithBadSuitePath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Suite path ./nope/ could not be found');

        $options = new Options(['configuration' => $this->fixture('phpunit-non-existent-testsuite-dir.xml')]);
        $loader  = new SuiteLoader($options);
        $loader->load();
    }

    public function testLoadFileGetsPathOfFile(): void
    {
        $path  = $this->fixture('failing-tests/UnitTestWithClassAnnotationTest.php');
        $paths = $this->getLoadedPaths($path);
        $this->assertEquals($path, array_shift($paths));
    }

    protected function getLoadedPaths(string $path, ?SuiteLoader $loader = null): array
    {
        $loader = $loader ?: new SuiteLoader();
        $loader->load($path);
        $loaded = $this->getObjectValue($loader, 'loadedSuites');

        return array_keys($loaded);
    }

    public function testLoadFileShouldLoadFileWhereNameDoesNotEndInTest(): void
    {
        $path  = $this->fixture('passing-tests/TestOfUnits.php');
        $paths = $this->getLoadedPaths($path);
        $this->assertEquals($path, array_shift($paths));
    }

    public function testLoadDirGetsPathOfAllTestsWithKeys(): array
    {
        $path  = $this->fixture('passing-tests');
        $files = $this->findTests($path);

        $loader = new SuiteLoader();
        $loader->load($path);
        $loaded = $this->getObjectValue($loader, 'loadedSuites');
        foreach ($loaded as $path => $test) {
            $this->assertContains($path, $files);
        }

        return $loaded;
    }

    /**
     * @depends testLoadDirGetsPathOfAllTestsWithKeys
     */
    public function testFirstParallelSuiteHasCorrectFunctions(array $paraSuites): void
    {
        $first     = $this->suiteByPath('GroupsTest.php', $paraSuites);
        $functions = $first->getFunctions();
        $this->assertCount(5, $functions);
        $this->assertEquals('testTruth', $functions[0]->getName());
        $this->assertEquals('testFalsehood', $functions[1]->getName());
        $this->assertEquals('testArrayLength', $functions[2]->getName());
        $this->assertEquals('testStringLength', $functions[3]->getName());
        $this->assertEquals('testAddition', $functions[4]->getName());
    }

    private function suiteByPath(string $path, array $paraSuites): Suite
    {
        foreach ($paraSuites as $completePath => $suite) {
            if (strstr($completePath, $path)) {
                return $suite;
            }
        }

        throw new RuntimeException("Suite $path not found.");
    }

    /**
     * @depends testLoadDirGetsPathOfAllTestsWithKeys
     */
    public function testSecondParallelSuiteHasCorrectFunctions(array $paraSuites): void
    {
        $second    = $this->suiteByPath('LegacyNamespaceTest.php', $paraSuites);
        $functions = $second->getFunctions();
        $this->assertCount(1, $functions);
    }

    public function testGetTestMethodsOnlyReturnsMethodsOfGroupIfOptionIsSpecified(): void
    {
        $options    = new Options(['group' => 'group1']);
        $loader     = new SuiteLoader($options);
        $groupsTest = $this->fixture('passing-tests/GroupsTest.php');
        $loader->load($groupsTest);
        $methods = $loader->getTestMethods();
        $this->assertCount(2, $methods);
        $this->assertEquals('testTruth', $methods[0]->getName());
        $this->assertEquals('testFalsehood', $methods[1]->getName());
    }

    public function testGetTestMethodsOnlyReturnsMethodsOfClassGroup(): void
    {
        $options    = new Options(['group' => 'group4']);
        $loader     = new SuiteLoader($options);
        $groupsTest = $this->fixture('passing-tests/GroupsTest.php');
        $loader->load($groupsTest);
        $methods = $loader->getTestMethods();
        $this->assertCount(1, $loader->getSuites());
        $this->assertCount(5, $methods);
    }

    public function testGetSuitesForNonMatchingGroups(): void
    {
        $options    = new Options(['group' => 'non-existent']);
        $loader     = new SuiteLoader($options);
        $groupsTest = $this->fixture('passing-tests/GroupsTest.php');
        $loader->load($groupsTest);
        $this->assertCount(0, $loader->getSuites());
        $this->assertCount(0, $loader->getTestMethods());
    }

    public function testLoadIgnoresFilesWithoutClasses(): void
    {
        $loader           = new SuiteLoader();
        $fileWithoutClass = $this->fixture('special-classes/FileWithoutClass.php');
        $loader->load($fileWithoutClass);
        $this->assertCount(0, $loader->getTestMethods());
    }

    public function testExecutableTestsForFunctionalModeUse(): void
    {
        $path   = $this->fixture('passing-tests/DependsOnChain.php');
        $loader = new SuiteLoader();
        $loader->load($path);
        $tests = $loader->getTestMethods();
        $this->assertCount(2, $tests);
        $testMethod = $tests[0];
        $this->assertEquals($testMethod->getName(), 'testOneA|testOneBDependsOnA|testOneCDependsOnB');
        $testMethod = $tests[1];
        $this->assertEquals($testMethod->getName(), 'testTwoA|testTwoBDependsOnA');
    }
}
