<?php

declare(strict_types=1);

namespace AcprIntegration\I18n;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Acpr\I18n\TranslationExtension
 * @covers \Acpr\I18n\Node\TransNode
 * @covers \Acpr\I18n\Translator
 * @covers \Acpr\I18n\NodeVisitor\Message
 * @covers \Acpr\I18n\TokenParser\TransTokenParser
 */
class TwigExtensionTest extends TestCase
{
    use TwigEnvironmentTrait;

    /** @test */
    public function returnsExpectedTranslatedTemplate(): void
    {
        $vfs = vfsStream::setup(
            'root',
            null,
            [
                'home.html.twig' => <<<TEMPLATE
<head>
    <title>{% trans %}A test page{% endtrans %}</title>
</head>
<body>
    <h1>{% trans %}A test page{% context %}Page title{% endtrans %}</h1>

    <h2>{% trans %}Twig Extracted{% endtrans %}</h2>
    <dl>
        {% set var = 'translated variable' | trans -%}
        <dt>{% trans %}Test of a translated variable{% endtrans %}</dt>
        <dd>{% trans with {'%variable%': var} %}A quick test of a "%variable%"{% endtrans %}</dd>

        <dt>{% trans %}Test of a plural statement{% endtrans %}</dt>
        <dd>{% trans count 3 %}I have an apple|I have %count% apples{% notes %}A simple plural count of apples{% endtrans %}</dd>

        <dt>{% trans %}Test of a second context{% endtrans %}</dt>
        <dd>{% trans %}A test page{% context %}Within a list{% endtrans %}</dd>

        <dt>{% trans %}Test of a manual domain{% endtrans %}</dt>
        <dd>{% trans from 'errors' %}This is an error{% endtrans %}</dd>

        <dt>{% trans %}Test of a language choice (en_GB){% endtrans %}</dt>
        <dd>{% trans %}This should be in english{% context %}Leave as english{% endtrans %}</dd>

        <dt>{% trans %}Test of a missing placeholder{% endtrans %}</dt>
        <dd>{% trans %}A quick test of a "%missing%" variable{% endtrans %}</dd>
    </dl>
</body>
TEMPLATE,
                'complexfilter.html.twig' =>
                    '<h2>in {{ "%count% day" | trans([], null, null, "%count% days", 1) }}</h2>',
            ]
        );

        $twig = $this->createTwigEnvironment([$vfs->url()]);

        $rendered = $twig->render('home.html.twig');
        $expected = <<<EXPECTED
<head>
    <title>A test page</title>
</head>
<body>
    <h1>A test page</h1>

    <h2>Twig Extracted</h2>
    <dl>
        <dt>Test of a translated variable</dt>
        <dd>A quick test of a "translated variable"</dd>

        <dt>Test of a plural statement</dt>
        <dd>I have 3 apples</dd>

        <dt>Test of a second context</dt>
        <dd>A test page</dd>

        <dt>Test of a manual domain</dt>
        <dd>This is an error</dd>

        <dt>Test of a language choice (en_GB)</dt>
        <dd>This should be in english</dd>

        <dt>Test of a missing placeholder</dt>
        <dd>A quick test of a "" variable</dd>
    </dl>
</body>
EXPECTED;
        $this->assertEquals($expected, $rendered);


        $renderedFilter = $twig->render('complexfilter.html.twig');
        $this->assertEquals('<h2>in 1 day</h2>', $renderedFilter);
    }
}
