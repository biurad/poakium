<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  SecurityManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/securitymanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Security\Password\Providers;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class CompositionValidator
 *
 * Checks the general makeup of the password.
 *
 * While older composition checks might have included different character
 * groups that you had to include, current NIST standards prefer to simply
 * set a minimum length and a long maximum (128+ chars).
 *
 * @see https://pages.nist.gov/800-63-3/sp800-63b.html#sec5
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @author Lonnie Ezell <lonnieje@gmail.com>
 */
class CompositionContext extends AbstractValidator
{
    /**
     * Returns true when the password passes this test.
     * The password will be passed to any remaining validators.
     * False will immediately stop validation process
     *
     * @param string $password
     * @param UserInterface $user
     *
     * @return boolean
     */
    public function check(string $password, UserInterface $user = null): bool
    {
        $passed = strlen($password) >= $this->config['length'];

        if(true !== $passed) {
            $this->error = sprintf('Passwords must be at least {%s} characters long.', $this->config['length']);
            $this->suggestion = 'Make more secure passwords that are easy to remember.';

            return false;
        }

        return true;
    }
}
