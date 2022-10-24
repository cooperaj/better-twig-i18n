<?php

declare(strict_types=1);

namespace Acpr\I18n;

use SplFileInfo;
use Symfony\Component\Finder\Finder;

abstract class AbstractFileExtractor
{
    /**
     * Pulls a list of php file info objects from a supplied filename, iterable list of filenames or directory name
     *
     * @param string $resource
     * @return array<SplFileInfo>|Finder
     */
    protected function extractFiles(string $resource): array|Finder
    {
        if (is_file($resource)) {
            $files = $this->canBeExtracted($resource) ? [$this->toSplFileInfo($resource)] : [];
        } else {
            $files = $this->extractFromDirectory($resource);
        }

        return $files;
    }

    /**
     * Given a filepath will return an object containing information about that file.
     *
     * @param string $file
     * @return SplFileInfo
     */
    protected function toSplFileInfo(string $file): SplFileInfo
    {
        return new SplFileInfo($file);
    }

    protected function canBeExtracted(string $file): bool
    {
        return $this->getExtension() === pathinfo($file, PATHINFO_EXTENSION);
    }

    protected function extractFromDirectory(string $directory): Finder
    {
        $finder = new Finder();

        return $finder->files()->name('*.' . $this->getExtension())->in($directory);
    }

    /**
     * @return string The files extension of the type of file the extractor can extract from e.g. 'twig' or 'php'
     */
    abstract public function getExtension(): string;
}
