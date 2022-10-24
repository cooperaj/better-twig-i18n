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
        if (!$node instanceof MethodCall) {
            return null;
        }

        /** @var Node\Identifier $name */
        $name = $node->name;
        if ($name->toString() !== 'translate') {
            return null;
        }

        /** @var array<Arg> $args */
        $args = $node->args;
        if (
            count($args) > 0
            && $args[TranslateFunctionArgument::Message->value]->value instanceof Node\Scalar\String_
        ) {
            $this->messages[] = [
                $this->extractArgument($args, TranslateFunctionArgument::Message),
                $this->extractArgument($args, TranslateFunctionArgument::Plural),
                $this->extractArgument($args, TranslateFunctionArgument::Domain),
                null,
                $this->extractArgument($args, TranslateFunctionArgument::Context),
                $node->getStartLine()
            ];
        }
    }

    /**
     * @param array<Arg>                $args
     * @param TranslateFunctionArgument $index
     * @return string|null
     */
    private function extractArgument(array $args, TranslateFunctionArgument $index): ?string
    {
        $index = $index->value;
        if (! isset($args[$index])) {
            return null;
        }

        $value = $args[$index]->value;

        if (! $value instanceof Node\Scalar\String_) {
            return null;
        }

        return $value->value;
    }
}
