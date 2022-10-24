<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use Acpr\I18n\TwigExtractor;
use Gettext\Translation;
use Gettext\Translations;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Acpr\I18n\TwigExtractor
 * @covers \Acpr\I18n\Translator
 * @covers \Acpr\I18n\TranslationExtension
 * @covers \Acpr\I18n\Node\TransNode
 * @covers \Acpr\I18n\TokenParser\TransTokenParser
 * @covers \Acpr\I18n\NodeVisitor\TranslationNodeVisitor
 * @covers \Acpr\I18n\NodeVisitor\Message
 */
class TwigExtractorTest extends TestCase
{
    use TwigEnvironmentTrait;

    /** @test */
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
        $this->assertCount(1, $catalogues['messages']);

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
    }

    /** @test */
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
        $this->assertCount(2, $catalogues['messages']);

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());

        $this->assertArrayHasKey("\004About", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004About"];
        $this->assertEquals('About', $translation->getOriginal());
    }

    /** @test */
    public function mergesTranslationsAcrossFiles(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{% trans %}My Title{% endtrans %}</h1>',
                'about.html.twig' => '<h1>{% trans %}My Title{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->url());

        $this->assertCount(1, $catalogues['messages']);

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
        $this->assertCount(2, $translation->getReferences()->toArray());
        $this->assertArrayHasKey('vfs://root/home.html.twig', $translation->getReferences()->toArray());
        $this->assertArrayHasKey('vfs://root/about.html.twig', $translation->getReferences()->toArray());
    }

    /** @test */
    public function handlesParametersWithoutError(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => "<h1>{% trans with {'variable': 'Title'} %}My %variable%{% endtrans %}</h1>"
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004My %variable%"];
        $this->assertEquals('My %variable%', $translation->getOriginal());
    }

    /** @test */
    public function correctlySetsTheDomainWhenSpecified(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => "<h1>{% trans from 'errors' %}An Error{% endtrans %}</h1>"
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004An Error", $catalogues['errors']->getTranslations());
        $translation = $catalogues['errors']->getTranslations()["\004An Error"];
        $this->assertEquals('An Error', $translation->getOriginal());
    }

    /** @test */
    public function handlesNotesInTranslationTags(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{% trans %}My Title{% notes %}A note{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertContains('A note', $translation->getExtractedComments()->toArray());
    }

    /** @test */
    public function handlesContextInTranslationTags(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{% trans %}My Title{% context %}Additional context{% endtrans %}</h1>',
                'page.html.twig' => '<h1>{% trans %}My Title{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->url());

        // Same translatable content, no context
        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
        $this->assertEquals('', $translation->getContext());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["Additional context\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
        $this->assertEquals('Additional context', $translation->getContext());
    }

    /** @test */
    public function handlesContextAndNotesInTranslationTags(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' =>
                    '<h1>{% trans %}My Title{% context %}Additional context{% notes %}A note{% endtrans %}</h1>',
                'page.html.twig' =>
                    '<h1>{% trans %}My Title{% notes %}A note{% context %}Additional context{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["Additional context\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
        $this->assertEquals('Additional context', $translation->getContext());
        $this->assertContains('A note', $translation->getExtractedComments()->toArray());
    }

    /**
     * @test
     */
    public function handlesPluralisation(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{% trans count 1 %}I have one apple|I have %count% apples{% endtrans %}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        $this->assertArrayHasKey('messages', $catalogues);
        $this->assertInstanceOf(Translations::class, $catalogues['messages']);
        $this->assertEquals(1, $catalogues['messages']->count());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004I have one apple"];
        $this->assertEquals('I have one apple', $translation->getOriginal());
        $this->assertEquals('I have %count% apples', $translation->getPlural());
    }

    /** @test */
    public function correctlyAttachesSourceReferences(): void
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

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $references = $translation->getReferences()->toArray();
        $this->assertArrayHasKey('vfs://root/home.html.twig', $references);
    }

    /** @test */
    public function handlesTranslationAsAFilter(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => '<h1>{{ "My Title" | trans }} - ' .
                    '{{ "%count% day" | trans([], null, null, "%count% days", 1) }}</h1>'
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $sut = new TwigExtractor($twig);

        $catalogues = $sut->extract($vfs->getChild('home.html.twig')->url());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());

        // Also handles plurals
        $translation = $catalogues['messages']->getTranslations()["\004%count% day"];
        $this->assertEquals('%count% days', $translation->getPlural());
    }
}
