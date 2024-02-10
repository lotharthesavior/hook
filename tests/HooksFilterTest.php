<?php

namespace tests;

use Hook\Filter;
use PHPUnit\Framework\TestCase;

/**
 * Class HooksTest
 */
class HooksFilterTest extends TestCase
{
    public function testFilter(): void
    {
        Filter::addFilter(
            'foo',
            function ($content) {
                return '<b>' . $content . '</b>';
            }
        );

        self::assertSame('<b>Hello world</b>', Filter::applyFilters('foo', 'Hello world'));
    }
}
