<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Gettext\Translations;

class PhpExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    protected const EXTENSION = 'php';

    protected const DEFAULT_DOMAIN = 'messages';
    private string $defaultDomain;


    public function __construct(string $defaultDomain = self::DEFAULT_DOMAIN)
    {
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
            $translations = $this->extractPhpFile(
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

    private function extractPhpFile(string $file, string $getFilename, string $getPath): array
    {
        $catalogue = [];

        return $catalogue;
    }

    /**
     * @inheritDoc
     */
    public function getExtension(): string
    {
        return self::EXTENSION;
    }
}