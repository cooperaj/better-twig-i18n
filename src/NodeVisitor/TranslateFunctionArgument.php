<?php

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

enum TranslateFunctionArgument : int
{
    case Message = 0;
    case Domain = 2;
    case Context = 3;
    case Plural = 4;
}
