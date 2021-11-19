<?php

/*
 * This file based on one originally part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

use Acpr\I18n\Node\TransNode;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Node;

/**
 * TranslationNodeVisitor extracts translation messages.
 *
 * Altered slightly from the stock Twig Bridge node visitor so that it returns additional context
 * in the form of template line numbers when parsing messages out of twig templates.
 *
 * Removes transchoice as a type of node that can be visited as that is old syntax.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Adam Cooper <adam@acpr.dev>
 */
final class TranslationNodeVisitor extends AbstractTranslationNodeVisitor
{
    public const UNDEFINED_DOMAIN = '_undefined';

    private bool $enabled = false;
    private array $messages = [];

    /**
     * {@inheritdoc}
     */
    public function enable(): void
    {
        $this->enabled = true;
        $this->messages = [];
    }

    /**
     * {@inheritdoc}
     */
    public function disable(): void
    {
        $this->enabled = false;
        $this->messages = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if (!$this->enabled) {
            return $node;
        }

        if (
            $node instanceof FilterExpression &&
            'trans' === $node->getNode('filter')->getAttribute('value') &&
            $node->getNode('node') instanceof ConstantExpression
        ) {
            // extract constant nodes with a trans filter
            $this->messages[] = [
                $node->getNode('node')->getAttribute('value'),
                $node->hasNode('plural') ? $node->getNode('plural')->getAttribute('data') : null,
                $this->getReadDomainFromArguments($node->getNode('arguments'), 1),
                null, # no notes yet.
                null, # no context either.
                $node->getTemplateLine()
            ];
        } elseif ($node instanceof TransNode) {
            // extract trans nodes
            $this->messages[] = [
                $node->getNode('body')->getAttribute('data'),
                $node->hasNode('plural') ? $node->getNode('plural')->getAttribute('data') : null,
                $node->hasNode('domain') ? $this->getReadDomainFromNode($node->getNode('domain')) : null,
                $node->hasNode('notes') ? $node->getNode('notes')->getAttribute('data') : null,
                $node->hasNode('context') ? $node->getNode('context')->getAttribute('data') : null,
                $node->getTemplateLine()
            ];
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 0;
    }

    private function getReadDomainFromArguments(Node $arguments, int $index): ?string
    {
        if ($arguments->hasNode('domain')) {
            $argument = $arguments->getNode('domain');
        } elseif ($arguments->hasNode((string) $index)) {
            $argument = $arguments->getNode((string) $index);
        } else {
            return null;
        }

        return $this->getReadDomainFromNode($argument);
    }

    private function getReadDomainFromNode(Node $node): ?string
    {
        if ($node instanceof ConstantExpression) {
            return $node->getAttribute('value');
        }

        return self::UNDEFINED_DOMAIN;
    }
}