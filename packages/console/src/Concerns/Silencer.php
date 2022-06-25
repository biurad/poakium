<?php

/*
 * The Biurad Toolbox ConsoleLite.
 *
 * This is an extensible library used to load classes
 * from namespaces and files just like composer.
 *
 * @see ReadMe.md to know more about how to load your
 * classes via command line.
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 */

namespace BiuradPHP\Toolbox\ConsoleLite\Concerns;

/**
 * Temporarily suppress PHP error reporting, usually warnings and below.
 *
 * Originally from Niels Keurentjes in Composer project.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 * @license MIT
 */
class Silencer
{
    /**
     * @var int[] Unpop stack
     */
    private static $stack = [];

    /**
     * Suppresses given mask or errors.
     *
     * @param int|null $mask Error levels to suppress, default value NULL indicates all warnings and below.
     *
     * @return int The old error reporting level.
     */
    public static function suppress($mask = null)
    {
        if (!isset($mask)) {
            $mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED | E_STRICT;
        }
        $old = error_reporting();
        self::$stack[] = $old;
        error_reporting($old & ~$mask);

        return $old;
    }

    /**
     * Restores a single state.
     */
    public static function restore()
    {
        if (!empty(self::$stack)) {
            error_reporting(array_pop(self::$stack));
        }
    }

    /**
     * Calls a specified function while silencing warnings and below.
     *
     * Future improvement: when PHP requirements are raised add Callable type hint (5.4) and variadic parameters (5.6)
     *
     * @param callable $callable function to execute
     *
     * @throws \Exception any exceptions from the callback are rethrown
     *
     * @return mixed return value of the callback
     */
    public static function call($callable /*, ...$parameters */)
    {
        try {
            $result = call_user_func_array($callable, array_slice(func_get_args(), 1));

            if ($result === 'eval' || $callable === 'eval') {
                throw new \BadFunctionCallException('The function eval should never be used');
            }

            return $result;
        } catch (\Exception $e) {
            // Use a finally block for this when requirements are raised to PHP 5.5
            self::restore();

            throw $e;
        }
    }
}
