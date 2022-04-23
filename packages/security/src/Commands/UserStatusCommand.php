<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Biurad\Security\Commands;

use Biurad\Security\Interfaces\UserStatusInterface;
use Biurad\Security\Interfaces\CredentialsHolderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Get a user's status.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class UserStatusCommand extends Command
{
    protected static $defaultName = 'security:user-status';
    private UserProviderInterface $provider;

    public function __construct(UserProviderInterface $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Check a registered user\'s status')
            ->addArgument('user', InputArgument::OPTIONAL, 'The User\'s identity that can be access in the website')
            ->setHelp(
                <<<EOF

The <info>%command.name%</info> command shows a registered user's status according to your
security configuration. This command is mainly used to view user's account status.

Suppose that you want to check a user's status or for performing an action on the website:

<info>php %command.full_name% [username or email]</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errorIo = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);
        $input->isInteractive() ? $errorIo->title('Biurad User Utility') : $errorIo->newLine();

        if (!$identity = $input->getArgument('user')) {
            if (!$input->isInteractive()) {
                $errorIo->error('The user\'s username or email should be provided and must not be empty.');

                return self::FAILURE;
            }

            $identityQuestion = $this->createIdentityQuestion();
            $identity = $errorIo->askQuestion($identityQuestion);
        }

        try {
            $user = $this->provider->loadUserByIdentifier($identity);
        } catch (UserNotFoundException $e) {
            $errorIo->note($e->getMessage());

            return self::FAILURE;
        }

        $rows = [
            ['Username', $user->getUserIdentifier()],
        ];

        if ($user instanceof CredentialsHolderInterface) {
            $rows[] = ['Fullname', $user->getFullName() ?? 'No record'];
            $rows[] = ['Email', $user->getEmail() ?? 'No record'];
            $rows[] = ['Password', $user->getPassword() ?? 'No record'];
            $rows[] = ['Status', $user->isEnabled() ? 'enabled' : 'disabled'];
        } elseif ($user instanceof PasswordAuthenticatedUserInterface) {
            $rows[] = ['Password', $user->getPassword() ?? 'No record'];
        }

        if ($user instanceof UserStatusInterface) {
            $rows[] = ['Created at', $user->getCreatedAt()->format('Y-m-d H:i:s')];
            $rows[] = ['Updated at', $user->getUpdatedAt() ? $user->getUpdatedAt()->format('Y-m-d H:i:s') : 'No record'];
            $rows[] = ['Last login', $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : 'No record'];
            $rows[] = ['Location', $user->getLocation() ?? 'No record'];
            $rows[] = ['Locked', $user->isLocked() ? 'Yes' : 'No'];
        }

        $errorIo->table(['User', 'Credentials'], $rows);

        return self::SUCCESS;
    }

    /**
     * Create the user question to ask the user for the username or email to be encoded.
     */
    private function createIdentityQuestion(): Question
    {
        $identityQuestion = new Question('Type in a username or email to proceed');

        return $identityQuestion->setValidator(function ($value) {
            if ('' === \trim($value)) {
                throw new InvalidArgumentException('The user\'s identity must not be empty.');
            }

            return $value;
        })->setMaxAttempts(20);
    }
}
