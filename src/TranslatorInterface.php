<?php

declare(strict_types=1);

namespace Acpr\I18n;

interface TranslatorInterface
{
    /**
     * @return \Gettext\TranslatorInterface The underlying Gettext translation instance.
     */
    public function getTranslator(): \Gettext\TranslatorInterface;

    /**
     * @param string $original    The original message to be translated.
     * @param array $replacements An array of replacement values to be subbed in at token locations.
     * @param string|null $domain A message domain to load the translations from.
     * @param string|null $context Additional context for string which are the same in the original language but may
     *                             translate differently into other languages.
     * @param string|null $plural A plural expression of the original message.
     * @param int|null $count     The count to apply to the pluralisation rules.
     * @return string             The translated string with tokens replaced.
     */
    public function translate(
        string $original,
        array $replacements = [],
        ?string $domain = null,
        ?string $context = null,
        ?string $plural = null,
        ?int $count = null
    ): string;
}
