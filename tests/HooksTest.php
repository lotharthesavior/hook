<?php

namespace tests;

use Hook\Action;
use Hook\Filter;
use Hook\Hooks;
use Hook\Shortcode;
use PHPUnit\Framework\TestCase;
use tests\stubs\ActionAndFilter;

/**
 * Class HooksTest
 */
class HooksTest extends TestCase
{
    protected string $testString_1 = 'lalllöäü123';

    protected string $testString_2 = 'lalll_§$§$&&//"?23';

    public function hookTestString1(string $input): string
    {
        return $input . $this->testString_1;
    }

    public function hookTestString2(string $input): string
    {
        return $input . $this->testString_2;
    }

    /**
     * test hooks
     */
    public function testHooks()
    {
        Filter::addFilter('test', [$this, 'hookTestString1']);
        Filter::addFilter('test', [$this, 'hookTestString2']);

        $lall = Filter::applyFilters('test', '');

        self::assertSame($lall, $this->testString_1 . $this->testString_2);
    }

    /**
     * test hooks instance
     *
     * WARNING: you have to run "$this->testHooks()" first
     */
    public function testHooksInstance()
    {
        $lall = Filter::applyFilters('test', '');

        self::assertSame($lall, $this->testString_1 . $this->testString_2);
    }

    public function testHasFunctions()
    {
        self::assertSame(true, Filter::removeAllFilters('testFilter'));
        self::assertSame(true, Action::removeAllActions('testAction'));

        self::assertFalse(Filter::hasFilter(''));
        self::assertFalse(Filter::hasFilter(' '));
        self::assertFalse(Filter::hasFilter('testFilter'));
        self::assertFalse(Filter::hasFilter('testFilter', 'time'));
        self::assertFalse(Action::hasAction('testAction', 'time'));

        self::assertSame(true, Filter::addFilter('testFilter', 'time'));
        self::assertSame(true, Action::addAction('testAction', 'time'));

        self::assertTrue(Filter::hasFilter('testFilter', 'time') !== false);
        self::assertTrue(Action::hasAction('testAction', 'time') !== false);

        self::assertFalse(Filter::hasFilter('testFilter', 'print_r'));
        self::assertFalse(Action::hasAction('testAction', 'print_r'));

        self::assertTrue(Filter::hasFilter('testFilter'));
        self::assertTrue(Action::hasAction('testAction'));

        self::assertFalse(Filter::hasFilter('notExistingFilter'));
        self::assertFalse(Action::hasAction('notExistingAction'));
    }

    public function testRemoveOneFunctions()
    {
        Filter::removeAllFilters('testFilter');
        Action::removeAllActions('testAction');

        self::assertFalse(Filter::hasFilter('testFilter', 'time'));
        self::assertFalse(Action::hasAction('testAction', 'time'));

        Filter::addFilter('testFilter', 'time');
        Action::addAction('testAction', 'time');

        self::assertFalse(Filter::removeFilter('testFilter', 'print_r'));
        self::assertFalse(Action::removeAction('testAction', 'print_r'));

        self::assertTrue(Filter::hasFilter('testFilter', 'time') !== false);
        self::assertTrue(Action::hasAction('testAction', 'time') !== false);

        self::assertTrue(Filter::removeFilter('testFilter', 'time'));
        self::assertTrue(Action::removeAction('testAction', 'time'));

        self::assertFalse(Filter::hasFilter('testFilter', 'time'));
        self::assertFalse(Action::hasAction('testAction', 'time'));
    }

    public function testRemoveAllFunctions()
    {
        self::assertSame(true, Filter::removeAllFilters('testFilter'));
        self::assertSame(true, Action::removeAllActions('testAction'));

        self::assertSame(true, Filter::addFilter('testFilter', 'time', 10));
        self::assertSame(true, Filter::addFilter('testFilter', 'print_r', 10));
        self::assertSame(true, Filter::addFilter('testFilter', 'time', 25));
        self::assertSame(true, Action::addAction('testAction', 'time', 10));
        self::assertSame(true, Action::addAction('testAction', 'print_r', 10));
        self::assertSame(true, Action::addAction('testAction', 'time', 25));

        self::assertTrue(Filter::removeAllFilters('testFilter', 10));
        self::assertTrue(Action::removeAllActions('testAction', 10));

        self::assertTrue(Filter::hasFilter('testFilter'));
        self::assertTrue(Action::hasAction('testAction'));

        self::assertSame(25, Filter::hasFilter('testFilter', 'time'));
        self::assertSame(25, Action::hasAction('testAction', 'time'));

        self::assertTrue(Filter::removeAllFilters('testFilter'));
        self::assertTrue(Action::removeAllActions('testAction'));

        self::assertFalse(Filter::hasFilter('testFilter'));
        self::assertFalse(Action::hasAction('testAction'));
    }

    public function testRunHookFunctions()
    {
        self::assertSame(true, Filter::removeAllFilters('testFilter'));
        self::assertSame(true, Action::removeAllActions('testAction'));

        self::assertSame(false, Action::doAction('testAction'));
        self::assertSame(false, Action::doActionRefArray('testNotExistingAction', []));
        self::assertSame('Foo', Filter::applyFilters('testFilter', 'Foo'));

        self::assertSame(false, Action::doActionRefArray('testAction', ['test']));
        self::assertSame('Foo', Filter::applyFiltersRefArray('testFilter', ['Foo']));

        $mock = $this->createMock(ActionAndFilter::class);
        $mock->expects(self::exactly(4))->method('doSomeAction');
        $mock->expects(self::exactly(10))->method('applySomeFilter')->willReturn('foo');

        self::assertSame(true, Action::addAction('testAction', [$mock, 'doSomeAction']));
        self::assertSame(true, Filter::addFilter('testFilter', [$mock, 'applySomeFilter']));

        self::assertSame(8, Action::didAction('testAction'));
        self::assertSame(true, Action::doAction('testAction'));
        self::assertSame(9, Action::didAction('testAction'));
        self::assertSame('foo', Filter::applyFilters('testFilter', 'Foo'));

        self::assertSame(true, Filter::addFilter('all', [$mock, 'applySomeFilter']));

        self::assertSame(false, Action::doAction('notExistingAction'));
        self::assertSame('Foo', Filter::applyFilters('notExistingFilter', 'Foo')); // unmodified value

        self::assertSame(true, Action::doAction('testAction', (object)['foo' => 'bar']));
        self::assertSame(true, Action::doAction('testAction', 'param1', 'param2', 'param3', 'param4'));
        self::assertSame(true, Action::doActionRefArray('testAction', ['test']));
        self::assertSame('foo', Filter::applyFilters('testFilter', 'Foo'));
        self::assertSame('foo', Filter::applyFiltersRefArray('testFilter', ['Foo']));
    }

    public function testRunShortcodeFunctions()
    {
        require_once __DIR__ . '/HooksFooBar.php';

        self::assertSame(true, Shortcode::removeAllShortcodes());

        self::assertSame('testAction', Shortcode::doShortcode('testAction'));

        $testClass = new HooksFooBar('foo');
        self::assertSame(true, Shortcode::addShortcode('testAction', [$testClass, 'doSomethingFunction']));
        self::assertTrue(Shortcode::shortcodeExists('testAction'));

        self::assertSame(
            'foo bar <li class="">content</li>',
            Shortcode::doShortcode('foo bar [testAction foo="bar"]content[/testAction]'),
        );

        self::assertSame(
            'foo bar ',
            Shortcode::stripShortcodes('foo bar [testAction foo="bar"]content[/testAction]'),
        );

        self::assertSame(true, Shortcode::removeShortcode('testAction'));
        self::assertSame(false, Shortcode::shortcodeExists('testAction'));
    }
}
