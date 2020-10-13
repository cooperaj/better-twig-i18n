<?php

declare(strict_types=1);

namespace Acpr\I18n;

use ArrayIterator;
use Gettext\Translations;
use Iterator;

class PhpExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    const MESSAGE_TOKEN = 1;
    const MESSAGE_REPLACEMENTS_TOKEN = 2;
    const DOMAIN_TOKEN = 3;
    const CONTEXT_TOKEN = 4;
    const PLURAL_TOKEN = 5;
    const COUNT_TOKEN = 6;

    protected const EXTENSION = 'php';

    protected const DEFAULT_DOMAIN = 'messages';
    private string $defaultDomain;

    /**
     * The sequence that captures translation messages.
     */
    protected $signature = [
        '->',
        'translate',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::MESSAGE_REPLACEMENTS_TOKEN,
        ',',
        self::DOMAIN_TOKEN,
        ',',
        self::CONTEXT_TOKEN,
        ',',
        self::PLURAL_TOKEN,
        ',',
        self::COUNT_TOKEN,
    ];

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

    private function extractPhpFile(string $file, string $getFilename, string $getPath): array
    {
        $catalogue = [];

        $tokenIterator = new ArrayIterator(token_get_all($file));

        for ($key = 0; $key < $tokenIterator->count(); ++$key) {
            $message = '';
            $tokenIterator->seek($key);

            foreach ($this->signature as $item) {
                $this->seekToNextRelevantToken($tokenIterator);

                switch ($item) {
                    case $this->normalizeToken($tokenIterator->current()):
                        $tokenIterator->next();
                        continue 2;
                    case self::MESSAGE_TOKEN:
                        $message = $this->getValue($tokenIterator);
                        break;
                    case self::MESSAGE_REPLACEMENTS_TOKEN:
                        $this->skipMethodArgument($tokenIterator);
                        break;
                    case self::DOMAIN_TOKEN:
                        $domainToken = $this->getValue($tokenIterator);
                        if ('' !== $domainToken) {
                            $domain = $domainToken;
                        }

                        break;
                    case self::CONTEXT_TOKEN:
                        $contextToken = $this->getValue($tokenIterator);
                        if ('' !== $contextToken) {
                            $context = $contextToken;
                        }

                        break;
                    case self::PLURAL_TOKEN:
                        $pluralToken = $this->getValue($tokenIterator);
                        if ('' !== $pluralToken) {
                            $plural = $pluralToken;
                        }

                        break;
                    case self::COUNT_TOKEN:
                        $countToken = $this->getValue($tokenIterator);
                        if ('' !== $countToken) {
                            $count = $countToken;
                        }

                        break;
                    default:
                        break 2;
                }
            }

            //                if ($message) {
            //                    $catalog->set($message, $this->prefix.$message, $domain);
            //                    $metadata = $catalog->getMetadata($message, $domain) ?? [];
            //                    $normalizedFilename = preg_replace('{[\\\\/]+}', '/', $filename);
            //                    $metadata['sources'][] = $normalizedFilename.':'.$tokens[$key][2];
            //                    $catalog->setMetadata($message, $metadata, $domain);
            //                    break;
            //                }
        }

        return $catalogue;
    }

    protected function normalizeToken($token): ?string
    {
        if (isset($token[1]) && 'b"' !== $token) {
            return $token[1];
        }

        return $token;
    }

    /**
     * Seeks to a non-whitespace token.
     */
    private function seekToNextRelevantToken(Iterator $tokenIterator): void
    {
        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if (\T_WHITESPACE !== $t[0]) {
                break;
            }
        }
    }

    private function skipMethodArgument(Iterator $tokenIterator)
    {
        $openBraces = 0;

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();

            if ('[' === $t[0] || '(' === $t[0]) {
                ++$openBraces;
            }

            if (']' === $t[0] || ')' === $t[0]) {
                --$openBraces;
            }

            if ((0 === $openBraces && ',' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
                break;
            }
        }
    }

    /**
     * Extracts the message from the iterator while the tokens
     * match allowed message tokens.
     */
    private function getValue(Iterator $tokenIterator)
    {
        $message = '';
        $docToken = '';
        $docPart = '';

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if ('.' === $t) {
                // Concatenate with next token
                continue;
            }
            if (!isset($t[1])) {
                break;
            }

            switch ($t[0]) {
                case \T_START_HEREDOC:
                    $docToken = $t[1];
                    break;
                case \T_ENCAPSED_AND_WHITESPACE:
                case \T_CONSTANT_ENCAPSED_STRING:
                    if ('' === $docToken) {
                        //$message .= PhpStringTokenParser::parse($t[1]);
                    } else {
                        $docPart = $t[1];
                    }
                    break;
                case \T_END_HEREDOC:
                    //$message .= PhpStringTokenParser::parseDocString($docToken, $docPart);
                    $docToken = '';
                    $docPart = '';
                    break;
                case \T_WHITESPACE:
                    break;
                default:
                    break 2;
            }
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getExtension(): string
    {
        return self::EXTENSION;
    }
}