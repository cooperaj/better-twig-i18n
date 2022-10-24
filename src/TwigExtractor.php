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
use Gettext\Translation;
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
class TwigExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    protected const EXTENSION = 'twig';
    protected const DEFAULT_DOMAIN = 'messages';

    public function __construct(
        protected Environment $twig,
        private string $defaultDomain = self::DEFAULT_DOMAIN,
    ) {
    }

    public function extract(string $resource): array
    {
        /** @var Translations[] $catalogues */
        $catalogues = [];

        foreach ($this->extractFiles($resource) as $file) {
            $translations = $this->extractTemplateDetails(
                file_get_contents($file->getPathname()),
                $file->getFilename(),
                $file->getPath()
            );

            // Merge our newly discovered translations into the full catalogue set
            array_walk(
                $translations,
                function (Translations $translations, string $domain) use (&$catalogues) {
                    if (in_array($domain, array_keys($catalogues))) {
                        $catalogues[$domain] = $catalogues[$domain]->mergeWith($translations);
                    } else {
                        $catalogues[$domain] = $translations;
                    }
                    return true;
                }
            );
        }

        return $catalogues;
    }

    /**
     * Parses a twig template for translation extension usages and extracts the text.
     *
     * @param string $template     The contents of a template file
     * @param string $name         The name of the template file
     * @param string $path         The path to the template file
     * @return array<Translations> An array of {@link Translations::class} keyed on translation domains
     * @throws SyntaxError
     */
    protected function extractTemplateDetails(
        string $template,
        string $name,
        string $path,
    ): array {
        /** @var array<Translations> $translations */
        $translations = [];

        $extension = $this->twig->getExtension(TranslationExtension::class);
        $visitor = $extension->getNodeVisitors()[0];

        if ($visitor instanceof AbstractTranslationNodeVisitor) {
            $visitor->enable();
        }

        $parser = $this->twig->parse($this->twig->tokenize(new Source($template, $name, $path)));

        foreach ($visitor->getMessages() as $message) {
            $key = trim($message->original);

            $domain = $message->domain ?: $this->defaultDomain;

            $translations[$domain] = $catalogue = $translations[$domain] ?? Translations::create($domain);

            $translation = Translation::create($message->context, $key);

            if ($message->plural !== null) {
                $translation->setPlural($message->plural);
            }

            $translation->getReferences()->add(
                $parser->getSourceContext()?->getPath() . '/' . $parser->getSourceContext()?->getName(),
                $message->line
            );

            if ($message->notes !== null) {
                $translation->getExtractedComments()->add($message->notes);
            }

            $catalogue->add($translation);
        }

        if ($visitor instanceof AbstractTranslationNodeVisitor) {
            $visitor->disable();
        }

        return $translations;
    }

    public function getExtension(): string
    {
        return self::EXTENSION;
    }
}
