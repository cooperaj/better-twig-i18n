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

use Acpr\I18n\NodeVisitor\AbstractTranslationNodeVisitor;
use Gettext\Translations;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Source;

/**
 * ExtendedTwigExtractor extracts translation messages from a twig template.
 *
 * Implements additional functionality beyond the standard extractor with the addition of message
 * context and comments/notes.
 */
class TwigExtractor extends AbstractFileExtractor
{
    protected const EXTENSION = 'twig';

    public function __construct(
        protected Environment $twig,
        string $defaultDomain = self::DEFAULT_DOMAIN,
    ) {
        parent::__construct($defaultDomain);
    }

    /**
     * Parses a twig template for translation extension usages and extracts the text.
     *
     * @param string $content The contents of a template file
     * @param string $name    The name of the template file
     * @param string $path    The path to the template file
     *
     * @return array<Translations> An array of {@link Translations::class} keyed on translation domains
     * @throws SyntaxError
     */
    protected function extractFromFile(string $content, string $name, string $path): array
    {
        /** @var array<Translations> $translations */
        $translations = [];

        $extension = $this->twig->getExtension(TranslationExtension::class);
        $visitor = $extension->getNodeVisitors()[0];

        if ($visitor instanceof AbstractTranslationNodeVisitor) {
            $visitor->enable();

            $parser = $this->twig->parse($this->twig->tokenize(new Source($content, $name, $path)));

            foreach ($visitor->getMessages() as $message) {
                $domain = $message->domain ?: $this->defaultDomain;

                $translations[$domain] = $catalogue = $translations[$domain] ?? Translations::create($domain);

                $translation = $this->messageToTranslation($message);

                $translation->getReferences()->add(
                    sprintf(
                        '%s/%s',
                        $parser->getSourceContext()?->getPath() ?? '',
                        $parser->getSourceContext()?->getName() ?? ''
                    ),
                    $message->line
                );

                $catalogue->add($translation);
            }

            $visitor->disable();
        }

        return $translations;
    }

    public function getExtension(): string
    {
        return self::EXTENSION;
    }
}
