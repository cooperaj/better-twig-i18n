Better Twig i18n
======

This package takes code from the Symfony translation packages which, for reasons unknown to me, are marked `final` and 
alters them to provide enhanced functionality around the handling of _PO_ and _POT_ files as well as their extraction
from __twig__ templates.

 * Includes a _POT_ file dumper that does not write `msgstr` values.
 * Adds source references to the PO/POT output e.g. `#: templates/home.html.twig:6`
 * Adds the capability for `msgctxt` and `#. extracted comments` to be pulled from the templates.
 * Is BC compatible with the original Symfony Twig translation syntax e.g. `{% trans %} ... {% endtrans %}` 
 and ` | trans`"
 
 ## Usage
 
 > See [extract.php](example/extract.php) and [index.php](example/index.php) for example usage.

