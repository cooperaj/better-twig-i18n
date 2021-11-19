<?php

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeVisitorAbstract;

final class PhpParserNodeVisitor extends NodeVisitorAbstract
{
    private array $messages;

    public function __construct()
    {
        $this->messages = [];
    }
    
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function leaveNode(Node $node)
    {
        if (
            $node instanceof MethodCall
            && $node->name->toString() === 'translate'
            && count($node->args) > 0
            && $node->args[0]->value instanceof Node\Scalar\String_
        ) {
            $this->messages[] = [
                $node->args[0]->value->value,
                $this->isValidArgument($node->args, 4) ? $node->args[4]->value->value : null, // plural
                $this->isValidArgument($node->args, 2) ? $node->args[2]->value->value : null, // domain
                null,
                $this->isValidArgument($node->args, 3) ? $node->args[3]->value->value : null, // context
                $node->getStartLine()
            ];
        }
    }

    /**
     * @param Arg[] $args
     * @param int $index
     * @return bool
     */
    protected function isValidArgument(array $args, int $index): bool
    {
        return isset($args[$index]) &&
            $args[$index]->value instanceof Node\Scalar\String_;
    }
}