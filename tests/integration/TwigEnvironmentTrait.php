<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Gettext\GettextTranslator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

trait TwigEnvironmentTrait
{
    protected function createTwigEnvironment(array $viewPaths): Environment
    {
        $loader = new FilesystemLoader($viewPaths);

        $gettextTranslator = new GettextTranslator('de_DE');
        $translator = new Translator($gettextTranslator);

        $environment = new Environment($loader);
        $environment->addExtension(new TranslationExtension($translator));

        return $environment;
    }
}