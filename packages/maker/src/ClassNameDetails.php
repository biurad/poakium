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

namespace BiuradPHP\Scaffold;

use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Property;
use Nette\Utils\Validators;
use Nette\PhpGenerator\PhpNamespace;

final class ClassNameDetails
{
    private $fullClassName;
    private $namespacePrefix;
    private $suffix;

    private $properties = [];
    private $constants = [];
    private $methods = [];
    private $traits = [];
    private $uses = [];
    private $implements = [];
    private $extends = [];
    private $comments = [];

    public function __construct(string $fullClassName, string $namespacePrefix, string $suffix = null)
    {
        $this->fullClassName = $fullClassName;
        $this->namespacePrefix = trim($namespacePrefix, '\\');
        $this->suffix = $suffix;
    }

    public function getFullName(): string
    {
        return $this->fullClassName;
    }

    public function getFullNamespace(): string
    {
        return $this->namespacePrefix;
    }

    public function getShortName(): string
    {
        return HelperUtil::getShortClassName($this->fullClassName);
    }

    /**
     * Returns the original class name the user entered (after
     * being cleaned up).
     *
     * For example, assuming the namespace is App\Entity:
     *      App\Entity\Admin\User => Admin\User
     */
    public function getRelativeName(): string
    {
        return str_replace($this->namespacePrefix.'\\', '', $this->fullClassName);
    }

    public function getRelativeNameWithoutSuffix(): string
    {
        return HelperUtil::removeSuffix($this->getRelativeName(), $this->suffix);
    }

    /**
     * Add a proprety's details to class information.
     *
     * @param Property $property
     *
     * @return self
     */
    public function addProperty(Property $property): self
    {
        $this->properties[] = $property->setInitialized(true);

        return $this;
    }

    /**
     * Add a method's details to class information.
     *
     * @param Method $method
     * @return self
     */
    public function addMethod(Method $method): self
    {
        $this->methods[] = $method;

        return $this;
    }


    /**
     * Add constant to class information.
     *
     * @param Constant $constant
     * @return self
     */
    public function addConstant(Constant $constant): self
    {
        $this->constants[] = $constant;

        return $this;
    }

    /**
     * Add interfaces to class information.
     *
     * @param array $implements
     *
     * @return self
     */
    public function setImplements(array $implements): self
    {
        foreach ($implements as $implement) {
            Validators::assert($implement, 'interface');
            $this->implements[] = $implement;
        }

        return $this;
    }

    /**
     * Add traits to class information.
     *
     * @param array $traits
     *
     * @return self
     */
    public function setTraits(array $traits): self
    {
        foreach ($traits as $trait) {
            Validators::assert($trait, 'trait');
            $this->traits[] = $trait;
        }

        return $this;
    }

    /**
     * Add extended class to class information.
     *
     * @param string $extend
     *
     * @return self
     */
    public function setExtended(string $extend): self
    {
        $this->extends = $extend;

        return $this;
    }

    /**
     * Add comments to class information.
     *
     * @param array $comments
     *
     * @return self
     */
    public function setComments(array $comments): self
    {
        foreach ($comments as $comment) {
            Validators::assert($comment, 'string');
            $this->comments[] = $comment;
        }

        return $this;
    }

    /**
     * Add classes, interfaces use statements to class information,
     * alias can be set null if not in use.
     *
     * eg: [MakerDeclareInterface::class => 'Maker'];
     *
     * @param array $uses An associative array of a class,interface mapping to an alias.
     *
     * @return self
     */
    public function setUses(array $uses): self
    {
        foreach ($uses as $use => $alias) {
            if (class_exists($use) || interface_exists($use)) {
                $this->uses[$use] = $alias;
            }
        }

        return $this;
    }

    /**
     * Generate qualified class from its information.
     *
     * @param PhpNamespace $namespace
     */
    public function generate(PhpNamespace $namespace): void
    {
        foreach ($this->uses as $use => $alias) {
            $namespace->addUse($use, $alias);
        }

        // This will create a psr standard php file
        $classType = $namespace->addClass($this->getRelativeName());
        $classType->setExtends($this->extends);
        $classType->setImplements($this->implements);
        $classType->setComment(join("\n", $this->comments));
        $classType->setTraits($this->traits);
        $classType->setConstants($this->constants);
        $classType->setProperties($this->properties);
        $classType->setMethods($this->methods);
    }
}
