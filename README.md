

# Hook

Hook is a hook system for PHP projects. This project is a fork of a fork of a fork, from a long time ago, and now got revived for posterity.

## Installation

```shell
composer require lotharthesavior/hook
```

## Context

Hooks are a way for one piece of code to interact/modify another piece of code. They are a way for a piece of code to be executed at a certain point in an application. This is a very powerful concept and is used in many applications, including WordPress, to allow developers to modify the behavior of the application without modifying the core code.

Customization points that use hooks are often called "actions" and "filters". An action is a point in the code where something happens, and a filter is a point in the code where something is modified. For example, in WordPress, the `wp_head` action is a point in the code where the `<head>` section of the HTML is output, and the `the_content` filter is a point in the code where the content of a post is modified before it is output.

This class is a rewritten of a fork of the original [PHP Hooks](https://github.com/bainternet/PHP-Hooks/) which is a fork of the original PHP Hooks by John Kolbert. The original class was designed to be used in WordPress plugins and themes, but this class can be used in any PHP project.

## Usage

### Filters

> Filters are functions that an application passes data through, at certain points in execution, just before taking some action with the data (such as adding it to the database or writing it to the output buffer - a terminal or a browser). As an example, most input and output in WordPress passes through at least one filter. Filter hooks is a great way to allow other developers to modify or extend the default behavior of any code.

This registers a filter:

```php
<?php
use Hook\Filter;

Filter::addFilter('filter_name','filter_function');

function filter_function($content){
   return $content.'this came from a hooked function';
}
```

Now, anywhere in your application, you can execute that filter:

```php
<?php

use Hook\Filter;

echo Filter::applyFilters('filter_name','this is the content: ');
```

### Actions

> Actions are functions that an application executes at specific points during execution, or when specific events occur. Actions are a way to make your application do something at a certain point, without modifying the code. In a sense it is like an event listener, but with a different name.

This registers an action:

```php
<?php
use Hook\Action;

Action::addAction('header_action','echo_this_in_header');

function echo_this_in_header(){
   echo 'this came from a hooked function';
}
```    

Now, anywhere in your application, you can execute that action:

```php
<?php

use Hooks\Action;

echo '<div id="extra_header">';
Action::doAction('header_action');
echo '</div>';
```

The output will be: `<div id="extra_header">this came from a hooked function</div>`

## Methods

### Filters

```php
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
    int $priority = Hook\Constants::PRIORITY_NEUTRAL,
    ?string $includePath = null,
): bool

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
    int $priority = Hook\Constants::PRIORITY_NEUTRAL,
): bool
```

### Actions

```php
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
    int $priority = Hook\Constants::PRIORITY_NEUTRAL,
    ?string $includePath = null
): bool

/**
 * Execute functions hooked on a specific action hook.
 *
 * @param string $tag The name of the action to be executed.
 * @param mixed $arg [optional] Additional arguments which are passed on
 *                   to the functions hooked to the action.
 * @return bool Will return false if $tag does not exist in $filter array.
 */
public static function doAction(string $tag, mixed $arg = ''): bool

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
): string|int|bool

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
    int $priority = Hook\Constants::PRIORITY_NEUTRAL
): bool
```

### Shortcodes
    
```php
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

/**
 * Whether the passed content contains the specified shortcode.
 *
 * @param string $content
 * @param string $tag
 *
 * @return bool
 */
public static function hasShortcode(string $content, string $tag): bool

/**
 * Removes hook for shortcode.
 *
 * @param string $tag shortcode tag to remove hook for.
 *
 * @return bool
 */
public static function removeShortcode(string $tag): bool
```

## License

Since this class is derived from the WordPress Plugin API so are the license, and they are GPL http://www.gnu.org/licenses/gpl.html

  [1]: https://github.com/bainternet/PHP-Hooks/zipball/master
  [2]: https://github.com/bainternet/PHP-Hooks/tarball/master
  [3]: https://github.com/bainternet/PHP-Hooks/
  [4]: https://github.com/voku/php-hooks/

## Credits

- Ohad Raz (https://github.com/bainternet)
- David Miles (https://github.com/amereservant/PHP-Hooks)
- Lars Moelleken (https://www.moelleken.org)
- Damien "Mistic" Sorel (https://www.strangeplanet.fr)
