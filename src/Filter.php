<?php

namespace Hook;

class Filter
{
    protected static array $filters = [];
    protected static array $mergedFilters = [];

    /**
     * Current Filter - holds the name of the current filter
     *
     * @var array
     */
    protected static array $currentFilter = [];

    /**
     * @param string $tag
     * @return void
     */
    public static function prepareAndSortFilters(string $tag): void
    {
        if (!isset(self::$filters['all'])) {
            self::$currentFilter[] = $tag;
        }

        if (!isset(self::$mergedFilters[$tag])) {
            ksort(self::$filters[$tag]);
            self::$mergedFilters[$tag] = true;
        }

        reset(self::$filters[$tag]);
    }

    /**
     * @param string $tag
     * @param array $args
     * @return true
     */
    protected static function executeSortedFilters(string $tag, array $args): bool
    {
        if (!isset(self::$mergedFilters[$tag])) {
            ksort(self::$filters[$tag]);
            self::$mergedFilters[$tag] = true;
        }

        reset(self::$filters[$tag]);

        do {
            foreach ((array) current(self::$filters[$tag]) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    call_user_func_array($the_['function'], $args);
                }
            }
        } while (next(self::$filters[$tag]) !== false);

        array_pop(self::$currentFilter);

        return true;
    }

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
    public static function addFilter(
        string $tag,
        callable $functionToAdd,
        int $priority = Constants::PRIORITY_NEUTRAL,
        ?string $includePath = null,
    ): bool {
        $idx = self::filterBuildUniqueId($functionToAdd);

        self::$filters[$tag][$priority][$idx] = [
            'function' => $functionToAdd,
            'include_path' => is_string($includePath) ? $includePath : null,
        ];

        unset(self::$mergedFilters[$tag]);

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
    public static function removeFilter(
        string $tag,
        callable $functionToRemove,
        int $priority = Constants::PRIORITY_NEUTRAL,
    ): bool {
        $functionToRemove = self::filterBuildUniqueId($functionToRemove);

        if (!isset(self::$filters[$tag][$priority][$functionToRemove])) {
            return false;
        }

        unset(self::$filters[$tag][$priority][$functionToRemove]);
        if (empty(self::$filters[$tag][$priority])) {
            unset(self::$filters[$tag][$priority]);
        }

        unset(self::$mergedFilters[$tag]);

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
    public static function removeAllFilters(string $tag, false|int $priority = false): bool
    {
        if (isset(self::$mergedFilters[$tag])) {
            unset(self::$mergedFilters[$tag]);
        }

        if (!isset(self::$filters[$tag])) {
            return true;
        }

        if (false !== $priority && isset(self::$filters[$tag][$priority])) {
            unset(self::$filters[$tag][$priority]);
        } else {
            unset(self::$filters[$tag]);
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
    public static function hasFilter(string $tag, false|callable $function_to_check = false): string|int|bool
    {
        $has = isset(self::$filters[$tag]);
        if (false === $function_to_check || !$has) {
            return $has;
        }

        if (!($idx = self::filterBuildUniqueId($function_to_check))) {
            return false;
        }

        foreach (array_keys(self::$filters[$tag]) as $priority) {
            if (isset(self::$filters[$tag][$priority][$idx])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Call the functions added to a filter hook.
     *
     * INFO: Additional variables passed to the functions hooked to $tag.
     *
     * @param string $tag The name of the filter hook.
     * @param mixed $value The value on which the filters hooked to $tag are applied on.
     *
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public static function applyFilters(string $tag, mixed $value): mixed
    {
        $args = [];

        // Do 'all' actions first
        if (isset(self::$filters['all'])) {
            self::$currentFilter[] = $tag;
            $args = func_get_args();
            self::callAllHook($args);
        }

        if (!isset(self::$filters[$tag])) {
            if (isset(self::$filters['all'])) {
                array_pop(self::$currentFilter);
            }

            return $value;
        }

        self::prepareAndSortFilters($tag);

        if (empty($args)) {
            $args = func_get_args();
        }

        array_shift($args);

        do {
            foreach ((array) current(self::$filters[$tag]) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    $args[0] = $value;
                    $value = call_user_func_array($the_['function'], $args);
                }
            }
        } while (next(self::$filters[$tag]) !== false);

        array_pop(self::$currentFilter);

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
    public static function applyFiltersRefArray(string $tag, array $args): mixed
    {
        // Do 'all' actions first
        if (isset(self::$filters['all'])) {
            self::$currentFilter[] = $tag;
            $all_args = func_get_args();
            self::callAllHook($all_args);
        }

        if (!isset(self::$filters[$tag])) {
            if (isset(self::$filters['all'])) {
                array_pop(self::$currentFilter);
            }

            return $args[0];
        }

        self::prepareAndSortFilters($tag);

        do {
            foreach ((array) current(self::$filters[$tag]) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    $args[0] = call_user_func_array($the_['function'], $args);
                }
            }
        } while (next(self::$filters[$tag]) !== false);

        array_pop(self::$currentFilter);

        return $args[0];
    }

    /**
     * Retrieve the name of the current filter or action.
     *
     * @return string Hook name of the current filter or action.
     */
    public static function currentFilter(): string
    {
        return end(self::$currentFilter);
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
    private static function filterBuildUniqueId(callable $function): false|string
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
    public static function callAllHook(array $args): void
    {
        reset(self::$filters['all']);

        do {
            foreach ((array) current(self::$filters['all']) as $the_) {
                if (null !== $the_['function']) {
                    if (null !== $the_['include_path']) {
                        include_once $the_['include_path'];
                    }

                    call_user_func_array($the_['function'], $args);
                }
            }
        } while (next(self::$filters['all']) !== false);
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
}
