<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Gettext\GettextTranslator;
use Gettext\TranslatorInterface as GettextTranslatorInterface;

class Translator implements TranslatorInterface
{
    /** @var GettextTranslator|GettextTranslatorInterface */
    private GettextTranslator $translator;

    public function __construct(GettextTranslator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @inheritDoc
     */
    public function setLocale(string $locale): void
    {
        $this->getTranslator()->setLanguage($locale);
    }

    /**
     * @inheritDoc
     */
    public function getTranslator(): GettextTranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @inheritDoc
     */
    public function translate(
        string $original,
        array $replacements = [],
        ?string $domain = null,
        ?string $context = null,
        ?string $plural = null,
        ?int $count = null
    ): string {
        [$function, $arguments] = $this->parseTranslationFunction(
            $domain,
            $context,
            $original,
            $plural,
            $count
        );

        $translated = $this->getTranslator()->$function(...$arguments);

        return $this->replaceTokens($translated, $replacements);
    }

    private function parseTranslationFunction(
        ?string $domain,
        ?string $context,
        string $original,
        ?string $plural,
        ?int $count
    ): array {

        $functionName = sprintf(
            '%s%s%sgettext',
            $domain !== null ? 'd' : '',
            $count !== null ? 'n' : '',
            $context !== null ? 'p' : ''
        );

        $arguments = [$original];
        $arguments = array_merge($context !== null ? [$context] : [], $arguments);
        $arguments = array_merge($domain !== null ? [$domain] : [], $arguments);
        $arguments = array_merge($arguments, $count !== null ? [$plural, $count] : []);

        return [$functionName, $arguments];
    }

    private function replaceTokens(string $translated, array $replacements): string
    {
        return strtr($translated, $replacements);
    }
}
