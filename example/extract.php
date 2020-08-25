#!/usr/bin/env php
<?php

declare(strict_types=1);

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Acpr\I18n\TwigExtractor;
use Gettext\Translations;
use Twig\Environment;

require '../vendor/autoload.php';

$gettextTranslator = new Gettext\GettextTranslator('en_GB');
$translator = new Translator($gettextTranslator);

$twig = new Environment(new Twig\Loader\FilesystemLoader('templates/'));
$twig->addExtension(new TranslationExtension($translator));

$extractor = new TwigExtractor($twig);

foreach($extractor->extract('templates/') as $domain => $translations) {
    $translations->toPoFile(sprintf('languages/%s.pot', $domain));
}