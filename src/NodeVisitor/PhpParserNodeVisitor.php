<?php

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeVisitorAbstract;

final class PhpParserNodeVisitor extends NodeVisitorAbstract
{
    /** @var array<Message>  */
    private array $messages;

    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * @return array<Message>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    #[Override]
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
            /** @var string $original */
            $original = $this->extractArgument($args, TranslateFunctionArgument::Message);

            $this->messages[] = new Message(
                original: $original,
                line: $node->getStartLine(),
                plural: $this->extractArgument($args, TranslateFunctionArgument::Plural),
                domain: $this->extractArgument($args, TranslateFunctionArgument::Domain),
                context: $this->extractArgument($args, TranslateFunctionArgument::Context),
            );
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
