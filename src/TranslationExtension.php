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
use Twig\Extension\AbstractExtension;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TwigFilter;

/**
 * Provides integration of the Translation component with Twig.
 *
 * @api
 */
class TranslationExtension extends AbstractExtension
{
    private NodeVisitorInterface $nodeVisitor;

    public function __construct(
        private TranslatorInterface $translator,
        ?NodeVisitorInterface $translationNodeVisitor = null,
    ) {
        $this->nodeVisitor = $translationNodeVisitor ?? new TranslationNodeVisitor();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans', $this->trans(...)),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            // {% trans %}Symfony is great!{% endtrans %}
            new TransTokenParser()
        ];
    }

    public function getNodeVisitors(): array
    {
        return [$this->nodeVisitor];
    }

    public function trans(
        string $message,
        array $replacements = [],
        ?string $domain = null,
        ?string $context = null,
        ?string $plural = null,
        ?int $count = null,
    ): string {
        return $this->getTranslator()->translate(
            original: $message,
            replacements: $replacements,
            domain: $domain,
            context: $context,
            plural: $plural,
            count: $count,
        );
    }
}
