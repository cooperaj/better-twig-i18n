<?php

declare(strict_types=1);

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Gettext\GettextTranslator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require '../vendor/autoload.php';

// Create our twig environment
$twig = new Environment(new FilesystemLoader('templates/'));

// Setup the translator
$gettextTranslator = new GettextTranslator('de');
$gettextTranslator->loadDomain('messages', 'languages');
$gettextTranslator->loadDomain('errors', 'languages', false);
$translator = new Translator($gettextTranslator);

// Make the translation extension and and it to twig
$translation = new TranslationExtension($translator);
$twig->addExtension($translation);

// Do translation in PHP of various strings
$phpTitle = $translator->translate('PHP Extracted');
$translatedVariable = $translator->translate(
    'A quick test of a "%variable%"',
    [
        '%variable%' => $translator->translate('translated variable')
    ]
);
$pluralApples = $translator->translate(
    'I have an apple',
    [
        '%count%' => 3
    ],
    null,
    null,
    'I have %count% apples',
    3
);
$withContext = $translator->translate('A test page', [], null, 'Within a list');
$errorDomain = $translator->translate('This is an error', [], 'errors');
$languageByContext = $translator->translate('This should be in english', [], null, 'Leave as english');
$missingVariable = $translator->translate('A quick test of a "%missing%" variable', []);

echo $twig->render(
    'home.html.twig',
    [
        'php_title' => $phpTitle,
        'translated_variable' => $translatedVariable,
        'plural_apples' => $pluralApples,
        'with_context' => $withContext,
        'error_domain' => $errorDomain,
        'language_by_context' => $languageByContext,
        'missing_variable' => $missingVariable
    ]
);
