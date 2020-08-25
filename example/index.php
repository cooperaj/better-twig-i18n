<?php

declare(strict_types=1);

use Acpr\I18n\ContextAwareTranslator;
use Acpr\I18n\Loader\MoFileLoader;
use Acpr\I18n\MessageContextualiser;
use Acpr\I18n\TranslationExtension;
use Symfony\Component\Translation\Translator;
use Twig\Environment;

require '../vendor/autoload.php';

$twig = new Environment(new Twig\Loader\FilesystemLoader('templates/'));
$twig->setCache(false);

$symfonyTranslator = new Translator('de_DE');
$symfonyTranslator->addLoader('mo', new MoFileLoader());
$symfonyTranslator->addResource('mo', 'languages/de_DE.mo', 'de_DE');
$translator = new ContextAwareTranslator($symfonyTranslator, new MessageContextualiser());

$translation = new TranslationExtension($translator);
$twig->addExtension($translation);

echo $twig->render('home.html.twig');