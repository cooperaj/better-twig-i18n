<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Gettext\GettextTranslator;
use Gettext\TranslatorInterface as GettextTranslatorInterface;

use function putenv;

class Translator implements TranslatorInterface
{
    public function __construct(private GettextTranslator $translator)
    {
    }

    public function setLocale(string $locale): void
    {
        // We want to *encourage* this work on a number of operating systems that apparently use
        // different values. The setLanguage call below already does LANGUAGE.
        putenv("LC_ALL=$locale"); // Needed on Alpine Linux as the php:7-fpm-alpine docker image.
        putenv("LC_LANG=$locale");
        putenv("LC_LANGUAGE=$locale");

        $this->translator->setLanguage($locale);
    }

    public function getTranslator(): GettextTranslatorInterface
    {
        return $this->translator;
    }

    public function translate(
        string $original,
        array $replacements = [],
        ?string $domain = null,
        ?string $context = null,
        ?string $plural = null,
        ?int $count = null,
    ): string {
        [$function, $arguments] = $this->parseTranslationFunction(
            domain: $domain,
            context: $context,
            original: $original,
            plural: $plural,
            count: $count,
        );

        /** @var string $translated */
        $translated = $this->translator->$function(...$arguments);

        if (null !== $count) {
            $replacements['%count%'] = $count;
        }

        return $this->replaceTokens($translated, $replacements);
    }

    private function parseTranslationFunction(
        ?string $domain,
        ?string $context,
        string $original,
        ?string $plural,
        ?int $count,
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
