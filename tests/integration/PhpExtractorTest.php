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
    /**
     * @test
     */
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

    /**
     * @test
     */
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
}
