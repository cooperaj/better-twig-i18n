<?php

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

use Twig\NodeVisitor\NodeVisitorInterface;

abstract class AbstractTranslationNodeVisitor implements NodeVisitorInterface
{
    /**
     * Enable the NodeVisitor
     *
     * NodeVisitors are expensive to run and its only necessary to run a translation NodeVisitor when
     * text is to be extracted so provide a way to enable/disable the operation of the extractor.
     */
    abstract public function enable(): void;

    /**
     * Disable the NodeVisitor
     *
     * Ensure that you disable a translation providing NodeVisitor after you've extracted so that any further
     * Twig parse operations do not invoke your extraction code.
     */
    abstract public function disable(): void;

    /**
     * Fetch any translatable messages that were picked up during the Twig parse operation that this
     * NodeVisitor enabled for.
     *
     * @return Message[]
     */
    abstract public function getMessages(): array;
}
