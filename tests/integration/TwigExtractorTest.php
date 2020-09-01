<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Acpr\I18n\TwigExtractor;
use Gettext\GettextTranslator;
use Gettext\Translation;
use Gettext\Translations;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \Acpr\I18n\TwigExtractor
 * @uses \Acpr\I18n\Translator
 * @uses \Acpr\I18n\TranslationExtension
 * @uses \Acpr\I18n\Node\TransNode
 * @uses \Acpr\I18n\TokenParser\TransTokenParser
 * @uses \Acpr\I18n\NodeVisitor\TranslationNodeVisitor
 */
class TwigExtractorTest extends TestCase
{
    public function createTwigEnvironment(array $viewPaths): Environment
    {
        $loader = new FilesystemLoader($viewPaths);

        $gettextTranslator = new GettextTranslator('de_DE');
        $translator = new Translator($gettextTranslator);

        $environment = new Environment($loader);
        $environment->addExtension(new TranslationExtension($translator));

        return $environment;
    }

    /**
     * @test
     */
    public function extractsASingleTwigFile(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{% trans %}My Title{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        $this->assertArrayHasKey('messages', $catalogues);
        $this->assertInstanceOf(Translations::class, $catalogues['messages']);
        $this->assertEquals(1, $catalogues['messages']->count());

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
    }

    /**
     * @test
     */
    public function extractsADirectoryOfTwigFiles(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{% trans %}My Title{% endtrans %}</h1>',
                'about.html.twig' => '<h1>{% trans %}About{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->url());

        $this->assertArrayHasKey('messages', $catalogues);
        $this->assertInstanceOf(Translations::class, $catalogues['messages']);
        $this->assertEquals(2, $catalogues['messages']->count());

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());

        $this->assertArrayHasKey("\004About", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004About"];
        $this->assertEquals('About', $translation->getOriginal());
    }
}
