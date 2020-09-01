Better Twig Gettext i18n
======

![Build](https://github.com/cooperaj/better-twig-i18n/workflows/Build/badge.svg)
[![codecov](https://codecov.io/gh/cooperaj/better-twig-i18n/branch/master/graph/badge.svg)](https://codecov.io/gh/cooperaj/better-twig-i18n)



Allows the use of a POT/PO/MO (gettext) set of language definitions with Twig, using an identical syntax to the current
official [Twig translation extension](https://github.com/symfony/twig-bridge).

It supports full extraction of Twig templates into language catalogues from the 
[gettext/gettext](https://github.com/php-gettext/Gettext) library, which you can use to write out POT files if desired.
 
### Supports
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
   
   The extraction of which would result in a POT file that looked like:
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
 
## Usage
 
 > See [extract.php](example/extract.php) and [index.php](example/index.php) for example usage.

