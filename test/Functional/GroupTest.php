<?php

declare(strict_types=1);

namespace ParaTest\Tests\Functional;

class GroupTest extends FunctionalTestBase
{
    /** @var ParaTestInvoker */
    private $invoker;

    public function setUp(): void
    {
        parent::setUp();
        $this->invoker = new ParaTestInvoker(
            $this->fixture('passing-tests/GroupsTest.php'),
            BOOTSTRAP
        );
    }

    public function testGroupSwitchOnlyExecutesThoseGroups()
    {
        $proc = $this->invoker->execute(['group' => 'group1']);
        $this->assertMatchesRegularExpression('/OK \(2 tests, 2 assertions\)/', $proc->getOutput());
    }

    public function testExcludeGroupSwitchDontExecuteThatGroup()
    {
        $proc = $this->invoker->execute(['exclude-group' => 'group1']);

        $this->assertMatchesRegularExpression('/OK \(3 tests, 3 assertions\)/', $proc->getOutput());
    }

    public function testGroupSwitchExecutesGroupsUsingShortOption()
    {
        $proc = $this->invoker->execute(['g' => 'group1']);
        $this->assertMatchesRegularExpression('/OK \(2 tests, 2 assertions\)/', $proc->getOutput());
    }

    public function testGroupSwitchOnlyExecutesThoseGroupsInFunctionalMode()
    {
        $proc = $this->invoker->execute(['functional', 'g' => 'group1']);
        $this->assertMatchesRegularExpression('/OK \(2 tests, 2 assertions\)/', $proc->getOutput());
    }

    public function testGroupSwitchOnlyExecutesThoseGroupsWhereTestHasMultipleGroups()
    {
        $proc = $this->invoker->execute(['functional', 'group' => 'group3']);
        $this->assertMatchesRegularExpression('/OK \(1 test, 1 assertion\)/', $proc->getOutput());
    }

    public function testGroupsSwitchExecutesMultipleGroups()
    {
        $proc = $this->invoker->execute(['functional', 'group' => 'group1,group3']);
        $this->assertMatchesRegularExpression('/OK \(3 tests, 3 assertions\)/', $proc->getOutput());
    }
}
