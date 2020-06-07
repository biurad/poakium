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

use PhpParser\Builder;
use PhpParser\BuilderHelpers;
use PhpParser\Comment\Doc;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Injects needed class dependencies into given source code.
 */
final class ClassInjector
{
    public const CONTEXT_OUTSIDE_CLASS = 'outside_class';
    public const CONTEXT_CLASS = 'class';
    public const CONTEXT_CLASS_METHOD = 'class_method';

    /** @var Parser */
    private $parser;

    /** @var Lexer */
    private $lexer;

    /** @var null|Standard|PrettyPrinterAbstract */
    private $printer;

    /** @var SymfonyStyle|null */
    private $io;

    private $overwrite;
    private $useAnnotations;
    private $fluentMutators;
    private $sourceCode;
    private $oldStmts;
    private $oldTokens;
    private $newStmts;

    private $pendingComments = [];

    public function __construct(string $sourceCode, bool $overwrite = false, bool $useAnnotations = true, bool $fluentMutators = true)
    {
        $this->overwrite = $overwrite;
        $this->useAnnotations = $useAnnotations;
        $this->fluentMutators = $fluentMutators;
        $this->lexer = new Lexer\Emulative([
            'usedAttributes' => [
                'comments',
                'startLine',
                'endLine',
                'startTokenPos',
                'endTokenPos',
            ],
        ]);
        $this->parser = new Parser\Php7($this->lexer);
        $this->printer = new PrettyPrinter();

        $this->setSourceCode(file_exists($sourceCode) ? file_get_contents($sourceCode) : $sourceCode);
    }

    public function setIo(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    private function setSourceCode(string $sourceCode): void
    {
        $this->sourceCode = $sourceCode;
        $this->oldStmts = $this->parser->parse($sourceCode);
        $this->oldTokens = $this->lexer->getTokens();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\CloningVisitor());
        $traverser->addVisitor(new NodeVisitor\NameResolver(null, [
            'replaceNodes' => false,
        ]));
        $this->newStmts = $traverser->traverse($this->oldStmts);
    }

    /**
     * Inject dependencies into PHP Class source code. Attention, resulted code will attempt to
     * preserve formatting but will affect it. Do not forget to add formatting fixer.
     *
     * @return void
     */
    private function updateSourceCodeFromNewStmts(): void
    {
        $newCode = $this->printer->printFormatPreserving(
            $this->newStmts,
            $this->oldStmts,
            $this->oldTokens
        );

        // replace the 3 "fake" items that may be in the code (allowing for different indentation)
        $newCode = preg_replace('/(\ |\t)*private\ \$__EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/use __EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/(\ |\t)*\$__EXTRA__LINE;/', '', $newCode);

        // process comment lines
        foreach ($this->pendingComments as $i => $comment) {
            // sanity check
            $placeholder = sprintf('$__COMMENT__VAR_%d;', $i);
            if (false === strpos($newCode, $placeholder)) {
                // this can happen if a comment is createSingleLineCommentNode()
                // is called, but then that generated code is ultimately not added
                continue;
            }

            $newCode = str_replace($placeholder, '// '.$comment, $newCode);
        }
        $this->pendingComments = [];

        $this->setSourceCode($newCode);
    }

    public function addInterface(string $interfaceName)
    {
        $this->addUseStatementIfNecessary($interfaceName);

        $this->getClassNode()->implements[] = new Node\Name(HelperUtil::getShortClassName($interfaceName));
        $this->updateSourceCodeFromNewStmts();
    }

    public function addAccessorMethod(string $propertyName, string $methodName, $returnType, bool $isReturnTypeNullable, array $commentLines = [], $typeCast = null)
    {
        $this->addCustomGetter($propertyName, $methodName, $returnType, $isReturnTypeNullable, $commentLines, $typeCast);
    }

    public function addGetter(string $propertyName, $returnType, bool $isReturnTypeNullable, array $commentLines = [])
    {
        $methodName = 'get'.HelperUtil::asCamelCase($propertyName);

        $this->addCustomGetter($propertyName, $methodName, $returnType, $isReturnTypeNullable, $commentLines);
    }

    public function addSetter(string $propertyName, $type, bool $isNullable, array $commentLines = [])
    {
        $builder = $this->createSetterNodeBuilder($propertyName, $type, $isNullable, $commentLines);
        $this->makeMethodFluent($builder);
        $this->addMethod($builder->getNode());
    }

    public function addMethodBuilder(Builder\Method $methodBuilder)
    {
        $this->addMethod($methodBuilder->getNode());
    }

    public function addMethodBody(Builder\Method $methodBuilder, string $methodBody)
    {
        $nodes = $this->parser->parse($methodBody);
        $methodBuilder->addStmts($nodes);
    }

    public function createMethodBuilder(string $methodName, $returnType, bool $isReturnTypeNullable, array $commentLines = []): Builder\Method
    {
        $methodNodeBuilder = (new Builder\Method($methodName))
            ->makePublic()
        ;

        if (null !== $returnType) {
            $methodNodeBuilder->setReturnType($isReturnTypeNullable ? new Node\NullableType($returnType) : $returnType);
        }

        if ($commentLines) {
            $methodNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        return $methodNodeBuilder;
    }

    public function createMethodLevelCommentNode(string $comment)
    {
        return $this->createSingleLineCommentNode($comment, self::CONTEXT_CLASS_METHOD);
    }

    public function createMethodLevelBlankLine()
    {
        return $this->createBlankLineNode(self::CONTEXT_CLASS_METHOD);
    }

    public function addProperty(string $name, array $annotationLines = [], $defaultValue = null)
    {
        if ($this->propertyExists($name)) {
            // we never overwrite properties
            return;
        }

        $newPropertyBuilder = (new Builder\Property($name))->makePrivate();
        if ($annotationLines && $this->useAnnotations) {
            $newPropertyBuilder->setDocComment($this->createDocBlock($annotationLines));
        }

        if (null !== $defaultValue) {
            $newPropertyBuilder->setDefault($defaultValue);
        }
        $newPropertyNode = $newPropertyBuilder->getNode();

        $this->addNodeAfterProperties($newPropertyNode);
    }

    public function addAnnotationToClass(string $annotationClass, array $options)
    {
        $annotationClassAlias = $this->addUseStatementIfNecessary($annotationClass);
        $docComment = $this->getClassNode()->getDocComment();

        $docLines = $docComment ? explode("\n", $docComment->getText()) : [];
        if (0 === \count($docLines)) {
            $docLines = ['/**', ' */'];
        } elseif (1 === \count($docLines)) {
            // /** inline doc syntax */
            // imperfect way to try to find where to split the lines
            $endOfOpening = strpos($docLines[0], '* ');
            $endingPosition = strrpos($docLines[0], ' *', $endOfOpening);
            $extraComments = trim(substr($docLines[0], $endOfOpening + 2, $endingPosition - $endOfOpening - 2));
            $newDocLines = [
                substr($docLines[0], 0, $endOfOpening + 1),
            ];

            if ($extraComments) {
                $newDocLines[] = ' * '.$extraComments;
            }

            $newDocLines[] = substr($docLines[0], $endingPosition);
            $docLines = $newDocLines;
        }

        array_splice(
            $docLines,
            \count($docLines) - 1,
            0,
            ' * '.HelperUtil::buildAnnotationLine('@'.$annotationClassAlias, $options)
        );

        $docComment = new Doc(implode("\n", $docLines));
        $this->getClassNode()->setDocComment($docComment);
        $this->updateSourceCodeFromNewStmts();
    }

    private function addCustomGetter(string $propertyName, string $methodName, $returnType, bool $isReturnTypeNullable, array $commentLines = [], $typeCast = null)
    {
        $propertyFetch = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName);

        if (null !== $typeCast) {
            switch ($typeCast) {
                case 'string':
                    $propertyFetch = new Node\Expr\Cast\String_($propertyFetch);
                    break;
                case 'int':
                    $propertyFetch = new Node\Expr\Cast\Int_($propertyFetch);
                    break;
                case 'double':
                    $propertyFetch = new Node\Expr\Cast\Double($propertyFetch);
                    break;
                case 'bool':
                case 'boolean':
                    $propertyFetch = new Node\Expr\Cast\Bool_($propertyFetch);
                    break;
                case 'array':
                    $propertyFetch = new Node\Expr\Cast\Array_($propertyFetch);
                    break;
                case 'object':
                    $propertyFetch = new Node\Expr\Cast\Object_($propertyFetch);
                    break;
                case 'unset':
                    $propertyFetch = new Node\Expr\Cast\Unset_($propertyFetch);
                    break;
                default:
                    // implement other cases if/when the library needs them
                    throw new \Exception('Not implemented');
            }
        }

        $getterNodeBuilder = (new Builder\Method($methodName))
            ->makePublic()
            ->addStmt(
                new Node\Stmt\Return_($propertyFetch)
            )
        ;

        if (null !== $returnType) {
            $getterNodeBuilder->setReturnType($isReturnTypeNullable ? new Node\NullableType($returnType) : $returnType);
        }

        if ($commentLines) {
            $getterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $this->addMethod($getterNodeBuilder->getNode());
    }

    private function createSetterNodeBuilder(string $propertyName, $type, bool $isNullable, array $commentLines = [])
    {
        $methodName = 'set'.HelperUtil::asCamelCase($propertyName);
        $setterNodeBuilder = (new Builder\Method($methodName))->makePublic();

        if ($commentLines) {
            $setterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $paramBuilder = new Builder\Param($propertyName);
        if (null !== $type) {
            $paramBuilder->setTypeHint($isNullable ? new Node\NullableType($type) : $type);
        }
        $setterNodeBuilder->addParam($paramBuilder->getNode());

        $setterNodeBuilder->addStmt(
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                new Node\Expr\Variable($propertyName)
            ))
        );

        return $setterNodeBuilder;
    }

    public function addStatementToConstructor(Node\Stmt $stmt)
    {
        if (!$this->getConstructorNode()) {
            $constructorNode = (new Builder\Method('__construct'))->makePublic()->getNode();

            // add call to parent::__construct() if there is a need to
            try {
                $ref = new \ReflectionClass($this->getThisFullClassName());

                if ($ref->getParentClass() && $ref->getParentClass()->getConstructor()) {
                    $constructorNode->stmts[] = new Node\Stmt\Expression(
                        new Node\Expr\StaticCall(new Node\Name('parent'), new Node\Identifier('__construct'))
                    );
                }
            } catch (\ReflectionException $e) {
            }

            $this->addNodeAfterProperties($constructorNode);
        }

        $constructorNode = $this->getConstructorNode();
        $constructorNode->stmts[] = $stmt;
        $this->updateSourceCodeFromNewStmts();
    }

    /**
     * @return Node\Stmt\ClassMethod|null
     *
     * @throws \Exception
     */
    private function getConstructorNode()
    {
        foreach ($this->getClassNode()->stmts as $classNode) {
            if ($classNode instanceof Node\Stmt\ClassMethod && '__construct' == $classNode->name) {
                return $classNode;
            }
        }

        return null;
    }

    /**
     * @return string The alias to use when referencing this class
     */
    public function addUseStatementIfNecessary(string $class): string
    {
        $shortClassName = HelperUtil::getShortClassName($class);
        if ($this->isInSameNamespace($class)) {
            return $shortClassName;
        }

        $namespaceNode = $this->getNamespaceNode();

        $targetIndex = null;
        $addLineBreak = false;
        $lastUseStmtIndex = null;
        foreach ($namespaceNode->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                // I believe this is an array to account for use statements with {}
                foreach ($stmt->uses as $use) {
                    $alias = $use->alias ? $use->alias->name : $use->name->getLast();

                    // the use statement already exists? Don't add it again
                    if ($class === (string) $use->name) {
                        return $alias;
                    }

                    if ($alias === $shortClassName) {
                        // we have a conflicting alias!
                        // to be safe, use the fully-qualified class name
                        // everywhere and do not add another use statement
                        return '\\'.$class;
                    }
                }

                // if $class is alphabetically before this use statement, place it before
                // only set $targetIndex the first time you find it
                if (null === $targetIndex && HelperUtil::areClassesAlphabetical($class, (string) $stmt->uses[0]->name)) {
                    $targetIndex = $index;
                }

                $lastUseStmtIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Class_) {
                if (null !== $targetIndex) {
                    // we already found where to place the use statement

                    break;
                }

                // we hit the class! If there were any use statements,
                // then put this at the bottom of the use statement list
                if (null !== $lastUseStmtIndex) {
                    $targetIndex = $lastUseStmtIndex + 1;
                } else {
                    $targetIndex = $index;
                    $addLineBreak = true;
                }

                break;
            }
        }

        if (null === $targetIndex) {
            throw new \Exception('Could not find a class!');
        }

        $newUseNode = (new Builder\Use_($class, Node\Stmt\Use_::TYPE_NORMAL))->getNode();
        array_splice(
            $namespaceNode->stmts,
            $targetIndex,
            0,
            $addLineBreak ? [$newUseNode, $this->createBlankLineNode(self::CONTEXT_OUTSIDE_CLASS)] : [$newUseNode]
        );

        $this->updateSourceCodeFromNewStmts();

        return $shortClassName;
    }

    private function getClassNode(): Node\Stmt\Class_
    {
        $node = $this->findFirstNode(function ($node) {
            return $node instanceof Node\Stmt\Class_;
        });

        if (!$node) {
            throw new \Exception('Could not find class node');
        }

        return $node;
    }

    private function getNamespaceNode(): Node\Stmt\Namespace_
    {
        $node = $this->findFirstNode(function ($node) {
            return $node instanceof Node\Stmt\Namespace_;
        });

        if (!$node) {
            throw new \Exception('Could not find namespace node');
        }

        return $node;
    }

    /**
     * @return Node|null
     */
    private function findFirstNode(callable $filterCallback)
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FirstFindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->newStmts);

        return $visitor->getFoundNode();
    }

    /**
     * @return Node|null
     */
    private function findLastNode(callable $filterCallback, array $ast)
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $nodes = $visitor->getFoundNodes();
        $node = end($nodes);

        return false === $node ? null : $node;
    }

    private function createBlankLineNode(string $context)
    {
        switch ($context) {
            case self::CONTEXT_OUTSIDE_CLASS:
                return (new Builder\Use_('__EXTRA__LINE', Node\Stmt\Use_::TYPE_NORMAL))
                    ->getNode()
                ;
            case self::CONTEXT_CLASS:
                return (new Builder\Property('__EXTRA__LINE'))
                    ->makePrivate()
                    ->getNode()
                ;
            case self::CONTEXT_CLASS_METHOD:
                return new Node\Expr\Variable('__EXTRA__LINE');
            default:
                throw new \Exception('Unknown context: '.$context);
        }
    }

    private function createSingleLineCommentNode(string $comment, string $context)
    {
        $this->pendingComments[] = $comment;
        switch ($context) {
            case self::CONTEXT_OUTSIDE_CLASS:
                // just not needed yet
                throw new \Exception('not supported');
            case self::CONTEXT_CLASS:
                // just not needed yet
                throw new \Exception('not supported');
            case self::CONTEXT_CLASS_METHOD:
                return BuilderHelpers::normalizeStmt(new Node\Expr\Variable(sprintf('__COMMENT__VAR_%d', \count($this->pendingComments) - 1)));
            default:
                throw new \Exception('Unknown context: '.$context);
        }
    }

    private function createDocBlock(array $commentLines)
    {
        $docBlock = "/**\n";
        foreach ($commentLines as $commentLine) {
            if ($commentLine) {
                $docBlock .= " * $commentLine\n";
            } else {
                // avoid the empty, extra space on blank lines
                $docBlock .= " *\n";
            }
        }
        $docBlock .= "\n */";

        return $docBlock;
    }

    private function addMethod(Node\Stmt\ClassMethod $methodNode)
    {
        $classNode = $this->getClassNode();
        $methodName = $methodNode->name;
        $existingIndex = null;
        if ($this->methodExists((string) $methodName)) {
            if (!$this->overwrite) {
                $this->writeNote(sprintf(
                    'Not generating <info>%s::%s()</info>: method already exists',
                    HelperUtil::getShortClassName($this->getThisFullClassName()),
                    $methodName
                ));

                return;
            }

            // record, so we can overwrite in the same place
            $existingIndex = $this->getMethodIndex((string) $methodName);
        }

        $newStatements = [];

        // put new method always at the bottom
        if (!empty($classNode->stmts)) {
            $newStatements[] = $this->createBlankLineNode(self::CONTEXT_CLASS);
        }

        $newStatements[] = $methodNode;

        if (null === $existingIndex) {
            // add them to the end!

            $classNode->stmts = array_merge($classNode->stmts, $newStatements);
        } else {
            array_splice(
                $classNode->stmts,
                $existingIndex,
                1,
                $newStatements
            );
        }

        $this->updateSourceCodeFromNewStmts();
    }

    private function makeMethodFluent(Builder\Method $methodBuilder)
    {
        if (!$this->fluentMutators) {
            return;
        }

        $methodBuilder
            ->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD))
            ->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('this')))
        ;
        $methodBuilder->setReturnType('self');
    }

    private function isInSameNamespace($class)
    {
        $namespace = substr($class, 0, strrpos($class, '\\'));

        return $this->getNamespaceNode()->name->toCodeString() === $namespace;
    }

    private function getThisFullClassName(): string
    {
        return (string) $this->getClassNode()->namespacedName;
    }

    /**
     * Adds this new node where a new property should go.
     *
     * Useful for adding properties, or adding a constructor.
     */
    private function addNodeAfterProperties(Node $newNode)
    {
        $classNode = $this->getClassNode();

        // try to add after last property
        $targetNode = $this->findLastNode(function ($node) {
            return $node instanceof Node\Stmt\Property;
        }, [$classNode]);

        // otherwise, try to add after the last constant
        if (!$targetNode) {
            $targetNode = $this->findLastNode(function ($node) {
                return $node instanceof Node\Stmt\ClassConst;
            }, [$classNode]);
        }

        // add the new property after this node
        if ($targetNode) {
            $index = array_search($targetNode, $classNode->stmts);

            array_splice(
                $classNode->stmts,
                $index + 1,
                0,
                [$this->createBlankLineNode(self::CONTEXT_CLASS), $newNode]
            );

            $this->updateSourceCodeFromNewStmts();

            return;
        }

        // put right at the beginning of the class
        // add an empty line, unless the class is totally empty
        if (!empty($classNode->stmts)) {
            array_unshift($classNode->stmts, $this->createBlankLineNode(self::CONTEXT_CLASS));
        }
        array_unshift($classNode->stmts, $newNode);
        $this->updateSourceCodeFromNewStmts();
    }

    private function methodExists(string $methodName): bool
    {
        return false !== $this->getMethodIndex($methodName);
    }

    private function getMethodIndex(string $methodName)
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\ClassMethod && strtolower($node->name->toString()) === strtolower($methodName)) {
                return $i;
            }
        }

        return false;
    }

    private function propertyExists(string $propertyName)
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\Property && $node->props[0]->name->toString() === $propertyName) {
                return true;
            }
        }

        return false;
    }

    private function writeNote(string $note)
    {
        if (null !== $this->io) {
            $this->io->text($note);
        }
    }
}
