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

echo $twig->render('home.html.twig');
