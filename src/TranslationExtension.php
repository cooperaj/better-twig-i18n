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

namespace Acpr\I18n;

use Acpr\I18n\NodeVisitor\TranslationNodeVisitor;
use Acpr\I18n\TokenParser\TransTokenParser;
use Symfony\Bridge\Twig\NodeVisitor\TranslationDefaultDomainNodeVisitor;
use Symfony\Bridge\Twig\TokenParser\TransDefaultDomainTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TwigFilter;

/**
 * Provides integration of the Translation component with Twig.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Adam Cooper <adam@acpr.dev>
 */
class TranslationExtension extends AbstractExtension
{
    private TranslatorInterface $translator;
    private ?NodeVisitorInterface $translationNodeVisitor;

    public function __construct(
        TranslatorInterface $translator,
        NodeVisitorInterface $translationNodeVisitor = null
    ) {
        $this->translator = $translator;
        $this->translationNodeVisitor = $translationNodeVisitor;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', [$this, 'trans']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers(): array
    {
        return [
            // {% trans %}Symfony is great!{% endtrans %}
            new TransTokenParser(),

            // {% trans_default_domain "foobar" %}
            new TransDefaultDomainTokenParser(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeVisitors(): array
    {
        return [$this->getTranslationNodeVisitor(), new TranslationDefaultDomainNodeVisitor()];
    }

    public function getTranslationNodeVisitor(): NodeVisitorInterface
    {
        return $this->translationNodeVisitor ?: $this->translationNodeVisitor = new TranslationNodeVisitor();
    }

    public function trans(
        string $message,
        ?string $context = null,
        array $replacements = [],
        ?string $domain = null,
        ?int $count = null
    ): string {
        if (null !== $count) {
            $arguments['%count%'] = $count;

            $pluralParts = $this->splitPluralisation($message);
            $message = $pluralParts[0];
            $plural = $pluralParts[1];
        }

        return $this->getTranslator()->translate(
            $message,
            $replacements,
            $domain,
            $context,
            $plural ?? null,
            $count
        );
    }

    /**
     * The twig translation extension expects a delimited key/id for it's rules.
     * @see Symfony\Contracts\Translation\TranslatorInterface for more information on the format.
     *
     * Copied from Symfony\Component\Translation\Dumper\PoFileDumper
     * @copyright Fabien Potencier <fabien@symfony.com>
     *
     * @param string $id
     * @return array
     */
    private function splitPluralisation(string $id)
    {
        // Partly copied from TranslatorTrait::trans.
        $parts = [];
        if (preg_match('/^\|++$/', $id)) {
            $parts = explode('|', $id);
        } elseif (preg_match_all('/(?:\|\||[^\|])++/', $id, $matches)) {
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

        return $standardRules;
    }
}
