<?php

declare(strict_types=1);

namespace AcprUnit\I18n;

use Acpr\I18n\Translator;
use Gettext\GettextTranslator as GettextTranslator;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Acpr\I18n\Translator::class)]
class TranslatorTest extends TestCase
{
    #[Test]
    #[BackupGlobals(true)]
    public function canSetLocale(): void
    {
        putenv('LC_ALL');
        putenv('LC_LANG');
        putenv('LC_LANGUAGE');

        $gettextTranslator = $this->createStub(GettextTranslator::class);
        $gettextTranslator->method('setLanguage')->willReturnSelf();

        $sut = new Translator($gettextTranslator);

        $sut->setLocale('de_DE');

        $this->assertEquals('de_DE', getenv('LC_ALL'));
        $this->assertEquals('de_DE', getenv('LC_LANG'));
        $this->assertEquals('de_DE', getenv('LC_LANGUAGE'));
    }

    #[Test]
    public function returnsSuppliedTranslator(): void
    {
        $gettextTranslator = $this->createStub(GettextTranslator::class);

        $sut = new Translator($gettextTranslator);

        $translator = $sut->getTranslator();

        $this->assertEquals($gettextTranslator, $translator);
    }

    #[Test]
    #[DataProvider('translationExpectationProvider')]
    public function providesASingleTranslateFunctionThatMapsToGettext(
        array $arguments,
        string $functionName,
        array $expectedArguments
    ): void {
        $gettextTranslator = $this->createMock(GettextTranslator::class);
        $gettextTranslator
            ->expects($this->once())
            ->method($functionName)
            ->with(...$expectedArguments)
            ->willReturn('success');


        $sut = new Translator($gettextTranslator);

        $string = $sut->translate(...$arguments);

        $this->assertEquals('success', $string);
    }

    public static function translationExpectationProvider(): array
    {
        return [
            'gettext' => [
                [ 'string to translate' ],
                'getText',
                [ 'string to translate' ]
            ],
            'ngetext (plural)' => [
                [ 'string to translate', [], null, null, 'plural string to translate', 2 ],
                'ngetText',
                [ 'string to translate', 'plural string to translate', 2 ]
            ],
            'dngetext (plural with domain)' => [
                [ 'string to translate', [], 'domain', null, 'plural string to translate', 2 ],
                'dngetText',
                [ 'domain', 'string to translate', 'plural string to translate', 2 ]
            ],
            'dngetext (plural with context)' => [
                [ 'string to translate', [], null, 'context', 'plural string to translate', 2 ],
                'npgetText',
                [ 'context', 'string to translate', 'plural string to translate', 2 ]
            ],
            'pgetext (context)' => [
                [ 'string to translate', [], null, 'context' ],
                'pgetText',
                [ 'context', 'string to translate' ]
            ],
            'dgetext (domain)' => [
                [ 'string to translate', [], 'domain' ],
                'dgetText',
                [ 'domain', 'string to translate' ]
            ],
            'dpgetext (domain with context)' => [
                [ 'string to translate', [], 'domain', 'context' ],
                'dpgetText',
                [ 'domain', 'context', 'string to translate' ]
            ],
            'dnpgetext (domain with context and plural)' => [
                [ 'string to translate', [], 'domain', 'context', 'plural string to translate', 2 ],
                'dnpgetText',
                [ 'domain', 'context', 'string to translate', 'plural string to translate', 2 ]
            ]
        ];
    }

    #[Test]
    public function interpolatesTokens()
    {
        $gettextTranslator = $this->createStub(GettextTranslator::class);
        $gettextTranslator->method('gettext')->willReturn('string with %var%');

        $sut = new Translator($gettextTranslator);

        $string = $sut->translate('string to translate', ['%var%' => 'tokens']);

        $this->assertEquals('string with tokens', $string);
    }

    #[Test]
    public function createsMissingCountToken()
    {
        $gettextTranslator = $this->createStub(GettextTranslator::class);
        $gettextTranslator->method('ngettext')->willReturn('string with %count%');

        $sut = new Translator($gettextTranslator);

        $string = $sut->translate(
            'string to translate',
            [], // no count token supplied
            null,
            null,
            'string with %count',
            3
        );

        $this->assertEquals('string with 3', $string);
    }
}
