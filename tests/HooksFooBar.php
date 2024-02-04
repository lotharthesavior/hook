<?php

namespace tests;

/**
 * Class HooksFooBar
 */
class HooksFooBar extends \PHPUnit\Framework\TestCase
{
  protected $foo = '';

  /**
   * @param        $attrs
   * @param string $content
   *
   * @return string
   */
  public function doSomethingFunction($attrs, $content = '')
  {
    // init
    $foo = '';

    extract(
        \voku\helper\Hooks::getInstance()->shortcode_atts(
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
