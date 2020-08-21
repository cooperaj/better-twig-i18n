#!/usr/bin/env php
<?php

declare(strict_types=1);

use Acpr\I18n\Dumper\PotFileDumper;
use Acpr\I18n\MessageContextualiser;
use Acpr\I18n\TranslationExtension;
use Acpr\I18n\ContextAwareTwigExtractor;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Twig\Environment;

require '../vendor/autoload.php';

$twig = new Environment(new Twig\Loader\FilesystemLoader('templates/'));
$twig->addExtension(new TranslationExtension(new Translator('en_GB')));

$contextualiser = new MessageContextualiser();

$extractor = new ChainExtractor();
$extractor->addExtractor('twig', new ContextAwareTwigExtractor($twig, $contextualiser));

$writer = new TranslationWriter();
$writer->addDumper('pot', new PotFileDumper($contextualiser));

$extractedCatalogue = new MessageCatalogue('en_GB');
$extractor->extract('templates/', $extractedCatalogue);

$writer->write(
    $extractedCatalogue,
    'pot',
    [
        'path' => __DIR__ . '/languages/'
    ]
);