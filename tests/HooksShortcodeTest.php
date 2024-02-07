<?php

namespace tests;

use Hook\Hooks;

/**
 * Class HooksTest
 */
class HooksShortcodeTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @var Hooks
   */
    protected $hooks;

  /**
   * @param array $attrs
   *
   * @return string
   */
    public function parseYoutube(array $attrs): string
    {
        $hooks = Hooks::getInstance();

      // init
        $autoplay = '';
        $noControls = '';
        $list = '';
        $id = '';
        $width = '';
        $height = '';
        $color = '';
        $theme = '';
        $start = '';

        extract(
            $hooks->shortcodeAttrs(
                [
                'autoplay',
                'noControls',
                'list'   => null,
                'id'     => null,
                'width'  => 640,
                'height' => 390,
                'color'  => 'red',
                'theme'  => 'dark',
                'start'  => 0,
                ],
                $attrs
            ),
            EXTR_OVERWRITE
        );

        if (!$id && !$list) {
            return 'Missing id or list parameter';
        }

        $h = '<iframe type="text/html" frameborder=0'
            . ' width=' . $width
            . ' height=' . $height
            . ' src="http://www.youtube.com/embed';
        if ($id) {
            $h .= '/' . $id;
        }
        $h .= '?color=' . $color
            . '&theme=' . $theme
            . '&autoplay=' . (int)$autoplay
            . '&controls=' . (int)!$noControls;
        if ($list) {
            $h .= '&listType=playlist&list=' . $list;
        } else {
            $h .= '&start=' . $start;
        }
        $h .= '" />';

        return $h;
    }

    public function testShortcode()
    {
        $hooks = Hooks::getInstance();
        $hooks->addShortcode('youtube', [$this, 'parseYoutube']);

        $default_content = '[youtube id=iCUV3iv9xOs color=white theme=light]';
        $parsed_content = $hooks->doShortcode($default_content);

        self::assertSame(
            '<iframe'
            . ' type="text/html"'
            . ' frameborder=0'
            . ' width=640'
            . ' height=390'
            . ' src="http://www.youtube.com/embed/iCUV3iv9xOs?color=white&theme=light&autoplay=0&controls=1&start=0"'
            . ' />',
            $parsed_content,
        );
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
