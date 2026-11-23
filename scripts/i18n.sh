#!/bin/zsh
# Refreshes the French translation from the PHP sources, compiles it and lists what is left to translate.
# Run: npm run i18n (needs gettext: brew install gettext)
set -e
cd "${0:A:h}/.."
pot=$(mktemp)
xgettext --language=PHP --from-code=UTF-8 --add-comments=translators: \
  --keyword=__ --keyword=esc_html__ --keyword=esc_html_e --keyword=esc_attr__ --keyword=esc_attr_e \
  -o "$pot" no-thanks.php lib/*.php
po=languages/no-thanks-fr_FR.po
msgmerge --quiet --update --backup=none --no-fuzzy-matching --no-location "$po" "$pot"
msgattrib --no-obsolete --no-wrap -o "$po" "$po"
msgfmt -o languages/no-thanks-fr_FR.mo "$po"
rm "$pot"
left=$(msgattrib --untranslated --no-wrap "$po" | grep -c '^msgid "..*"' || true)
echo "Translation compiled; $left string(s) left to translate in $po"
