Better Twig Gettext i18n
======

![Build](https://github.com/cooperaj/better-twig-i18n/workflows/Build/badge.svg)
[![codecov](https://codecov.io/gh/cooperaj/better-twig-i18n/branch/master/graph/badge.svg)](https://codecov.io/gh/cooperaj/better-twig-i18n)



Allows the use of a POT/PO/MO (gettext) set of language definitions with Twig, using an identical syntax to the current
official [Twig translation extension](https://github.com/symfony/twig-bridge).

It supports full extraction of Twig templates into language catalogues from the 
[gettext/gettext](https://github.com/php-gettext/Gettext) library, which you can use to write out POT files if desired.

Additionally support for extraction of text values from PHP is possible when directly using the `translate` function 
from`\Acpr\I18n\Translator` in your code. For instance, you may be creating translated flash messages and storing them 
in your session to be used by subsequent twig templates.
 
### Supports
#### Twig
 * Translation tags (`{% trans %} ... {% endtrans %}`) and filters (`| trans`)
 * Variable interpolation `{% trans with { '%var%': var } %}%var%{% endtrans %}`
 * Pluralisation `{% trans count 3 %}%count% item|%count% items{% endtrans %}`
 * Message specific domains `{% trans from 'errors' %} ... {% endtrans %}`
 * Message contexts `{% trans %} ... {% context %}Some context{% endtrans %}`
 * Notes/comments for the translation `{% trans %} ... {% notes %}A translation note{% endtrans %}`
 
 
 * or some horrid combination of all of them
   ```twig
   {% trans count 5 with { '%name%': 'Adam' } from 'errors' %} 
     %name% has %count% apple|%name% has %count% apples
     {% notes %}A translation note
     {% context %}Some context to the translation
   {% endtrans %}
   ```
   
   The extraction of which would result in a `errors.pot` file that contains:
   ```
   #. A translation note
   msgctxt "Some context to the translation"
   msgid "%name% has %count% apple"
   msgid_plural "%name% has %count% apples"
   msgstr[0] ""
   ```
   
   And the (default, i.e. no language supplied) output of which would look like
   ```
   Adam has 5 apples
   ```
 
#### PHP
The PHP extraction works by parsing the text of your PHP files through [nikic/php-parser](https://github.com/nikic/PHP-Parser)
. This requires your PHP to be valid in order to work.

You could have a PHP file
```php
/** @var $translator \Acpr\I18n\Translator **/
$pluralApples = $translator->translate(
    '%name% has %count% apple',
    [
        '%name' => 'Adam'
    ],
    'errors',
    'Some context to the translation',
    '%name% has %count% apples',
    5
);

// Assuming no translations had been loaded
// $pluralApples == 'Adam has 5 apples'
```

The extraction of which would result in a `errors.pot` file that contains:
```
msgctxt "Some context to the translation"
msgid "%name% has %count% apple"
msgid_plural "%name% has %count% apples"
msgstr[0] ""
```

##### Limitations
 * The extraction specifically looks for usages of a `translate` function with the correct signature. This may result in 
   false positives dependent on your code base.
 * It is not currently possible to add notes/comments to a translation entry.
 * For the correct values to be parsed, the string arguments to the `translate` function **must** be inlined strings 
   (quoted or heredoc). It is **not** possible to use variables. 
   
   ```php
   // This will *not* work
   $var = 'I have an apple';
   $value = $translator->translate($var);
   
   // This will
   $value = $translator->translate('I have an apple');
   ```
   
   

## Usage
 
 > See [extract.php](example/extract.php) and [index.php](example/index.php) for example usage.

