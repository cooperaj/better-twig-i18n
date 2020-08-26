<?php

declare(strict_types=1);

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Twig\Environment;

require '../vendor/autoload.php';

$twig = new Environment(new Twig\Loader\FilesystemLoader('templates/'));
$twig->setCache(false);

$gettextTranslator = new Gettext\GettextTranslator('de');
$gettextTranslator->loadDomain('messages', 'languages');
$gettextTranslator->loadDomain('errors', 'languages', false);

$translator = new Translator($gettextTranslator);

$translation = new TranslationExtension($translator);
$twig->addExtension($translation);

echo $twig->render('home.html.twig');
