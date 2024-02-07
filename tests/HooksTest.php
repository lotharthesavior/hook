<?php

namespace tests;

use Hook\Hooks;
use tests\stubs\ActionAndFilter;

/**
 * Class HooksTest
 */
class HooksTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @var Hooks
   */
    protected $hooks;

  /**
   * @var string
   */
    protected $testString_1 = 'lalllöäü123';

  /**
   * @var string
   */
    protected $testString_2 = 'lalll_§$§$&&//"?23';

  /**
   * @param $input
   *
   * @return string
   */
    public function hookTestString1($input): string
    {
        return $input . $this->testString_1;
    }

  /**
   * @param $input
   *
   * @return string
   */
    public function hookTestString2($input): string
    {
        return $input . $this->testString_2;
    }

  /**
   * test hooks
   */
    public function testHooks()
    {
        $this->hooks->addFilter('test', [$this, 'hookTestString1']);
        $this->hooks->addFilter('test', [$this, 'hookTestString2']);

        $lall = $this->hooks->applyFilters('test', '');

        self::assertSame($lall, $this->testString_1 . $this->testString_2);
    }

  /**
   * test hooks instance
   *
   * WARNING: you have to run "$this->testHooks()" first
   */
    public function testHooksInstance()
    {
        $lall = $this->hooks->applyFilters('test', '');

        self::assertSame($lall, $this->testString_1 . $this->testString_2);
    }

    public function testHasFunctions()
    {
        $hooks = Hooks::getInstance();

        self::assertSame(true, $hooks->removeAllFilters('testFilter'));
        self::assertSame(true, $hooks->removeAllActions('testAction'));

        self::assertFalse($hooks->hasFilter(''));
        self::assertFalse($hooks->hasFilter(' '));
        self::assertFalse($hooks->hasFilter('testFilter'));
        self::assertFalse($hooks->hasFilter('testFilter', 'time'));
        self::assertFalse($hooks->hasAction('testAction', 'time'));

        self::assertSame(true, $hooks->addFilter('testFilter', 'time'));
        self::assertSame(true, $hooks->addAction('testAction', 'time'));

        self::assertTrue($hooks->hasFilter('testFilter', 'time') !== false);
        self::assertTrue($hooks->hasAction('testAction', 'time') !== false);

        self::assertFalse($hooks->hasFilter('testFilter', 'print_r'));
        self::assertFalse($hooks->hasAction('testAction', 'print_r'));

        self::assertTrue($hooks->hasFilter('testFilter'));
        self::assertTrue($hooks->hasAction('testAction'));

        self::assertFalse($hooks->hasFilter('notExistingFilter'));
        self::assertFalse($hooks->hasAction('notExistingAction'));
    }

    public function testRemoveOneFunctions()
    {
        $hooks = Hooks::getInstance();

        $hooks->removeAllFilters('testFilter');
        $hooks->removeAllActions('testAction');

        self::assertFalse($hooks->hasFilter('testFilter', 'time'));
        self::assertFalse($hooks->hasAction('testAction', 'time'));

        $hooks->addFilter('testFilter', 'time');
        $hooks->addAction('testAction', 'time');

        self::assertFalse($hooks->removeFilter('testFilter', 'print_r'));
        self::assertFalse($hooks->removeAction('testAction', 'print_r'));

        self::assertTrue($hooks->hasFilter('testFilter', 'time') !== false);
        self::assertTrue($hooks->hasAction('testAction', 'time') !== false);

        self::assertTrue($hooks->removeFilter('testFilter', 'time'));
        self::assertTrue($hooks->removeAction('testAction', 'time'));

        self::assertFalse($hooks->hasFilter('testFilter', 'time'));
        self::assertFalse($hooks->hasAction('testAction', 'time'));
    }

    public function testRemoveAllFunctions()
    {
        $hooks = Hooks::getInstance();

        self::assertSame(true, $hooks->removeAllFilters('testFilter'));
        self::assertSame(true, $hooks->removeAllActions('testAction'));

        self::assertSame(true, $hooks->addFilter('testFilter', 'time', 10));
        self::assertSame(true, $hooks->addFilter('testFilter', 'print_r', 10));
        self::assertSame(true, $hooks->addFilter('testFilter', 'time', 25));
        self::assertSame(true, $hooks->addAction('testAction', 'time', 10));
        self::assertSame(true, $hooks->addAction('testAction', 'print_r', 10));
        self::assertSame(true, $hooks->addAction('testAction', 'time', 25));

        self::assertTrue($hooks->removeAllFilters('testFilter', 10));
        self::assertTrue($hooks->removeAllActions('testAction', 10));

        self::assertTrue($hooks->hasFilter('testFilter'));
        self::assertTrue($hooks->hasAction('testAction'));

        self::assertSame(25, $hooks->hasFilter('testFilter', 'time'));
        self::assertSame(25, $hooks->hasAction('testAction', 'time'));

        self::assertTrue($hooks->removeAllFilters('testFilter'));
        self::assertTrue($hooks->removeAllActions('testAction'));

        self::assertFalse($hooks->hasFilter('testFilter'));
        self::assertFalse($hooks->hasAction('testAction'));
    }

    public function testRunHookFunctions()
    {
        $hooks = Hooks::getInstance();

        self::assertSame(true, $hooks->removeAllFilters('testFilter'));
        self::assertSame(true, $hooks->removeAllActions('testAction'));

        self::assertSame(false, $hooks->doAction('testAction'));
        self::assertSame(false, $hooks->doActionRefArray('testNotExistingAction', []));
        self::assertSame('Foo', $hooks->applyFilters('testFilter', 'Foo'));

        self::assertSame(false, $hooks->doActionRefArray('testAction', ['test']));
        self::assertSame('Foo', $hooks->applyFiltersRefArray('testFilter', ['Foo']));

        $mock = $this->createMock(ActionAndFilter::class);
        $mock->expects(self::exactly(4))->method('doSomeAction');
        $mock->expects(self::exactly(10))->method('applySomeFilter')->willReturn('foo');

        self::assertSame(true, $hooks->addAction('testAction', [$mock, 'doSomeAction']));
        self::assertSame(true, $hooks->addFilter('testFilter', [$mock, 'applySomeFilter']));

        self::assertSame(8, $hooks->didAction('testAction'));
        self::assertSame(true, $hooks->doAction('testAction'));
        self::assertSame(9, $hooks->didAction('testAction'));
        self::assertSame('foo', $hooks->applyFilters('testFilter', 'Foo'));

        self::assertSame(true, $hooks->addFilter('all', [$mock, 'applySomeFilter']));

        self::assertSame(false, $hooks->doAction('notExistingAction'));
        self::assertSame('Foo', $hooks->applyFilters('notExistingFilter', 'Foo')); // unmodified value

        self::assertSame(true, $hooks->doAction('testAction', (object)['foo' => 'bar']));
        self::assertSame(true, $hooks->doAction('testAction', 'param1', 'param2', 'param3', 'param4'));
        self::assertSame(true, $hooks->doActionRefArray('testAction', ['test']));
        self::assertSame('foo', $hooks->applyFilters('testFilter', 'Foo'));
        self::assertSame('foo', $hooks->applyFiltersRefArray('testFilter', ['Foo']));
    }

    public function testRunShortcodeFunctions()
    {
        require_once __DIR__ . '/HooksFooBar.php';

        $hooks = Hooks::getInstance();

        self::assertSame(true, $hooks->removeAllShortcodes());

        self::assertSame('testAction', $hooks->doShortcode('testAction'));

        $testClass = new HooksFooBar('foo');
        self::assertSame(true, $hooks->addShortcode('testAction', [$testClass, 'doSomethingFunction']));
        self::assertTrue($hooks->shortcodeExists('testAction'));

        self::assertSame(
            'foo bar <li class="">content</li>',
            $hooks->doShortcode('foo bar [testAction foo="bar"]content[/testAction]'),
        );

        self::assertSame(
            'foo bar ',
            $hooks->stripShortcodes('foo bar [testAction foo="bar"]content[/testAction]'),
        );

        self::assertSame(true, $hooks->removeShortcode('testAction'));
        self::assertSame(false, $hooks->shortcodeExists('testAction'));
    }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
    protected function setUp(): void
    {
        $this->hooks = Hooks::getInstance();
    }
}
