<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Monorepo;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A monorepo workflow worker implementation.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface WorkerInterface
{
    /**
     * A process title message.
     */
    public function getDescription(): string;

    /**
     * This method is called in the workflow's execute command method.
     */
    public function work(Monorepo $repo, InputInterface $input, SymfonyStyle $output): int;

    /**
     * This method is called in the workflow's configure command method.
     */
    public static function configure(WorkflowCommand $command): self;
}
