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
 */
final class TranslationNodeVisitor extends AbstractTranslationNodeVisitor
{
    public const UNDEFINED_DOMAIN = '_undefined';

    private bool $enabled = false;

    /** @var array<Message> */
    private array $messages = [];

    public function enable(): void
    {
        $this->enabled = true;
        $this->messages = [];
    }

    public function disable(): void
    {
        $this->enabled = false;
        $this->messages = [];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

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
            $arguments = $node->hasNode('arguments') ? $node->getNode('arguments') : null;

            // extract constant nodes with a trans filter
            $this->messages[] = new Message(
                original: (string) $node->getNode('node')->getAttribute('value'),
                line:     $node->getTemplateLine(),
                plural:   $arguments?->hasNode('3')
                              ? (string) $arguments->getNode('3')->getAttribute('value')
                              : null,
                domain:   $this->getReadDomainFromArguments($node->getNode('arguments')),
            );
        } elseif ($node instanceof TransNode) {
            // extract trans nodes
            $this->messages[] = new Message(
                original: (string) $node->getNode('body')->getAttribute('data'),
                line:     $node->getTemplateLine(),
                plural:   $node->hasNode('plural')
                              ? (string) $node->getNode('plural')->getAttribute('data')
                              : null,
                domain:   $node->hasNode('domain')
                              ? $this->getReadDomainFromNode($node->getNode('domain'))
                              : null,
                notes:    $node->hasNode('notes')
                              ? (string) $node->getNode('notes')->getAttribute('data')
                              : null,
                context:  $node->hasNode('context')
                              ? (string) $node->getNode('context')->getAttribute('data')
                              : null,
            );
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function getReadDomainFromArguments(Node $arguments): ?string
    {
        if ($arguments->hasNode('1')) {
            $argument = $arguments->getNode('1');
            return $this->getReadDomainFromNode($argument);
        }

        return null;
    }

    private function getReadDomainFromNode(Node $node): string
    {
        if ($node instanceof ConstantExpression) {
            return (string) $node->getAttribute('value');
        }

        return self::UNDEFINED_DOMAIN;
    }
}