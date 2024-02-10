<?php

namespace Hook;

class Shortcode extends Filter
{
    /**
     * Container for storing shortcode tags and their hook to call for the shortcode
     *
     * @var array
     */
    public static array $shortcodeTags = [];

    /**
     * Add hook for shortcode tag.
     *
     * There can only be one hook for each shortcode. Which means that if another
     * plugin has a similar shortcode, it will override yours or yours will override
     * theirs depending on which order the plugins are included and/or ran.
     *
     * Simplest example of a shortcode tag using the API:
     *
     * // [footag foo="bar"]
     * function footag_func($attrs) {
     *  return "foo = {$attrs[foo]}";
     * }
     * addShortcode('footag', 'footag_func');
     *
     * Example with nice attribute defaults:
     *
     * // [bartag foo="bar"]
     * function bartag_func($attrs) {
     *  $args = shortcodeAttrs(array(
     *    'foo' => 'no foo',
     *    'baz' => 'default baz',
     *  ), $attrs);
     *
     *  return "foo = {$args['foo']}";
     * }
     * addShortcode('bartag', 'bartag_func');
     *
     * Example with enclosed content:
     *
     * // [baztag]content[/baztag]
     * function baztag_func($attrs, $content='') {
     *  return "content = $content";
     * }
     * addShortcode('baztag', 'baztag_func');
     *
     * @param string $tag Shortcode tag to be searched in post content.
     * @param callable $function Hook to run when shortcode is found.
     *
     * @return bool
     */
    public static function addShortcode(string $tag, callable $function): bool
    {
        if (is_callable($function)) {
            self::$shortcodeTags[$tag] = $function;

            return true;
        }

        return false;
    }

    /**
     * Removes hook for shortcode.
     *
     * @param string $tag shortcode tag to remove hook for.
     *
     * @return bool
     */
    public static function removeShortcode(string $tag): bool
    {
        if (isset(self::$shortcodeTags[$tag])) {
            unset(self::$shortcodeTags[$tag]);

            return true;
        }

        return false;
    }

    /**
     * This function is simple, it clears all the shortcode tags by replacing the
     * shortcodes by an empty array. This is actually a very efficient method
     * for removing all shortcodes.
     *
     * @return bool
     */
    public static function removeAllShortcodes(): bool
    {
        self::$shortcodeTags = [];

        return true;
    }

    /**
     * Whether a registered shortcode exists named $tag
     *
     * @param string $tag
     *
     * @return bool
     */
    public static function shortcodeExists(string $tag): bool
    {
        return array_key_exists($tag, self::$shortcodeTags);
    }

    /**
     * Whether the passed content contains the specified shortcode.
     *
     * @param string $content
     * @param string $tag
     *
     * @return bool
     */
    public static function hasShortcode(string $content, string $tag): bool
    {
        if (!str_contains($content, '[')) {
            return false;
        }

        if (self::shortcodeExists($tag)) {
            preg_match_all('/' . self::getShortcodeRegex() . '/s', $content, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                return false;
            }

            foreach ($matches as $shortcode) {
                if ($tag === $shortcode[2]) {
                    return true;
                }

                if (!empty($shortcode[5]) && self::hasShortcode($shortcode[5], $tag)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Search content for shortcodes and filter shortcodes through their hooks.
     *
     * If there are no shortcode tags defined, then the content will be returned
     * without any filtering. This might cause issues when plugins are disabled but
     * the shortcode will still show up in the post or content.
     *
     * @param string $content Content to search for shortcodes.
     *
     * @return string Content with shortcodes filtered out.
     */
    public static function doShortcode(string $content): string
    {
        if (empty(self::$shortcodeTags)) {
            return $content;
        }

        $pattern = self::getShortcodeRegex();

        return preg_replace_callback(
            "/$pattern/s",
            [
                self::class,
                'doShortcodeTag',
            ],
            $content
        );
    }

    /**
     * Retrieve the shortcode regular expression for searching.
     *
     * The regular expression combines the shortcode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping shortcodes with double [[]]
     * 2 - The shortcode name
     * 3 - The shortcode argument list
     * 4 - The self-closing /
     * 5 - The content of a shortcode when it wraps some content.
     * 6 - An extra ] to allow for escaping shortcodes with double [[]]
     *
     * @return string The shortcode search regular expression
     */
    public static function getShortcodeRegex(): string
    {
        $tagNames = array_keys(self::$shortcodeTags);
        $tagRegexp = implode('|', array_map('preg_quote', $tagNames));

        // WARNING! Do not change this regex without changing _doShortcodeTag() and _stripShortcodeTag()
        // Also, see shortcode_unautop() and shortcode.js.
        return
            '\\[' // Opening bracket
            . '(\\[?)' // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "($tagRegexp)" // 2: Shortcode name
            . '(?![\\w-])' // Not followed by word character or hyphen
            . '(' // 3: Unroll the loop: Inside the opening shortcode tag
            . '[^\\]\\/]*' // Not a closing bracket or forward slash
            . '(?:'
            . '\\/(?!\\])' // A forward slash not followed by a closing bracket
            . '[^\\]\\/]*' // Not a closing bracket or forward slash
            . ')*?'
            . ')'
            . '(?:'
            . '(\\/)' // 4: Self closing tag ...
            . '\\]' // ... and closing bracket
            . '|'
            . '\\]' // Closing bracket
            . '(?:'
            . '(' // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            . '[^\\[]*+' // Not an opening bracket
            . '(?:'
            . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            . '[^\\[]*+' // Not an opening bracket
            . ')*+'
            . ')'
            . '\\[\\/\\2\\]' // Closing shortcode tag
            . ')?'
            . ')'
            . '(\\]?)'; // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    /**
     * Regular Expression callable for doShortcode() for calling shortcode hook.
     *
     * @param array $m regular expression match array
     * @return string|false False on failure.
     * @see self::getShortcodeRegex for details of the match array contents.
     *
     */
    private static function doShortcodeTag(array $m): false|string
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag = $m[2];
        $attr = self::shortcodeParseAttrs($m[3]);

        // enclosing tag - extra parameter
        if (isset($m[5])) {
            return $m[1] . call_user_func(self::$shortcodeTags[$tag], $attr, $m[5], $tag) . $m[6];
        }

        // self-closing tag
        return $m[1] . call_user_func(self::$shortcodeTags[$tag], $attr, null, $tag) . $m[6];
    }

    /**
     * Retrieve all attributes from the shortcodes tag.
     *
     * The attributes list has the attribute name as the key and the value of the
     * attribute as the value in the key/value pair. This allows for easier
     * retrieval of the attributes, since all attributes have to be known.
     *
     * @param string $text
     * @return array List of attributes and their value.
     */
    public static function shortcodeParseAttrs(string $text): array
    {
        $attrs = [];
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)'
            . '|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)'
            . '|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)'
            . '|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", ' ', $text);
        $matches = [];

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                if (!empty($m[1])) {
                    $attrs[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (!empty($m[3])) {
                    $attrs[strtolower($m[3])] = stripcslashes($m[4]);
                } elseif (!empty($m[5])) {
                    $attrs[strtolower($m[5])] = stripcslashes($m[6]);
                } elseif (isset($m[7]) && $m[7] !== '') {
                    $attrs[] = stripcslashes($m[7]);
                } elseif (isset($m[8])) {
                    $attrs[] = stripcslashes($m[8]);
                }
            }
        } else {
            $attrs = ltrim($text);
        }

        return $attrs;
    }

    /**
     * Combine user attributes with known attributes and fill in defaults when needed.
     *
     * The pairs should be considered to be all the attributes which are
     * supported by the caller and given as a list. The returned attributes will
     * only contain the attributes in the $pairs list.
     *
     * If the $attrs list has unsupported attributes, then they will be ignored and
     * removed from the final returned list.
     *
     * @param array $pairs Entire list of supported attributes and their defaults.
     * @param array $attrs User defined attributes in shortcode tag.
     * @param string $shortcode [optional] The name of the shortcode, provided
     *                          for context to enable filtering.
     *
     * @return array Combined and filtered attribute list.
     */
    public static function shortcodeAttrs(array $pairs, array $attrs, string $shortcode = ''): array
    {
        $out = [];
        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $attrs)) {
                $out[$name] = $attrs[$name];
            } else {
                $out[$name] = $default;
            }
        }

        /**
         * Filter a shortcode's default attributes.
         *
         *
         *
         * If the third parameter of the shortcodeAttrs() function is present then this filter is available.
         * The third parameter, $shortcode, is the name of the shortcode.
         *
         *
         * @param array $out The output array of shortcode attributes.
         * @param array $pairs The supported attributes and their defaults.
         * @param array $attrs The user defined shortcode attributes.
         */
        if ($shortcode) {
            $out = self::applyFilters(
                "shortcodeAttrs_{$shortcode}",
                $out,
                $pairs,
                $attrs
            );
        }

        return $out;
    }

    /**
     * Remove all shortcode tags from the given content.
     *
     * @param string $content Content to remove shortcode tags.
     * @return string Content without shortcode tags.
     */
    public static function stripShortcodes(string $content): string
    {
        if (empty(self::$shortcodeTags)) {
            return $content;
        }

        $pattern = self::getShortcodeRegex();

        return preg_replace_callback(
            "/$pattern/s",
            [
                self::class,
                'stripShortcodeTag',
            ],
            $content
        );
    }

    /**
     * Strip shortcode by tag.
     *
     * @param array $m
     *
     * @return string
     */
    private static function stripShortcodeTag(array $m): string
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] === '[' && $m[6] === ']') {
            return substr($m[0], 1, -1);
        }

        return $m[1] . $m[6];
    }
}
