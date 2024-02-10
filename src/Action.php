<?php

namespace Hook;

class Action extends Filter
{
    protected static array $actions = [];

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
    public static function hasAction(
        string $tag,
        false|string $function_to_check = false
    ): string|int|bool {
        return self::hasFilter($tag, $function_to_check);
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
    public static function removeAction(
        string $tag,
        callable $functionToRemove,
        int $priority = Constants::PRIORITY_NEUTRAL
    ): bool {
        return self::removeFilter($tag, $functionToRemove, $priority);
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
    public static function addAction(
        string $tag,
        callable $functionToAdd,
        int $priority = Constants::PRIORITY_NEUTRAL,
        ?string $includePath = null
    ): bool {
        return self::addFilter($tag, $functionToAdd, $priority, $includePath);
    }

    /**
     * Remove all the hooks from an action.
     *
     * @param string $tag The action to remove hooks from.
     * @param false|int $priority The priority number to remove them from.
     *
     * @return bool
     */
    public static function removeAllActions(
        string $tag,
        false|int $priority = false
    ): bool {
        return self::removeAllFilters($tag, $priority);
    }

    /**
     * Execute functions hooked on a specific action hook.
     *
     * @param string $tag The name of the action to be executed.
     * @param mixed $arg [optional] Additional arguments which are passed on
     *                   to the functions hooked to the action.
     * @return bool Will return false if $tag does not exist in $filter array.
     */
    public static function doAction(string $tag, mixed $arg = ''): bool
    {
        if (!self::maybeInitializeActions($tag)) {
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

        return self::executeSortedFilters($tag, $args);
    }

    /**
     * Execute functions hooked on a specific action hook, specifying arguments in an array.
     *
     * @param string $tag The name of the action to be executed.
     * @param array $args The arguments supplied to the functions hooked to $tag
     *
     * @return bool Will return false if $tag does not exist in $filter array.
     */
    public static function doActionRefArray(string $tag, array $args): bool
    {
        if (!self::maybeInitializeActions($tag)) {
            return false;
        }

        return self::executeSortedFilters($tag, $args);
    }

    private static function maybeInitializeActions(string $tag): bool
    {
        if (isset(self::$actions[$tag])) {
            ++self::$actions[$tag];
        } else {
            self::$actions[$tag] = 1;
        }

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

            return false;
        }

        if (!isset(self::$filters['all'])) {
            self::$currentFilter[] = $tag;
        }

        return true;
    }

    /**
     * Retrieve the number of times an action has fired.
     *
     * @param string $tag The name of the action hook.
     * @return int The number of times action hook $tag is fired.
     */
    public static function didAction(string $tag): int
    {
        if (!isset(self::$actions[$tag])) {
            return 0;
        }

        return self::$actions[$tag];
    }
}
