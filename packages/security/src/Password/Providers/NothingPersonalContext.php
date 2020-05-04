<?php /** @noinspection PhpUndefinedMethodInspection */

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

use BiuradPHP\Security\Interfaces\PasswordValidatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use function BiuradPHP\Support\strip_explode;

/**
 * Class NothingPersonalValidator
 *
 * Checks password does not contain any personal information
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @author Lonnie Ezell <lonnieje@gmail.com>
 */
class NothingPersonalContext extends AbstractValidator implements PasswordValidatorInterface
{
    /**
     * Returns true if $password contains no part of the username
     * or the user's email. Otherwise, it returns false.
     * If true is returned the password will be passed to next validator.
     * If false is returned the validation process will be immediately stopped.
     *
     * @param string $password
     * @param UserInterface $user
     *
     * @return boolean
     */
    public function check(string $password, UserInterface $user = null): bool
    {
        $password = strtolower($password);

        if(false !== $valid = $this->isNotSimilar($password, $user)) {
            $valid = $this->isNotPersonal($password, $user);
        }

        return $valid;
    }

    /**
     * isNotPersonal()
     *
     * Looks for personal information in a password. The personal info used
     * comes from Myth\Auth\Entities\User properties username and email.
     *
     * It is possible to include other fields as information sources.
     * For instance, a project might require adding `phonenumner` and `fullname` properties
     * to an extended version of the UserInterface class.
     *
     * isNotPersonal() returns true if no personal information can be found, or false
     * if such info is found.
     *
     * @param string $password
     * @param UserInterface $user
     * @return boolean
     */
    protected function isNotPersonal(string $password, UserInterface $user)
    {
        $userName = strtolower($user->getUsername() ?: '');
        $email = strtolower($user->getEmail());
        $valid = true;

        // The most obvious transgressions
        if(
            $password === $userName ||
            $password === $email ||
            $password === strrev($userName)
        ) {
            $valid = false;
        }

        // Parse out as many pieces as possible from username, password and email.
        // Use the pieces as needles and haystacks and look every which way for matches.
        if(false !== $valid) {
            // Take username apart for use as search needles
            $needles = strip_explode($userName);

            // extract local-part and domain parts from email as separate needles
            [$localPart, $domain] = explode('@', $email);
            // might be john.doe@example.com and we want all the needles we can get
            $emailParts = strip_explode($localPart);
            if(!empty($domain)) {
                $emailParts[] = $domain;
            }

            $needles = array_merge($needles, $emailParts);

            // Get any other "personal" fields defined in config
            $personalFields = [$user->getFullName() ?: '', (string) $user->getPhoneNumber() ?: ''];
            if( ! empty($personalFields)) {
                foreach($personalFields as $value) {
                    if( ! empty($user->$value)) {
                        $needles[] = strtolower($user->$value);
                    }

                    if (in_array($value, $needles)) {
                        $needles[] = strtolower($value);
                    }
                }
            }

            $trivial = [
                'a', 'an', 'and', 'as', 'at', 'but', 'for',
                'if', 'in', 'not', 'of', 'or', 'so', 'the', 'then'
            ];

            // Make password into haystacks
            $haystacks = strip_explode($password);

            foreach($haystacks as $haystack) {
                if(empty($haystack) || in_array($haystack, $trivial)) {
                    continue;  //ignore trivial words
                }

                foreach($needles as $needle) {
                    if(empty($needle) || in_array($needle, $trivial)) {
                        continue;
                    }

                    // look both ways in case password is subset of needle
                    if(
                        strpos($haystack, $needle) !== false ||
                        strpos($needle, $haystack) !== false
                    ) {
                        $valid = false;
                        break 2;
                    }
                }
            }
        }

        if($valid) {
            return true;
        }

        $this->error = 'Passwords cannot contain re-hashed personal information.';
        $this->suggestion = 'Variations on your email address or username should not be used for passwords.';

        return false;
    }

    /**
     * notSimilar() uses $password and $userName to calculate a similarity value.
     * Similarity values equal to, or greater than config maxSimilarity
     * are rejected for being too much alike and false is returned.
     * Otherwise, true is returned,
     *
     * A $maxSimilarity value of 0 (zero) returns true without making a comparison.
     * In other words, 0 (zero) turns off similarity testing.
     *
     * @param string $password
     * @param UserInterface $user
     * @return boolean
     */
    protected function isNotSimilar(string $password, UserInterface $user)
    {
        $maxSimilarity = (float) $this->config['max_similarity'];
        // sanity checking - working range 1-100, 0 is off
        if($maxSimilarity < 1) {
            $maxSimilarity = 0;
        } elseif($maxSimilarity > 100) {
            $maxSimilarity = 100;
        }

        if( ! empty($maxSimilarity)) {
            $userName = strtolower($user->getUsername() ?: '');
            similar_text($password, $userName, $similarity);

            if($similarity >= $maxSimilarity) {
                $this->error = 'Password is too similar to the username.';
                $this->suggestion = 'Do not use parts of your username in your password.';

                return false;
            }
        }

        return true;
    }
}
