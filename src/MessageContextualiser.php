<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\MetadataAwareInterface;

class MessageContextualiser
{
    protected const CONTEXT_PREFIX = 'HAS_CONTEXT';

    /**
     * Generates a unique key that can be used by a message that has extra context metadata.
     *
     * @param MessageCatalogueInterface $catalogue
     * @param string $key
     * @param string $domain
     * @return string The key with a prefix identifying it as having context metadata
     */
    public function contextualiseKey(MessageCatalogueInterface $catalogue, string $key, string $domain): string
    {
        return $this->generateContextAwareKeyPrefix($catalogue, $key, $domain) . $key;
    }

    public function decontextualiseKey(MessageCatalogueInterface $catalogue, string $key, string $domain): string
    {
        if (!$catalogue instanceof MetadataAwareInterface) {
            throw new InvalidArgumentException(
                'The passed in message catalogue must implement MessageCatalogueInterface and '
                . 'MetadataAwareInterface.'
            );
        }

        // only remove a prefix if it has one and there is attached metadata.
        if (!str_starts_with($key, self::CONTEXT_PREFIX)
            || !isset($catalogue->getMetadata($key, $domain)['context'])) {
            return $key;
        }

        return preg_replace('/' . self::CONTEXT_PREFIX . '[\d]+::/', '', $key);
    }

    /**
     * Iterates over the MessageCatalogue attempting to find a unique prefix/key combination that will
     * allow translation context support.
     *
     * @param MessageCatalogueInterface $catalogue
     * @param string $key
     * @param string $domain
     * @return string
     */
    protected function generateContextAwareKeyPrefix(
        MessageCatalogueInterface $catalogue,
        string $key,
        string $domain
    ): string {
        $prefix = null;
        $count = 0;

        do {
            $prefix = sprintf('%s%d::', self::CONTEXT_PREFIX, $count++);
            $existingKey = $catalogue->has($prefix . $key, $domain);

            $prefix = $existingKey ? null : $prefix;
        } while ($prefix === null);

        return $prefix;
    }
}