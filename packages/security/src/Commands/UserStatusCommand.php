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

namespace BiuradPHP\Security\Commands;

use BiuradPHP\Security\User\UserFirewall;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use BiuradPHP\Security\Interfaces\CredentialsHolderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Throwable;

/**
 * Get a user's status.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class UserStatusCommand extends Command
{
    protected static $defaultName = 'security:user-status';

    private $firewall;
    private $provider;

    public function __construct(UserProviderInterface $provider, UserFirewall $firewall)
    {
        $this->firewall = $firewall;
        $this->provider = $provider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Check a registered user\'s status')
            ->addArgument('user', InputArgument::OPTIONAL, 'The User\'s identity that can be access in the website')
            ->setHelp(<<<EOF

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
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $errorIo = $output instanceof ConsoleOutputInterface ? new SymfonyStyle($input, $output->getErrorOutput()) : $io;

        $input->isInteractive() ? $errorIo->title('BiuradPHP User Utility') : $errorIo->newLine();

        $identity = $input->getArgument('user');

        if (!$identity) {
            if (!$input->isInteractive()) {
                $errorIo->error('The user\'s username or email should be provided and must not be empty.');

                return 1;
            }

            $identityQuestion = $this->createIdentityQuestion();
            $identity = $errorIo->askQuestion($identityQuestion);
        }

        if (is_string($user = $this->firewall->checkUserExistence($this->provider, $identity))) {
            $errorIo->note($user);
            return 1;
        }

        $rows = [
            ['Username', $user->getUsername() ?? 'Not provided'],
            ['Status', $user->isEnabled() ? 'enabled' : 'disabled'],
        ];

        if ($user instanceof CredentialsHolderInterface) {
            $rows[] = ['Fullname', $user->getFullName()];
            $rows[] = ['Email', $user->getEmail()];
            $rows[] = ['Encoded password', $user->getPassword()];
            $rows[] = ['IP address', $user->getIpaddress() ?? 'No record'];
            $rows[] = ['Last login', $user->getLastLogin() ?? 'No record'];
        } else {
            $rows[] = ['Plain password', $user->getPassword()];
        }

        $io->table(['User', 'Credentials'], $rows);

        return 0;
    }

    /**
     * Create the user question to ask the user for the username or email to be encoded.
     */
    private function createIdentityQuestion(): Question
    {
        $identityQuestion = new Question('Type in a username or email to proceed');

        return $identityQuestion->setValidator(function ($value) {
            if ('' === trim($value)) {
                throw new InvalidArgumentException('The user\'s identity must not be empty.');
            }

            return $value;
        })->setMaxAttempts(20);
    }
}
