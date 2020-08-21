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

namespace Acpr\I18n\Dumper;

use Acpr\I18n\MessageContextualiser;
use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * PotFileDumper allows the writing of POT files from MessageCatalogues. These differ from PO files in that
 * they do not contain msgstr values.
 *
 * Additionally this class has been extended to allow the writing of msgctxt values
 */
class PotFileDumper extends FileDumper
{
    private MessageContextualiser $contextualiser;

    public function __construct(MessageContextualiser $contextualiser)
    {
        $this->contextualiser = $contextualiser;
    }

    /**
     * {@inheritdoc}
     */
    public function formatCatalogue(MessageCatalogue $messages, string $domain, array $options = [])
    {
        $output = 'msgid ""'."\n";
        $output .= 'msgstr ""'."\n";
        $output .= '"Content-Type: text/plain; charset=UTF-8\n"'."\n";
        $output .= '"Content-Transfer-Encoding: 8bit\n"'."\n";
        $output .= '"Language: '.$messages->getLocale().'\n"'."\n";
        $output .= "\n";

        $newLine = false;
        foreach ($messages->all($domain) as $source => $target) {
            if ($newLine) {
                $output .= "\n";
            } else {
                $newLine = true;
            }
            $metadata = $messages->getMetadata($source, $domain);

            if (isset($metadata['comments'])) {
                $output .= $this->formatComments($metadata['comments'], '. TRANSLATORS:');
            }
            if (isset($metadata['flags'])) {
                $output .= $this->formatComments(implode(',', (array) $metadata['flags']), ',');
            }
            if (isset($metadata['sources'])) {
                $output .= $this->formatComments(implode(' ', (array) $metadata['sources']), ':');
            }

            if (isset($metadata['context'])) {
                $output .= sprintf('msgctxt "%s"' . "\n", $this->escape($metadata['context']));
                $source = $this->contextualiser->decontextualiseKey($messages, $source, $domain);
            }

            $sourceRules = $this->getStandardRules($source);
            $targetRules = $this->getStandardRules($target);
            if (2 == \count($sourceRules) && $targetRules !== []) {
                $output .= sprintf('msgid "%s"'."\n", $this->escape($sourceRules[0]));
                $output .= sprintf('msgid_plural "%s"'."\n", $this->escape($sourceRules[1]));
                foreach ($targetRules as $i => $targetRule) {
                    $output .= sprintf('msgstr[%d] ""'."\n", $i);
                }
            } else {
                $output .= sprintf('msgid "%s"'."\n", $this->escape($source));
                $output .= 'msgstr ""'."\n";
            }
        }

        return $output;
    }

    private function getStandardRules(string $id)
    {
        // Partly copied from TranslatorTrait::trans.
        $parts = [];
        if (preg_match('/^\|++$/', $id)) {
            $parts = explode('|', $id);
        } elseif (preg_match_all('/(?:\|\||[^\|])++/', $id, $matches)) {
            $parts = $matches[0];
        }

        $intervalRegexp = <<<'EOF'
/^(?P<interval>
    ({\s*
        (\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)
    \s*})

        |

    (?P<left_delimiter>[\[\]])
        \s*
        (?P<left>-Inf|\-?\d+(\.\d+)?)
        \s*,\s*
        (?P<right>\+?Inf|\-?\d+(\.\d+)?)
        \s*
    (?P<right_delimiter>[\[\]])
)\s*(?P<message>.*?)$/xs
EOF;

        $standardRules = [];
        foreach ($parts as $part) {
            $part = trim(str_replace('||', '|', $part));

            if (preg_match($intervalRegexp, $part)) {
                // Explicit rule is not a standard rule.
                return [];
            } else {
                $standardRules[] = $part;
            }
        }

        return $standardRules;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtension()
    {
        return 'pot';
    }

    private function escape(string $str): string
    {
        return addcslashes($str, "\0..\37\42\134");
    }

    private function formatComments($comments, string $prefix = ''): ?string
    {
        $output = null;

        foreach ((array) $comments as $comment) {
            $output .= sprintf('#%s %s'."\n", $prefix, $comment);
        }

        return $output;
    }
}
