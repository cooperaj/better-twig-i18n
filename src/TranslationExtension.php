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
use Symfony\Contracts\Translation\TranslatorInterface;
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
        NodeVisitorInterface $translationNodeVisitor = null)
    {
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
        array $arguments = [],
        ?string $domain = null,
        ?string $locale = null,
        ?int $count = null
    ): string {
        if (null !== $count) {
            $arguments['%count%'] = $count;
        }

        return $this->getTranslator()->transWithContext($message, $context, $arguments, $domain, $locale);
    }
}
