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

use Gettext\Translation;
use Gettext\Translations;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Twig\Environment;
use Twig\Source;

/**
 * ExtendedTwigExtractor extracts translation messages from a twig template.
 *
 * Implements additional functionality beyond the standard extractor with the addition of message
 * context and comments/notes.
 */
class TwigExtractor implements ExtractorInterface
{
    protected const DEFAULT_DOMAIN = 'messages';
    private string $defaultDomain;

    protected Environment $twig;

    public function __construct(Environment $twig, string $defaultDomain = self::DEFAULT_DOMAIN)
    {
        $this->twig = $twig;
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * @inheritDoc
     */
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
     * @param string $template The contents of a template file
     * @param string $name The name of the template file
     * @param string $path The path to the template file
     * @return Translations[] An array of {@link Translations::class} keyed on translation domains
     * @throws \Twig\Error\SyntaxError
     */
    protected function extractTemplateDetails(
        string $template,
        string $name,
        string $path
    ): array {
        /** @var Translations[] $translations */
        $translations = [];

        $visitor = $this->twig->getExtension('Acpr\I18n\TranslationExtension')
            ->getTranslationNodeVisitor();
        $visitor->enable();

        $parser = $this->twig->parse($this->twig->tokenize(new Source($template, $name, $path)));

        foreach ($visitor->getMessages() as $message) {
            $key = trim($message[0]);

            $domain = $message[2] ?: $this->defaultDomain;

            $translations[$domain] = $catalogue = $translations[$domain] ?? Translations::create($domain);

            $translation = Translation::create($message[4], $key);

            if ($message[1] !== null) {
                $translation->setPlural($message[1]);
            }

            $translation->getReferences()->add(
                $parser->getSourceContext()->getPath() . '/' . $parser->getSourceContext()->getName(),
                $message[5]
            );

            if ($message[3] !== null) {
                $translation->getExtractedComments()->add($message[3]);
            }

            $catalogue->add($translation);
        }

        $visitor->disable();

        return $translations;
    }

    /**
     * Pulls a list of twig file info objects from a supplied filename, iterable list of filenames or directory name
     *
     * @param $resource
     * @return array|SplFileInfo[]|Finder
     */
    protected function extractFiles($resource)
    {
        if (is_file($resource)) {
            $files = $this->canBeExtracted($resource) ? [$this->toSplFileInfo($resource)] : [];
        } else {
            $files = $this->extractFromDirectory($resource);
        }

        return $files;
    }

    /**
     * Given a filepath will return an object containing information about that file.
     *
     * @param string $file
     * @return SplFileInfo
     */
    protected function toSplFileInfo(string $file): SplFileInfo
    {
        return new SplFileInfo($file);
    }

    /**
     * @param string $file
     * @return bool
     */
    protected function canBeExtracted(string $file): bool
    {
        return 'twig' === pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * @param $directory
     * @return Finder
     */
    protected function extractFromDirectory($directory)
    {
        $finder = new Finder();

        return $finder->files()->name('*.twig')->in($directory);
    }
}
