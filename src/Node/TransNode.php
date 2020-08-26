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

namespace Acpr\I18n\Node;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Adam Cooper <adam@acpr.dev>
 */
final class TransNode extends Node
{
    public function __construct(
        Node $original,
        Node $plural = null,
        Node $domain = null,
        AbstractExpression $count = null,
        AbstractExpression $vars = null,
        TextNode $notes = null,
        TextNode $context = null,
        int $lineno = 0,
        string $tag = null
    ) {
        $nodes = ['body' => $original];

        if (null !== $plural) {
            $nodes['plural'] = $plural;
        }
        if (null !== $domain) {
            $nodes['domain'] = $domain;
        }
        if (null !== $count) {
            $nodes['count'] = $count;
        }
        if (null !== $vars) {
            $nodes['vars'] = $vars;
        }
        if (null !== $notes) {
            $nodes['notes'] = $notes;
        }
        if (null !== $context) {
            $nodes['context'] = $context;
        }

        parent::__construct($nodes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $defaults = new ArrayExpression([], -1);
        if ($this->hasNode('vars') && ($vars = $this->getNode('vars')) instanceof ArrayExpression) {
            $defaults = $this->getNode('vars');
            $vars = null;
        }
        [$msg, $plural, $defaults] = $this->compileString(
            $this->getNode('body'),
            $defaults,
            $this->hasNode('count') ? $this->getNode('plural') : null,
            (bool) $vars
        );

        $compiler
            ->write('echo $this->env->getExtension(\'Acpr\I18n\TranslationExtension\')->trans(')
            ->subcompile($msg)
        ;

        $compiler->raw(', ');

        if (null !== $vars) {
            $compiler
                ->raw('array_merge(')
                ->subcompile($defaults)
                ->raw(', ')
                ->subcompile($this->getNode('vars'))
                ->raw(')')
            ;
        } else {
            $compiler->subcompile($defaults);
        }

        $compiler->raw(', ');

        if (!$this->hasNode('domain')) {
            $compiler->repr('messages');
        } else {
            $compiler->subcompile($this->getNode('domain'));
        }

        $compiler->raw(', ');

        if ($this->hasNode('context')) {
            $compiler->subcompile(
                new ConstantExpression(
                    trim($this->getNode('context')->getAttribute('data')),
                    $this->getNode('context')->getTemplateLine()
                )
            );
        } else {
            $compiler->raw('null');
        }

        if ($this->hasNode('count')) {
            $compiler
                ->raw(', ')
                ->subcompile($plural)
                ->raw(', ')
                ->subcompile($this->getNode('count'))
            ;
        }

        $compiler->raw(");\n");
    }

    private function compileString(
        Node $body,
        ArrayExpression $vars,
        ?Node $plural = null,
        bool $ignoreStrictCheck = false
    ): array {
        if ($body instanceof TextNode) {
            $msg = $body->getAttribute('data');
        } else {
            return [$body, $vars];
        }

        $pmsg = '';
        if ($plural !== null) {
            $pmsg = $plural->getAttribute('data');
        }

        // for the purposes of figuring out default variable substitution values concatenate the
        // message and plural strings together.
        preg_match_all('/(?<!%)%([^%]+)%/', $msg . $pmsg, $matches);

        foreach ($matches[1] as $var) {
            $key = new ConstantExpression('%'.$var.'%', $body->getTemplateLine());
            if (!$vars->hasElement($key)) {
                if ('count' === $var && $this->hasNode('count')) {
                    $vars->addElement($this->getNode('count'), $key);
                } else {
                    $varExpr = new NameExpression($var, $body->getTemplateLine());
                    $varExpr->setAttribute('ignore_strict_check', $ignoreStrictCheck);
                    $vars->addElement($varExpr, $key);
                }
            }
        }

        return [
            new ConstantExpression(
                str_replace('%%', '%', trim($msg)),
                $body->getTemplateLine()
            ),
            $plural !== null
            ? new ConstantExpression(
                str_replace('%%', '%', trim($pmsg)),
                $body->getTemplateLine()
            )
            : null,
            $vars
        ];
    }
}
