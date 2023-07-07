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
use Acpr\I18n\UnhandledPluralisationRuleException;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class TransTokenParser extends AbstractTokenParser
{
    /**
     * @param Token $token
     *
     * @return Node
     * @throws SyntaxError
     * @throws UnhandledPluralisationRuleException
     *
     * @psalm-suppress InternalMethod
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
                /** @var AbstractExpression $count */
                $count = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('with')) {
                // {% trans with vars %}
                $stream->next();
                /** @var AbstractExpression $vars */
                $vars = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('from')) {
                // {% trans from "messages" %}
                $stream->next();
                /** @var AbstractExpression $domain */
                $domain = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('into')) {
                throw new SyntaxError(
                    'The "into" tag is not available in this iteration of the twig translation syntax.',
                    $stream->getCurrent()->getLine(),
                    $stream->getSourceContext()
                );
            } elseif (!$stream->test(Token::BLOCK_END_TYPE)) {
                throw new SyntaxError(
                    'Unexpected token. Twig was looking for the "with", "count" or "from" keyword.',
                    $stream->getCurrent()->getLine(),
                    $stream->getSourceContext()
                );
            }
        }

        // {% trans %}message{% endtrans %}
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideTransFork']);

        if (!$body instanceof TextNode) {
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
                    $notes = $this->parser->subparse([$this, 'decideTransFork']);

                    if (!$notes instanceof TextNode) {
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
                    $context = $this->parser->subparse([$this, 'decideTransFork']);

                    if (!$context instanceof TextNode) {
                        throw new SyntaxError(
                            'A message following a context tag must be a simple text.',
                            $body->getTemplateLine(),
                            $stream->getSourceContext()
                        );
                    }

                    break;
            }
        }

        // ensure we move past the {% endtrans %} we should be on
        $stream->next();
        $stream->expect(Token::BLOCK_END_TYPE);

        if ($count !== null) {
            [$body, $plural] = $this->parsePluralisation($body);
        }

        // strip whitespace of more than 1 character
        /** @var string $msg */
        $msg = $body->getAttribute('data');
        $body->setAttribute(
            'data',
            preg_replace('/\s{2,}/', ' ', $msg)
        );

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
     *
     * @param TextNode $body
     *
     * @return array<TextNode>
     * @throws UnhandledPluralisationRuleException
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @see       \Symfony\Contracts\Translation\TranslatorInterface for more information on the format.
     *
     * Copied from Symfony\Component\Translation\Dumper\PoFileDumper
     */
    private function parsePluralisation(TextNode $body): array
    {
        /** @var string $msg */
        $msg = $body->getAttribute('data');

        // Partly copied from TranslatorTrait::trans.
        $parts = [];
        if (preg_match_all('/(?:\|\||[^\|])++/', $msg, $matches)) {
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
                throw new UnhandledPluralisationRuleException(
                    'Interval based pluralisation definitions are not supported.'
                );
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
