<?php

declare(strict_types=1);

namespace tests;

use Hook\Shortcode;
use PHPUnit\Framework\TestCase;

/**
 * Class HooksTest
 */
class HooksShortcodeStrictTest extends TestCase
{
    /**
     * @param array $attrs
     *
     * @return string
     */
    public function parseYoutube(array $attrs): string
    {
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
            Shortcode::shortcodeAttrs(
                [
                    'autoplay',
                    'noControls',
                    'list' => null,
                    'id' => null,
                    'width' => 640,
                    'height' => 390,
                    'color' => 'red',
                    'theme' => 'dark',
                    'start' => 0,
                ],
                $attrs
            ),
            EXTR_OVERWRITE
        );

        if (!$id && !$list) {
            return 'Missing id or list parameter';
        }

        $h = '<iframe'
            . ' type="text/html"'
            . ' frameborder=0'
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
        Shortcode::addShortcode('youtube', [$this, 'parseYoutube']);

        $default_content = '[youtube id=iCUV3iv9xOs color=white theme=light]';
        $parsed_content = Shortcode::doShortcode($default_content);

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
}
