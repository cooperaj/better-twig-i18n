<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use Acpr\I18n\TranslationExtension;
use Acpr\I18n\Translator;
use Gettext\GettextTranslator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Test\IntegrationTestCase;

#[CoversClass(\Acpr\I18n\TranslationExtension::class)]
#[CoversClass(\Acpr\I18n\Node\TransNode::class)]
#[CoversClass(\Acpr\I18n\Translator::class)]
#[CoversClass(\Acpr\I18n\NodeVisitor\Message::class)]
#[CoversClass(\Acpr\I18n\TokenParser\TransTokenParser::class)]
class TwigIntegrationTest extends TestCase
{
    public static IntegrationTestCase $integrationTestCase;

    public static function buildTestCase(): IntegrationTestCase
    {
        if (isset(self::$integrationTestCase)) {
            return self::$integrationTestCase;
        }

        self::$integrationTestCase = new class () extends IntegrationTestCase {
            public function __construct()
            {
            }

            public function getExtensions(): array
            {
                return [
                    new TranslationExtension(new Translator(new GettextTranslator()))
                ];
            }

            protected function getFixturesDir(): string
            {
                return __DIR__ . '/fixtures/';
            }
        };

        return self::$integrationTestCase;
    }


    #[Test]
    #[DataProvider('getIntegrationTests')]
    public function integration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = ''): void
    {
        self::buildTestCase()->testIntegration(
            $file,
            $message,
            $condition,
            $templates,
            $exception,
            $outputs,
            $deprecation,
        );
    }

    #[Test]
    #[DataProvider('getLegacyIntegrationTests')]
    public function legacyIntegration(
        $file,
        $message,
        $condition,
        $templates,
        $exception,
        $outputs,
        $deprecation = ''
    ): void {
        self::buildTestCase()->testLegacyIntegration(
            $file,
            $message,
            $condition,
            $templates,
            $exception,
            $outputs,
            $deprecation,
        );
    }

    public static function getIntegrationTests(): array
    {
        $tests = self::buildTestCase()->getTests('');

        return array_combine(array_map(fn($testParams) => $testParams[1], $tests), $tests);
    }

    public static function getLegacyIntegrationTests(): array
    {
        $tests = self::buildTestCase()->getTests('', true);

        return array_combine(array_map(fn($testParams) => $testParams[1], $tests), $tests);
    }
}