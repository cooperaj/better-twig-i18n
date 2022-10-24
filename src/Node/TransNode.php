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

final class TransNode extends Node
{
    public function __construct(
        Node $original,
        ?Node $plural = null,
        ?Node $domain = null,
        ?AbstractExpression $count = null,
        ?AbstractExpression $vars = null,
        ?TextNode $notes = null,
        ?TextNode $context = null,
        int $lineno = 0,
        ?string $tag = null
    ) {
        $nodes = ['body' => $original];

        if ($plural !== null) {
            $nodes['plural'] = $plural;
        }
        if ($domain !== null) {
            $nodes['domain'] = $domain;
        }
        if ($count !== null) {
            $nodes['count'] = $count;
        }
        if ($vars !== null) {
            $nodes['vars'] = $vars;
        }
        if ($notes !== null) {
            $nodes['notes'] = $notes;
        }
        if ($context !== null) {
            $nodes['context'] = $context;
        }

        parent::__construct($nodes, [], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $defaults = new ArrayExpression([], -1);
        $vars = $this->getNode('vars');

        if ($this->hasNode('vars') && $vars instanceof ArrayExpression) {
            $defaults = $vars;
            $vars = null;
        }

        [$msg, $plural, $defaults] = $this->compileString(
            $this->getNode('body'),
            $defaults,
            $this->hasNode('count') ? $this->getNode('plural') : null,
            (bool) $vars,
        );

        $compiler
            ->write('echo $this->env->getExtension(\'Acpr\I18n\TranslationExtension\')->trans(')
            ->subcompile($msg)
        ;

        $compiler->raw(', ');

        if ($vars !== null) {
            $compiler
                ->raw('array_merge(')
                ->subcompile($defaults)
                ->raw(', ')
                ->subcompile($vars)
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
                    trim((string) $this->getNode('context')->getAttribute('data')),
                    $this->getNode('context')->getTemplateLine()
                )
            );
        } else {
            $compiler->raw('null');
        }

        if ($this->hasNode('count')) {
            /** @psalm-var Node $plural */
            $compiler
                ->raw(', ')
                ->subcompile($plural)
                ->raw(', ')
                ->subcompile($this->getNode('count'))
            ;
        }

        $compiler->raw(");\n");
    }

    /**
     * @param Node            $body
     * @param ArrayExpression $vars
     * @param Node|null       $plural
     * @param bool            $ignoreStrictCheck
     *
     * @return array
     * @psalm-return array{0: Node, 1: ?Node, 2: ArrayExpression}
     */
    private function compileString(
        Node $body,
        ArrayExpression $vars,
        ?Node $plural = null,
        bool $ignoreStrictCheck = false
    ): array {
        /** @var string $msg */
        $msg = $body->getAttribute('data');

        $pmsg = '';
        if ($plural !== null) {
            /** @var string $pmsg */
            $pmsg = $plural->getAttribute('data');
        }

        // for the purposes of figuring out default variable substitution values concatenate the
        // message and plural strings together.
        preg_match_all('/(?<!%)%([^%]+)%/', $msg . $pmsg, $matches);

        foreach ($matches[1] as $var) {
            $key = new ConstantExpression('%' . $var . '%', $body->getTemplateLine());
            if (!$vars->hasElement($key)) {
                if ('count' === $var && $this->hasNode('count')) {
                    /** @var AbstractExpression $countNode */
                    $countNode = $this->getNode('count');
                    $vars->addElement($countNode, $key);
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
