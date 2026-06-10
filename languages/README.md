# Language Files

This directory is prepared for WordPress translation files.

Template:

- `light-swiss-cookie-consent.pot`

Locale catalogues (`.po`) and compiled `.mo` (since v0.2.1):

- `light-swiss-cookie-consent-de_CH.po` / `.mo`
- `light-swiss-cookie-consent-en_US.po` / `.mo`
- `light-swiss-cookie-consent-fr_FR.po` / `.mo`
- `light-swiss-cookie-consent-it_IT.po` / `.mo`
- `light-swiss-cookie-consent-tr_TR.po` / `.mo`
- `light-swiss-cookie-consent-hu_HU.po` / `.mo`

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
