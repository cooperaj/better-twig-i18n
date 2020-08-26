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

namespace Acpr\I18n\TokenParser;

use Acpr\I18n\Node\TransNode;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Token Parser for the 'trans' tag.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class TransTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $vars = new ArrayExpression([], $lineno);
        $notes = null;
        $context = null;
        $domain = null;
        $plural = null;

        if (!$stream->test(Token::BLOCK_END_TYPE)) {
            if ($stream->test('count')) {
                // {% trans count 5 %}
                $stream->next();
                $count = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('with')) {
                // {% trans with vars %}
                $stream->next();
                $vars = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('from')) {
                // {% trans from "messages" %}
                $stream->next();
                $domain = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('into')) {
                throw new SyntaxError(
                    'The "into" tag is not available in this iteration of the twig translation syntax',
                    $stream->getCurrent()->getLine(),
                    $stream->getSourceContext()
                );
            } elseif (!$stream->test(Token::BLOCK_END_TYPE)) {
                throw new SyntaxError(
                    'Unexpected token. Twig was looking for the "with", or "from" keyword.',
                    $stream->getCurrent()->getLine(),
                    $stream->getSourceContext()
                );
            }
        }

        // {% trans %}message{% endtrans %}
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideTransFork']);

        if (!$body instanceof TextNode && !$body instanceof AbstractExpression) {
            throw new SyntaxError(
                'A message inside a trans tag must be a simple text.',
                $body->getTemplateLine(),
                $stream->getSourceContext()
            );
        }

        while (!$stream->getCurrent()->test(['endtrans'])) {
            switch ($stream->next()->getValue()) {
                case 'notes':
                    // Provides translator notes
                    // ... {% notes %}a note ...
                    $stream->expect(Token::BLOCK_END_TYPE);
                    /** @var TextNode $notes */
                    $notes = $this->parser->subparse([$this, 'decideTransFork']);

                    if (!$notes instanceof TextNode && !$notes instanceof AbstractExpression) {
                        throw new SyntaxError(
                            'A message following a notes tag must be a simple text.',
                            $body->getTemplateLine(),
                            $stream->getSourceContext()
                        );
                    }

                    break;
                case 'context':
                    // Allows the disambiguation of msgids based on the provided context text.
                    // ... {% context %}a note ...
                    $stream->expect(Token::BLOCK_END_TYPE);
                    /** @var TextNode $notes */
                    $context = $this->parser->subparse([$this, 'decideTransFork']);

                    if (!$context instanceof TextNode && !$context instanceof AbstractExpression) {
                        throw new SyntaxError(
                            'A message following a notes tag must be a simple text.',
                            $body->getTemplateLine(),
                            $stream->getSourceContext()
                        );
                    }

                    break;
                default:
                    continue 2;
            }
        }

        // ensure we move past the {% endtrans %} we should be on
        $stream->next();
        $stream->expect(Token::BLOCK_END_TYPE);

        if ($count !== null) {
            [$body, $plural] = $this->parsePluralisation($body);
        }

        return new TransNode($body, $plural, $domain, $count, $vars, $notes, $context, $lineno, $this->getTag());
    }

    public function decideTransFork(Token $token): bool
    {
        return $token->test(['context', 'notes', 'endtrans']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTag(): string
    {
        return 'trans';
    }

    /**
     * The twig translation extension expects a delimited key/id for it's rules.
     * @see Symfony\Contracts\Translation\TranslatorInterface for more information on the format.
     *
     * Copied from Symfony\Component\Translation\Dumper\PoFileDumper
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @param TextNode $body
     * @return TextNode[]
     */
    private function parsePluralisation(TextNode $body): array
    {
        $msg = $body->getAttribute('data');

        // Partly copied from TranslatorTrait::trans.
        $parts = [];
        if (preg_match('/^\|++$/', $msg)) {
            $parts = explode('|', $msg);
        } elseif (preg_match_all('/(?:\|\||[^\|])++/', $msg, $matches)) {
            $parts = $matches[0];
        }

        $intervalRegexp = <<<'EOF'
/^(?P<interval>
    ({\s*
        (\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)
    \s*})
        |
    (?P<left_delimiter>[\[\]])
        \s*
        (?P<left>-Inf|\-?\d+(\.\d+)?)
        \s*,\s*
        (?P<right>\+?Inf|\-?\d+(\.\d+)?)
        \s*
    (?P<right_delimiter>[\[\]])
)\s*(?P<message>.*?)$/xs
EOF;

        $standardRules = [];
        foreach ($parts as $part) {
            $part = trim(str_replace('||', '|', $part));

            if (preg_match($intervalRegexp, $part)) {
                // Explicit rule is not a standard rule.
                return [];
            } else {
                $standardRules[] = $part;
            }
        }

        $body->setAttribute('data', $standardRules[0]);

        $plural = clone $body;
        $plural->setAttribute('data', $standardRules[1]);

        return [$body, $plural];
    }
}