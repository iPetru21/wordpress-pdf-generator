#!/usr/bin/env bash
#
# Generează wordpress-pdf-generator.zip pentru instalarea plugin-ului pe alte site-uri.
# Rulează din directorul rădăcină al plugin-ului.
#

set -e

PLUGIN_SLUG="wordpress-pdf-generator"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ZIP_PATH="$SCRIPT_DIR/${PLUGIN_SLUG}.zip"

# Opțional: INCLUDE_TESTS=1 pentru a include directorul tests/
INCLUDE_TESTS="${INCLUDE_TESTS:-0}"

cd "$SCRIPT_DIR/.."

rm -f "$ZIP_PATH"

echo "Construiesc ${PLUGIN_SLUG}.zip..."

# Excludem fișiere/directoare care nu trebuie în pachetul de instalare
EXCLUDES=(
  ".git"
  ".gitignore"
  ".cursor"
  ".idea"
  ".vscode"
  "node_modules"
  "*.log"
  ".DS_Store"
  "*.swp"
  "*.swo"
  "*~"
  "*.tmp"
  "*.bak"
  "composer.lock"
  "build-plugin-zip.sh"
)

if [[ "$INCLUDE_TESTS" != "1" ]]; then
  EXCLUDES+=("tests/*" "tests/*/*")
fi

# Construim zip din directorul părinte, astfel în arhivă rădăcina e wordpress-pdf-generator/
ZIP_EXCLUDES=()
for ex in "${EXCLUDES[@]}"; do
  ZIP_EXCLUDES+=(-x "$PLUGIN_SLUG/$ex" -x "$PLUGIN_SLUG/*/$ex" -x "$PLUGIN_SLUG/*/*/$ex")
done

# Pe macOS zip nu exclude directoarele doar cu -x "dir"; trebuie conținutul explicit
zip -r "$ZIP_PATH" "$PLUGIN_SLUG" \
  -x "*.zip" \
  -x "$PLUGIN_SLUG/.git/*" \
  -x "$PLUGIN_SLUG/.git/*/*" \
  -x "$PLUGIN_SLUG/.git/*/*/*" \
  -x "$PLUGIN_SLUG/.git/*/*/*/*" \
  -x "$PLUGIN_SLUG/.cursor/*" \
  -x "$PLUGIN_SLUG/.gitignore" \
  -x "$PLUGIN_SLUG/.idea/*" \
  -x "$PLUGIN_SLUG/.vscode/*" \
  -x "$PLUGIN_SLUG/node_modules/*" \
  "${ZIP_EXCLUDES[@]}"

echo "Gata: $ZIP_PATH"
echo "Poți încărca acest fișier din WordPress: Preinstalare pluginuri -> Încarcă plugin."
