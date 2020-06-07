<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  Scaffolds Maker
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/scaffoldsmaker
 * @since     Version 0.1
 */

namespace BiuradPHP\Scaffold\Bridges;

use BiuradPHP\DependencyInjection\Concerns\Compiler;
use BiuradPHP\DependencyInjection\Concerns\PassConfig;
use BiuradPHP\MVC\Application as MVCApplication;
use BiuradPHP\Scaffold\Commands\MakerCommand;
use Nette, ReflectionClass;
use BiuradPHP\Scaffold\Config\MakerConfig;
use BiuradPHP\Scaffold\Declarations\MakeMiddleware;
use BiuradPHP\Scaffold\Declarations\MakeSubscriber;
use BiuradPHP\Scaffold\Declarations\MakeUnitTest;
use BiuradPHP\Scaffold\EventListeners\ConsoleErrorSubscriber;
use BiuradPHP\Scaffold\Interfaces\MakerExtensionInterface;
use Nette\DI\Definitions\Reference;
use Nette\PhpGenerator\PhpLiteral;
use Symfony\Component\Console\Application;

class ScaffoldExtension extends Nette\DI\CompilerExtension implements MakerExtensionInterface
{
    public const MAKER_TAG = 'maker.command';

    /**
     * Whether or not we are in debug/console mode.
     *
     * @var bool
     */
    private $debug;

    public function __construct(bool $isConsoleMode = false)
    {
        $this->debug = $isConsoleMode;
    }

    /**
     * {@inheritDoc}
     */
	public function getConfigSchema(): Nette\Schema\Schema
	{
        try {
            $defaultNamespace = (new ReflectionClass(\App\Kernel::class))->getNamespaceName();
        } catch (\ReflectionException $e) {
            $defaultNamespace = '';
        }

        return Nette\Schema\Expect::structure([
                'header'        => Nette\Schema\Expect::array()->before(function ($value) {
                    return [serialize($value)];
                }),
                'root_directory'     => Nette\Schema\Expect::string()->assert('is_dir')->required(),
                'view_directory'     => Nette\Schema\Expect::string()->assert('is_dir')->required(),
                'namespace'     => Nette\Schema\Expect::string()->before(function ($value) use ($defaultNamespace) {
                    return null === $value ? $defaultNamespace : $value;
                })->default($defaultNamespace),
                'declarations'  => Nette\Schema\Expect::arrayOf(
                    Nette\Schema\Expect::structure([
                        'namespace'     => Nette\Schema\Expect::string(),
                        'postfix'       => Nette\Schema\Expect::string(),
                        'class'         => Nette\Schema\Expect::string(),
                        'options'       => Nette\Schema\Expect::array(),
                    ])->castTo('array')
                )
		])->castTo('array');
	}

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(): void
	{
        if (true !== $this->debug) {
            return;
        }

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('config'))
            ->setFactory(MakerConfig::class)
            ->setArguments([$this->config])
        ;

        // Declarations maker...
        if (null !== $builder->getByType(MVCApplication::class)) {
            $builder->addDefinition($this->prefix('make.test'))
                ->setFactory(MakeUnitTest::class);

            $builder->addDefinition($this->prefix('make.middleware'))
                ->setFactory(MakeMiddleware::class)->addTag(self::MAKER_TAG);

            $builder->addDefinition($this->prefix('make.subscriber'))
                ->setFactory(MakeSubscriber::class)->addTag(self::MAKER_TAG);

            // Event Listener...
            $builder->addDefinition($this->prefix('listener'))
                ->setFactory(ConsoleErrorSubscriber::class);
        }

        if (null !== $builder->getByType(Application::class)) {
            $builder->getDefinitionByType(Application::class)
                ->addSetup('
foreach (?->getDeclarations() as $element => $declarations) {
    ?->add($this->createInstance(?::class, [4 => $element]));
}', [new Reference(MakerConfig::class), '@self', new PhpLiteral(MakerCommand::class)]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addCompilerPasses(Compiler &$compiler): void
    {
        $compiler->addPass(new ScaffoldPassCompiler, PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
