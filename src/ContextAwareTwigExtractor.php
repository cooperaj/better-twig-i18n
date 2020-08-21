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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Node\ModuleNode;
use Twig\Source;

/**
 * ExtendedTwigExtractor extracts translation messages from a twig template.
 *
 * Implements additional functionality beyond the standard extractor with the addition of message
 * context and comments/notes.
 *
 * @author Michel Salib <michelsalib@hotmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Adam Cooper <adam@acpr.dev>
 */
class ContextAwareTwigExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    protected const DEFAULT_DOMAIN = 'messages';

    protected Environment $twig;
    protected MessageContextualiser $contextualiser;
    protected string $prefix = '';

    public function __construct(Environment $twig, MessageContextualiser $contextualiser)
    {
        $this->twig = $twig;
        $this->contextualiser = $contextualiser;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($resource, MessageCatalogue $catalogue): void
    {
        foreach ($this->extractFiles($resource) as $file) {
            try {
                $this->extractTemplateDetails(
                    file_get_contents($file->getPathname()), $catalogue, $file->getFilename(), $file->getPath()
                );
            } catch (Error $e) {
                // ignore errors, these should be fixed by using the linter
            }
        }
    }

    protected function extractTemplateDetails(string $template, MessageCatalogue $catalogue, string $name, string $path): void
    {
        $visitor = $this->twig->getExtension('Acpr\I18n\TranslationExtension')
            ->getTranslationNodeVisitor();
        $visitor->enable();

        $parser = $this->twig->parse($this->twig->tokenize(new Source($template, $name, $path)));

        foreach ($visitor->getMessages() as $message) {
            $key = trim($message[0]);
            $domain = $message[1] ?: self::DEFAULT_DOMAIN;

            // if the message has extra context we need to prepend that to the key so that the catalogue
            // considers it to be different.
            $ctxtKey = $key;
            if ($message[3] !== null) {
                $ctxtKey = $this->contextualiser->contextualiseKey($catalogue, $key, $domain);
            }
            $catalogue->set($ctxtKey, $this->prefix . $key, $domain);

            $metadata = $catalogue->getMetadata($ctxtKey, $domain) ?: [];

            $this->addMessageSource(
                $metadata,
                $parser->getSourceContext()->getPath(),
                $parser->getSourceContext()->getName(),
                $message[4]
            );

            if ($message[2] !== null) {
                $this->addComment($metadata, $message[2]);
            }

            if ($message[3] !== null) {
                $this->addContext($metadata, $message[3]);
            }

            $catalogue->setMetadata($ctxtKey, $metadata, $domain);
        }

        $visitor->disable();
    }

    protected function canBeExtracted(string $file): bool
    {
        return $this->isFile($file) && 'twig' === pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFromDirectory($directory)
    {
        $finder = new Finder();

        return $finder->files()->name('*.twig')->in($directory);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Populate the sources in the message metadata
     *
     * @param array $metadata
     * @param string $path
     * @param string $name
     * @param int $lineNo
     * @return void
     */
    protected function addMessageSource(array &$metadata, string $path, string $name, int $lineNo): void
    {
        if (!isset($metadata['sources'])) {
            $metadata['sources'] = [];
        }

        $metadata['sources'][] = sprintf('%s/%s:%d', $path, $name, $lineNo);
    }

    /**
     * Add any comments to the metadata
     *
     * @param array $metadata
     * @param string $comment
     */
    protected function addComment(array &$metadata, string $comment): void
    {
        $metadata['comments'] = $comment;
    }

    /**
     * Adds context information to the metadata
     *
     * @param array $metadata
     * @param string $context
     */
    protected function addContext(array &$metadata, string $context): void
    {
        $metadata['context'] = $context;
    }
}
