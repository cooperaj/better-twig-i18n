<?php

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

class Message
{
    /**
     * @param string      $original The original text, or key to use for the translation
     * @param int         $line The line number of the file this message was found on
     * @param string|null $plural A plural form of the original text (optional)
     * @param string|null $domain A domain to add the extracted text to (optional)
     * @param string|null $notes A translators note to be provided with the original text (optional)
     * @param string|null $context A message context that applies to the original text (optional)
     */
    public function __construct(
        public string $original,
        public int $line,
        public ?string $plural = null,
        public ?string $domain = null,
        public ?string $notes = null,
        public ?string $context = null,
    ) {
    }
}
