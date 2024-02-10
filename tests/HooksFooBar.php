<?php

namespace tests;

use Hook\Shortcode;
use PHPUnit\Framework\TestCase;

/**
 * Class HooksFooBar
 */
class HooksFooBar extends TestCase
{
    protected $foo = '';

    /**
     * @param        $attrs
     * @param string $content
     *
     * @return string
     */
    public function doSomethingFunction($attrs, string $content = ''): string
    {
      // init
        $foo = '';

        extract(
            Shortcode::shortcodeAttrs(
                [
                'foo',
                ],
                $attrs
            ),
            EXTR_OVERWRITE
        );

        return $this->foo . '<li class="' . $foo . '">' . $content . '</li>';
    }
}
