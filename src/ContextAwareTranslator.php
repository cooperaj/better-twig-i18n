<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\MetadataAwareInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Decorates a TranslatorInterface so that it is capable of handling translation keys with additional context.
 *
 * Gettext supports the idea of attaching context to a translation key such that two identical keys
 * are interpreted as different if one or more of them have attached context information.
 *
 * @package Acpr\I18n
 */
class ContextAwareTranslator implements ContextAwareTranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    private MessageContextualiser $contextualiser;

    /** @var TranslatorBagInterface|LocaleAwareInterface|TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator Translator instance to decorate
     */
    public function __construct(TranslatorInterface $translator, MessageContextualiser $contextualiser)
    {
        if (!$translator instanceof TranslatorBagInterface || !$translator instanceof LocaleAwareInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'The Translator "%s" must implement TranslatorInterface, TranslatorBagInterface and '
                    . 'LocaleAwareInterface.',
                    get_debug_type($translator)
                )
            );
        }

        $this->translator = $translator;
        $this->contextualiser = $contextualiser;
    }

    /**
     * @inheritDoc
     */
    public function setLocale(string $locale)
    {
        $this->translator->setLocale($locale);
    }

    /**
     * @inheritDoc
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * @inheritDoc
     */
    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * @inheritDoc
     */
    public function trans(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $this->transWithContext($id, '', $parameters, $domain, $locale);
    }

    /**
     * A context aware translator expects to have to de/contextulalise the $id of a string
     */
    public function transWithContext(
            string $id,
            string $context = '',
            array $parameters = [],
            ?string $domain = null,
            ?string $locale = null
        ): string {

        /** @var MetadataAwareInterface $metadata */
        $metadata = $this->getCatalogue()->getMetaData($id, $domain);

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    /**
     * Ensure we map any other calls to the decorated translator
     *
     * @param string $method The decorated function to call
     * @param array $arguments Expected arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->translator->{$method}(...$arguments);
    }
}