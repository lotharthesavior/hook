<?php

declare(strict_types=1);

namespace Hook;

/**
 * PHP Hooks Class (Modified)
 *
 * The PHP Hooks Class is a fork of the WordPress filters hook system rolled in
 * to a class to be ported into any php based system
 *
 * @package voku\helper
 */
class LegacyHooks
{
    /**
     * Filters - holds list of hooks
     *
     * @var array
     */
    protected array $filters = [];
    protected array $mergedFilters = [];
    protected array $actions = [];

    /**
     * Current Filter - holds the name of the current filter
     *
     * @var array
     */
    protected array $currentFilter = [];

    /**
     * Container for storing shortcode tags and their hook to call for the shortcode
     *
     * @var array
     */
    public static array $shortcodeTags = [];

    /**
     * Default priority
     *
     * @const int
     */
    public const PRIORITY_NEUTRAL = 50;

    /**
     * This class is not allowed to call from outside: private!
     */
    protected function __construct()
    {
    }

    /**
     * @param string $tag
     * @return void
     */
    public function prepareAndSortFilters(string $tag): void
    {
        if (!isset($this->filters['all'])) {
            $this->currentFilter[] = $tag;
        }

        if (!isset($this->mergedFilters[$tag])) {
            ksort($this->filters[$tag]);
            $this->mergedFilters[$tag] = true;
        }

        reset($this->filters[$tag]);
    }

    /**
     * @param string $tag
     * @param array $args
     * @return true
     */
    private function executeSortedFilters(string $tag, array $args): bool
    {
        if (!isset($this->mergedFilters[$tag])) {
            ksort($this->filters[$tag]);
            $this->mergedFilters[$tag] = true;
        }

        reset($this->filters[$tag]);

        do {
            foreach ((array) current($this->filters[$tag]) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    call_user_func_array($the_['function'], $args);
                }
            }
        } while (next($this->filters[$tag]) !== false);

        array_pop($this->currentFilter);

        return true;
    }

    /**
     * Prevent the object from being cloned.
     */
    protected function __clone()
    {
    }

    /**
     * Avoid serialization.
     */
    public function __wakeup()
    {
    }

    /**
     * Returns a Singleton instance of this class.
     *
     * @return Hooks
     */
    public static function getInstance(): self
    {
        static $instance;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * FILTERS
     */

    /**
     * Adds Hooks to a function or method to a specific filter action.
     *
     * @param string $tag The name of the filter to hook the {@link $functionToAdd} to.
     *
     * @param callable $functionToAdd The name of the function to be called when the filter is applied.
     *
     * @param int $priority [optional] Used to specify the order in
     *                      which the functions associated with a
     *                      particular action are executed (default: 50).
     *                      Lower numbers correspond with earlier execution,
     *                      and functions with the same priority are executed
     *                      in the order in which they were added to the action.
     *
     * @param ?string $includePath [optional] File to include before executing the callback.
     *
     * @return bool
     */
    public function addFilter(
        string $tag,
        callable $functionToAdd,
        int $priority = self::PRIORITY_NEUTRAL,
        ?string $includePath = null,
    ): bool {
        $idx = $this->filterBuildUniqueId($functionToAdd);

        $this->filters[$tag][$priority][$idx] = [
            'function' => $functionToAdd,
            'include_path' => is_string($includePath) ? $includePath : null,
        ];

        unset($this->mergedFilters[$tag]);

        return true;
    }

    /**
     * Removes a function from a specified filter hook.
     *
     * @param string $tag The filter hook to which the function to be removed is hooked.
     * @param callable $functionToRemove The name of the function which should be removed.
     * @param int $priority [optional] The priority of the function (default: 50).
     *
     * @return bool
     */
    public function removeFilter(
        string $tag,
        callable $functionToRemove,
        int $priority = self::PRIORITY_NEUTRAL,
    ): bool {
        $functionToRemove = $this->filterBuildUniqueId($functionToRemove);

        if (!isset($this->filters[$tag][$priority][$functionToRemove])) {
            return false;
        }

        unset($this->filters[$tag][$priority][$functionToRemove]);
        if (empty($this->filters[$tag][$priority])) {
            unset($this->filters[$tag][$priority]);
        }

        unset($this->mergedFilters[$tag]);

        return true;
    }

    /**
     * Remove all the hooks from a filter.
     *
     * @param string $tag The filter to remove hooks from.
     * @param false|int $priority The priority number to remove.
     *
     * @return bool
     */
    public function removeAllFilters(string $tag, false|int $priority = false): bool
    {
        if (isset($this->mergedFilters[$tag])) {
            unset($this->mergedFilters[$tag]);
        }

        if (!isset($this->filters[$tag])) {
            return true;
        }

        if (false !== $priority && isset($this->filters[$tag][$priority])) {
            unset($this->filters[$tag][$priority]);
        } else {
            unset($this->filters[$tag]);
        }

        return true;
    }

    /**
     * Check if any filter has been registered for the given hook.
     * INFO: Use !== false to check if it's true!
     *
     * @param string $tag The name of the filter hook.
     * @param false|callable $function_to_check [optional] Callback function name to check for
     *
     * @return string|int|bool If {@link $function_to_check} is omitted,
     *         returns boolean for whether the hook has
     *         anything registered.
     *         When checking a specific function, the priority
     *         of that hook is returned, or false if the
     *         function is not attached.
     *         When using the {@link $function_to_check} argument,
     *         this function may return a non-boolean value that
     *         evaluates to false (e.g.) 0, so use the === operator for testing the return value.
     *
     */
    public function hasFilter(string $tag, false|callable $function_to_check = false): string|int|bool
    {
        $has = isset($this->filters[$tag]);
        if (false === $function_to_check || !$has) {
            return $has;
        }

        if (!($idx = $this->filterBuildUniqueId($function_to_check))) {
            return false;
        }

        foreach (array_keys($this->filters[$tag]) as $priority) {
            if (isset($this->filters[$tag][$priority][$idx])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Call the functions added to a filter hook.
     *
     *
     *
     * INFO: Additional variables passed to the functions hooked to $tag.
     *
     *
     * @param string $tag The name of the filter hook.
     * @param mixed $value The value on which the filters hooked to $tag are applied on.
     *
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFilters(string $tag, mixed $value): mixed
    {
        $args = [];

        // Do 'all' actions first
        if (isset($this->filters['all'])) {
            $this->currentFilter[] = $tag;
            $args = func_get_args();
            $this->callAllHook($args);
        }

        if (!isset($this->filters[$tag])) {
            if (isset($this->filters['all'])) {
                array_pop($this->currentFilter);
            }

            return $value;
        }

        $this->prepareAndSortFilters($tag);

        if (empty($args)) {
            $args = func_get_args();
        }

        array_shift($args);

        do {
            foreach ((array) current($this->filters[$tag]) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    $args[0] = $value;
                    $value = call_user_func_array($the_['function'], $args);
                }
            }
        } while (next($this->filters[$tag]) !== false);

        array_pop($this->currentFilter);

        return $value;
    }

    /**
     * Execute functions hooked on a specific filter hook, specifying arguments in an array.
     *
     * @param string $tag The name of the filter hook.
     * @param array $args The arguments supplied to the functions hooked to $tag
     *
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFiltersRefArray(string $tag, array $args): mixed
    {
        // Do 'all' actions first
        if (isset($this->filters['all'])) {
            $this->currentFilter[] = $tag;
            $all_args = func_get_args();
            $this->callAllHook($all_args);
        }

        if (!isset($this->filters[$tag])) {
            if (isset($this->filters['all'])) {
                array_pop($this->currentFilter);
            }

            return $args[0];
        }

        $this->prepareAndSortFilters($tag);

        do {
            foreach ((array) current($this->filters[$tag]) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    $args[0] = call_user_func_array($the_['function'], $args);
                }
            }
        } while (next($this->filters[$tag]) !== false);

        array_pop($this->currentFilter);

        return $args[0];
    }

    /**
     * Check if any action has been registered for a hook.
     *
     * INFO: Use !== false to check if it's true!
     *
     * @param string $tag The name of the action hook.
     * @param false|string $function_to_check [optional]
     *
     * @return string|int|bool If $function_to_check is omitted,
     *                         returns boolean for whether the hook has
     *                         anything registered.
     *                         When checking a specific function,
     *                         the priority of that hook is returned,
     *                         or false if the function is not attached.
     *                         When using the $function_to_check
     *                         argument, this function may return a non-boolean
     *                         value that evaluates to false (e.g.) 0,
     *                         so use the === operator for testing the return value.
     */
    public function hasAction(
        string $tag,
        false|string $function_to_check = false
    ): string|int|bool {
        return $this->hasFilter($tag, $function_to_check);
    }

    /**
     * Removes a function from a specified action hook.
     *
     * @param string $tag The action hook to which the function to be removed is hooked.
     * @param callable $functionToRemove The name of the function which should be removed.
     * @param int $priority [optional] The priority of the function (default: 50).
     *
     * @return bool Whether the function is removed.
     */
    public function removeAction(
        string $tag,
        callable $functionToRemove,
        int $priority = self::PRIORITY_NEUTRAL
    ): bool {
        return $this->removeFilter($tag, $functionToRemove, $priority);
    }

    /**
     * Hooks a function on to a specific action.
     *
     * @param string $tag The name of the action to which the
     *                    $functionToAdd is hooked.
     * @param callable $functionToAdd The name of the function you wish to be called.
     * @param int $priority [optional] Used to specify the order in which
     *                      the functions associated with a particular
     *                      action are executed (default: 50).
     *                      Lower numbers correspond with earlier execution,
     *                      and functions with the same priority are executed
     *                      in the order in which they were added to the action.
     * @param ?string $includePath [optional] File to include before executing the callback.
     *
     * @return bool
     */
    public function addAction(
        string $tag,
        callable $functionToAdd,
        int $priority = self::PRIORITY_NEUTRAL,
        ?string $includePath = null
    ): bool {
        return $this->addFilter($tag, $functionToAdd, $priority, $includePath);
    }

    /**
     * Remove all the hooks from an action.
     *
     * @param string $tag The action to remove hooks from.
     * @param false|int $priority The priority number to remove them from.
     *
     * @return bool
     */
    public function removeAllActions(
        string $tag,
        false|int $priority = false
    ): bool {
        return $this->removeAllFilters($tag, $priority);
    }

    /**
     * Execute functions hooked on a specific action hook.
     *
     * @param string $tag The name of the action to be executed.
     * @param mixed $arg [optional] Additional arguments which are passed on
     *                   to the functions hooked to the action.
     * @return bool Will return false if $tag does not exist in $filter array.
     */
    public function doAction(string $tag, mixed $arg = ''): bool
    {
        if (!$this->maybeInitializeActions($tag)) {
            return false;
        }

        $args = [];

        if (
            is_array($arg)
            && isset($arg[0])
            && is_object($arg[0])
            && 1 == count($arg)
        ) {
            $args[] =& $arg[0];
        } else {
            $args[] = $arg;
        }

        $numArgs = func_num_args();

        for ($a = 2; $a < $numArgs; $a++) {
            $args[] = func_get_arg($a);
        }

        return $this->executeSortedFilters($tag, $args);
    }

    /**
     * Execute functions hooked on a specific action hook, specifying arguments in an array.
     *
     * @param string $tag The name of the action to be executed.
     * @param array $args The arguments supplied to the functions hooked to $tag
     *
     * @return bool Will return false if $tag does not exist in $filter array.
     */
    public function doActionRefArray(string $tag, array $args): bool
    {
        if (!$this->maybeInitializeActions($tag)) {
            return false;
        }

        return $this->executeSortedFilters($tag, $args);
    }

    private function maybeInitializeActions(string $tag): bool
    {
        if (isset($this->actions[$tag])) {
            ++$this->actions[$tag];
        } else {
            $this->actions[$tag] = 1;
        }

        // Do 'all' actions first
        if (isset($this->filters['all'])) {
            $this->currentFilter[] = $tag;
            $all_args = func_get_args();
            $this->callAllHook($all_args);
        }

        if (!isset($this->filters[$tag])) {
            if (isset($this->filters['all'])) {
                array_pop($this->currentFilter);
            }

            return false;
        }

        if (!isset($this->filters['all'])) {
            $this->currentFilter[] = $tag;
        }

        return true;
    }

    /**
     * Retrieve the number of times an action has fired.
     *
     * @param string $tag The name of the action hook.
     * @return int The number of times action hook $tag is fired.
     */
    public function didAction(string $tag): int
    {
        if (!isset($this->actions[$tag])) {
            return 0;
        }

        return $this->actions[$tag];
    }

    /**
     * Retrieve the name of the current filter or action.
     *
     * @return string Hook name of the current filter or action.
     */
    public function currentFilter(): string
    {
        return end($this->currentFilter);
    }

    /**
     * Build Unique ID for storage and retrieval.
     *
     * @param callable $function Used for creating unique id.
     *
     * @return string|false Unique ID for usage as array key or false if
     *                      $priority === false and $function is an
     *                      object reference, and it does not already have a unique id.
     *
     */
    private function filterBuildUniqueId(callable $function): false|string
    {
        if (is_string($function)) {
            return $function;
        }

        if (is_object($function)) {
            // Closures are currently implemented as objects
            $function = [
                $function,
                '',
            ];
        } else {
            $function = (array) $function;
        }

        if (is_object($function[0])) {
            // Object Class Calling
            return spl_object_hash($function[0]) . $function[1];
        }

        if (is_string($function[0])) {
            // Static Calling
            return $function[0] . $function[1];
        }

        return false;
    }

    /**
     * Call "All" Hook
     *
     * @param array $args
     */
    public function callAllHook(array $args): void
    {
        reset($this->filters['all']);

        do {
            foreach ((array) current($this->filters['all']) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    call_user_func_array($the_['function'], $args);
                }
            }
        } while (next($this->filters['all']) !== false);
    }

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
    public function addShortcode(string $tag, callable $function): bool
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
    public function removeShortcode(string $tag): bool
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
    public function removeAllShortcodes(): bool
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
    public function shortcodeExists(string $tag): bool
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
    public function hasShortcode(string $content, string $tag): bool
    {
        if (!str_contains($content, '[')) {
            return false;
        }

        if ($this->shortcodeExists($tag)) {
            preg_match_all('/' . $this->getShortcodeRegex() . '/s', $content, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                return false;
            }

            foreach ($matches as $shortcode) {
                if ($tag === $shortcode[2]) {
                    return true;
                }

                if (!empty($shortcode[5]) && $this->hasShortcode($shortcode[5], $tag)) {
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
    public function doShortcode(string $content): string
    {
        if (empty(self::$shortcodeTags)) {
            return $content;
        }

        $pattern = $this->getShortcodeRegex();

        return preg_replace_callback(
            "/$pattern/s",
            [
                $this,
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
    public function getShortcodeRegex(): string
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
    private function doShortcodeTag(array $m): false|string
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag = $m[2];
        $attr = $this->shortcodeParseAttrs($m[3]);

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
    public function shortcodeParseAttrs(string $text): array
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
    public function shortcodeAttrs(array $pairs, array $attrs, string $shortcode = ''): array
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
            $out = $this->applyFilters(
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
    public function stripShortcodes(string $content): string
    {
        if (empty(self::$shortcodeTags)) {
            return $content;
        }

        $pattern = $this->getShortcodeRegex();

        return preg_replace_callback(
            "/$pattern/s",
            [
                $this,
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
    private function stripShortcodeTag(array $m): string
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] === '[' && $m[6] === ']') {
            return substr($m[0], 1, -1);
        }

        return $m[1] . $m[6];
    }
}
