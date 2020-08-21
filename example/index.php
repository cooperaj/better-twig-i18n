<?php

declare(strict_types=1);

use Acpr\I18n\ContextAwareTranslator;
use Acpr\I18n\TranslationExtension;
use Symfony\Component\Translation\Translator;
use Twig\Environment;

require '../vendor/autoload.php';

$twig = new Environment(new Twig\Loader\FilesystemLoader('templates/'));
$twig->setCache(false);

$translator = new ContextAwareTranslator(new Translator('en_GB'));

$translation = new TranslationExtension($translator);
$twig->addExtension($translation);

echo $twig->render('home.html.twig');