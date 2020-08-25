<?php

declare(strict_types=1);

namespace Acpr\I18n;

use Gettext\Translations;

interface ExtractorInterface
{
    /**
     * Extract the translateable strings found in the file/directory specified as $resource and return an array
     * containing translations grouped by domain.
     *
     * ```
     * $return = [
     *     'messages' => Translations::class
     *     'errors' => Translations::class
     * ]
     * ```
     *
     * @param string $resource Pulls a list of twig file info objects from a supplied filename, iterable list of
     *                         filenames or directory name
     * @return Translations[]  A keyed array of {@link Translations::class} objects
     */
    public function extract(string $resource): array;
}