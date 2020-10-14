<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Acpr\I18n\NodeVisitor\PhpParserNodeVisitor;
use Gettext\Translation;
use Gettext\Translations;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

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

    /**
     * @param string $contents The contents of the PHP file
     * @param string $filename
     * @param string $path
     * @return array A translation catalague containing domain keyed translations
     * @throws Error Parsing of the PHP file has failed
     */
    private function extractPhpFile(string $contents, string $filename, string $path): array
    {
        /** @var Translations[] $translations */
        $translations = [];

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $visitor = new PhpParserNodeVisitor();

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($parser->parse($contents));

        foreach ($visitor->getMessages() as $message) {
            $key = trim($message[0]);

            $domain = $message[2] ?: $this->defaultDomain;

            $translations[$domain] = $catalogue = $translations[$domain] ?? Translations::create($domain);

            $translation = Translation::create($message[4], $key);

            if ($message[1] !== null) {
                $translation->setPlural($message[1]);
            }

            $translation->getReferences()->add(
                $path . '/' . $filename,
                $message[5]
            );

            if ($message[3] !== null) {
                $translation->getExtractedComments()->add($message[3]);
            }

            $catalogue->add($translation);
        }

        return $translations;
    }

    /**
     * @inheritDoc
     */
    public function getExtension(): string
    {
        return self::EXTENSION;
    }
}