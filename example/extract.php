#!/usr/bin/env php
<?php

declare(strict_types=1);

use Acpr\I18n\PhpExtractor;
use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Acpr\I18n\TwigExtractor;
use Gettext\Generator\PoGenerator;
use Gettext\GettextTranslator;
use Gettext\Translations;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require '../vendor/autoload.php';

// We wrap the GettextTranslator with one that provides a single 'translate' call.
$gettextTranslator = new GettextTranslator('en_GB');
$translator = new Translator($gettextTranslator);

// Load the twig environment and add our translation extension
$twig = new Environment(new FilesystemLoader('templates/'));
$twig->addExtension(new TranslationExtension($translator));

// Create our extractors
$twigExtractor = new TwigExtractor($twig);
$phpExtractor = new PhpExtractor();

$catalogues = $twigExtractor->extract('templates/');
$phpCatalogues = $phpExtractor->extract('./');

// Merge the two catalogues
array_walk(
    $phpCatalogues,
    function (Translations $translations, string $domain) use (&$catalogues) {
        if (in_array($domain, array_keys($catalogues))) {
            $catalogues[$domain] = $catalogues[$domain]->mergeWith($translations);
        } else {
            $catalogues[$domain] = $translations;
        }
        return true;
    }
);

// Extractor returns a different translations instance for each domain that it discovers.
// We need to write these out individually.
$generator = new PoGenerator();
foreach ($catalogues as $domain => $translations) {
    $translations->getHeaders()->setLanguage('en_GB'); // our template is in english
    $translations->getHeaders()->set('POT-Creation-Date', (new DateTime())->format('c'));

    $generator->generateFile($translations, sprintf('languages/%s.pot', $domain));
}