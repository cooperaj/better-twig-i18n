<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Gettext\GettextTranslator;
use Twig\Test\IntegrationTestCase;

/**
 * @covers \Acpr\I18n\TranslationExtension
 * @covers \Acpr\I18n\Node\TransNode
 * @covers \Acpr\I18n\Translator
 * @covers \Acpr\I18n\NodeVisitor\Message
 * @covers \Acpr\I18n\TokenParser\TransTokenParser
 */
class TwigIntegrationTest extends IntegrationTestCase
{
    public function getExtensions()
    {
        return [
            new TranslationExtension(new Translator(new GettextTranslator()))
        ];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__ . '/fixtures/';
    }
}