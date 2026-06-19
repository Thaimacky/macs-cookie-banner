# Language Files

This directory is prepared for WordPress translation files.

Template:

- `macs-cookie-banner.pot`

Locale catalogues (`.po`) and compiled `.mo` (since v0.2.1):

- `macs-cookie-banner-de_CH.po` / `.mo`
- `macs-cookie-banner-en_US.po` / `.mo`
- `macs-cookie-banner-fr_FR.po` / `.mo`
- `macs-cookie-banner-it_IT.po` / `.mo`
- `macs-cookie-banner-tr_TR.po` / `.mo`
- `macs-cookie-banner-hu_HU.po` / `.mo`

Scope: front-end / visitor-facing strings (category labels and descriptions,
legal links, service-component placeholders) are translated in all six
languages. Admin-only page strings stay as the German source (operator
language) and are listed untranslated in the `.po`/`.pot` (see ADR-19).

The seven editable banner strings get their language-aware defaults from the
PHP locale table (`get_default_text_table()`), not from these `.mo` files;
WPML / Polylang String Translation remains the override path.

The `.pot` template is generated from the real source call sites (POT audit).
When adding or changing translatable strings, update the `.po` files and
recompile the `.mo`.
