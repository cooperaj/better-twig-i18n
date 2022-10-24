<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Acpr\I18n\NodeVisitor\Message;
use Gettext\Translation;
use Gettext\Translations;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

abstract class AbstractFileExtractor implements ExtractorInterface
{
    protected const DEFAULT_DOMAIN = 'messages';

    public function __construct(protected string $defaultDomain = self::DEFAULT_DOMAIN)
    {
    }

    /**
     * Pulls a list of php file info objects from a supplied filename, iterable list of filenames or directory name
     *
     * @param string $resource
     * @return array<SplFileInfo>|Finder
     */
    protected function extractFiles(string $resource): array|Finder
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

    protected function canBeExtracted(string $file): bool
    {
        return $this->getExtension() === pathinfo($file, PATHINFO_EXTENSION);
    }

    protected function extractFromDirectory(string $directory): Finder
    {
        $finder = new Finder();

        return $finder->files()->name('*.' . $this->getExtension())->in($directory);
    }

    protected function messageToTranslation(Message $message): Translation
    {
        $key = trim($message->original);

        $translation = Translation::create($message->context, $key);

        if ($message->plural !== null) {
            $translation->setPlural($message->plural);
        }

        if ($message->notes !== null) {
            $translation->getExtractedComments()->add($message->notes);
        }

        return $translation;
    }

    public function extract(string $resource): array
    {
        $catalogues = [];

        foreach ($this->extractFiles($resource) as $file) {
            $translations = $this->extractFromFile(
                file_get_contents($file->getPathname()),
                $file->getFilename(),
                $file->getPath()
            );

            // Merge our newly discovered translations into the full catalogue set
            array_walk(
                $translations,
                function (Translations $translations, string $domain) use (&$catalogues) {
                    /** @psalm-var array<Translations> $catalogues */

                    if (in_array($domain, array_keys($catalogues))) {
                        $catalogues[$domain] = $catalogues[$domain]->mergeWith($translations);
                    } else {
                        $catalogues[$domain] = $translations;
                    }
                    return true;
                }
            );
        }

        /** @var array<Translations> $catalogues */
        return $catalogues;
    }

    /**
     * @return array<Translations>
     */
    abstract protected function extractFromFile(string $content, string $name, string $path): array;

    /**
     * @return string The files extension of the type of file the extractor can extract from e.g. 'twig' or 'php'
     */
    abstract public function getExtension(): string;
}
