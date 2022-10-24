<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Acpr\I18n\NodeVisitor\PhpParserNodeVisitor;
use Gettext\Translation;
use Gettext\Translations;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class PhpExtractor extends AbstractFileExtractor
{
    protected const EXTENSION = 'php';

    /**
     * @param string $content The contents of the PHP file
     * @param string $name    The name of the PHP file
     * @param string $path    The path to the PHP file
     *
     * @return array<Translations> A translation catalogue containing domain keyed translations
     * @throws Error               Parsing of the PHP file has failed
     */
    protected function extractFromFile(string $content, string $name, string $path): array
    {
        /** @var array<Translations> $translations */
        $translations = [];

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $visitor = new PhpParserNodeVisitor();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($parser->parse($content) ?? []);

        foreach ($visitor->getMessages() as $message) {
            $domain = $message->domain ?: $this->defaultDomain;

            $translations[$domain] = $catalogue = $translations[$domain] ?? Translations::create($domain);

            $translation = $this->messageToTranslation($message);

            $translation->getReferences()->add(
                $path . '/' . $name,
                $message->line
            );

            $catalogue->add($translation);
        }

        return $translations;
    }

    public function getExtension(): string
    {
        return self::EXTENSION;
    }
}