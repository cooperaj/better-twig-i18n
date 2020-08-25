<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Symfony\Contracts\Translation\TranslatorInterface;

interface ContextAwareTranslatorInterface extends TranslatorInterface
{
    /**
     * @param string $id
     * @param string $context
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function transWithContext(
        string $id,
        string $context = '',
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null): string;
}