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

namespace BiuradPHP\Toolbox\ConsoleLite\Exceptions;

/**
 * ConsoleLite DeprecatedException.
 *
 * Enter deprecated option or command name.
 *
 * Usuage:
 *
 * throw new \BiuradPHP\Toolbox\ConsoleLite\Exceptions\DeprecatedException('listf')
 *
 * or
 *
 * throw new \BiuradPHP\Toolbox\ConsoleLite\Exceptions\DeprecatedException(['listf' => 'list'])
 *
 * @author Divine Niiquaye <hello@biuhub.net>
 * @license MIT
 */
class DeprecatedException extends \RuntimeException
{
    /**
     * The constuctor.
     */
    public function __construct($message = null)
    {
        parent::__construct($this->_message($message), E_DEPRECATED);
    }

    /**
     * Undocumented function.
     *
     * @param string|array $message
     *
     * @return string|array
     */
    protected function _message($message)
    {
        if (is_array($message)) {
            foreach ($message as $name => $replace) {
                return sprintf("Using '%s' is deprecated since last released version, use '%s' instead.", $name, $replace);
            }
        }

        return sprintf('%s has been deprecated since previous released', $message);
    }
}
