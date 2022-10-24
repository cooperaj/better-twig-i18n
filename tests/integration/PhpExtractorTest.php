<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use Acpr\I18n\PhpExtractor;
use Gettext\Translation;
use Gettext\Translations;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Acpr\I18n\PhpExtractor
 * @covers \Acpr\I18n\AbstractFileExtractor
 * @covers \Acpr\I18n\NodeVisitor\PhpParserNodeVisitor
 */
class PhpExtractorTest extends TestCase
{
    /** @test */
    public function extractsASinglePhpFile(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title');"
            ]
        );

        $sut = new PhpExtractor();

        $catalogues = $sut->extract($vfs->getChild('index.php')->url());

        $this->assertArrayHasKey('messages', $catalogues);
        $this->assertInstanceOf(Translations::class, $catalogues['messages']);
        $this->assertCount(1, $catalogues['messages']);

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
    }

    /** @test */
    public function extractsADirectoryOfPhpFiles(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title');",
                'about.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('About');"
            ]
        );

        $sut = new PhpExtractor();

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
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title');",
                'about.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title');"
            ]
        );

        $sut = new PhpExtractor();

        $catalogues = $sut->extract($vfs->url());

        $this->assertCount(1, $catalogues['messages']);

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004My Title", $catalogues['messages']->getTranslations());
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $this->assertEquals('My Title', $translation->getOriginal());
        $this->assertCount(2, $translation->getReferences()->toArray());
        $this->assertArrayHasKey('vfs://root/index.php', $translation->getReferences()->toArray());
        $this->assertArrayHasKey('vfs://root/about.php', $translation->getReferences()->toArray());
    }

    /** @test */
    public function handlesParametersWithoutError(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My %variable%', ['%variable%' => 'Title']);",
            ]
        );

        $sut = new PhpExtractor();

        $catalogues = $sut->extract($vfs->getChild('index.php')->url());

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
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('An Error', [], 'errors');",
            ]
        );

        $sut = new PhpExtractor();

        $catalogues = $sut->extract($vfs->getChild('index.php')->url());

        /** @var Translation $translation */
        $this->assertArrayHasKey("\004An Error", $catalogues['errors']->getTranslations());
        $translation = $catalogues['errors']->getTranslations()["\004An Error"];
        $this->assertEquals('An Error', $translation->getOriginal());
    }

    /** @test */
    public function handlesContextInTranslationTags(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title');",
                'about.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title', [], null, 'Additional context');"
            ]
        );

        $sut = new PhpExtractor();

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
    public function handlesPluralisation(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = " .
                        "\$translator->translate('I have one apple', [], null, null, 'I have %count% apples', 1);",
            ]
        );

        $sut = new PhpExtractor();

        $catalogues = $sut->extract($vfs->getChild('index.php')->url());

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
                'index.php' => "<?php " .
                    "\$gettextTranslator = new GettextTranslator('de');" .
                    "\$translator = new Translator(\$gettextTranslator);" .
                    "\$phpTitle = \$translator->translate('My Title');",
            ]
        );

        $sut = new PhpExtractor();

        $catalogues = $sut->extract($vfs->getChild('index.php')->url());

        /** @var Translation $translation */
        $translation = $catalogues['messages']->getTranslations()["\004My Title"];
        $references = $translation->getReferences()->toArray();
        $this->assertArrayHasKey('vfs://root/index.php', $references);
    }
}
