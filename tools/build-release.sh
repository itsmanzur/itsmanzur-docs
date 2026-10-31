#!/usr/bin/env bash
# Build the wordpress.org release zip: itsmanzur-docs-<version>.zip
#
# Usage: tools/build-release.sh [composer-command]
#
# Ships the compiled build/ assets and a production vendor/ (the plugin refuses to
# start without vendor/autoload.php), and fails if the zip is missing either.
# Uses `git archive`, so only committed files are included; honours .distignore.
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSER="${1:-composer}"
SLUG="itsmanzur-docs"
VERSION="$(sed -n "s/^ \* Version:[[:space:]]*//p" "$SLUG.php" | tr -d '\r')"
STAGE="$(mktemp -d)"
OUT="$PWD/$SLUG-$VERSION.zip"

[ -f build/frontend.js ] || { echo "build/ is missing - run npm run build first" >&2; exit 1; }

git archive --prefix="$SLUG/" HEAD | tar -x -C "$STAGE"
cp -r build "$STAGE/$SLUG/build"

(
	cd "$STAGE/$SLUG"
	$COMPOSER install --no-dev --optimize-autoloader --no-interaction --quiet

	# Remove everything listed in .distignore (simple names and globs).
	while IFS= read -r pattern || [ -n "$pattern" ]; do
		pattern="${pattern%$'\r'}"
		case "$pattern" in ''|'#'*|'!'*) continue ;; esac
		# shellcheck disable=SC2086
		rm -rf ${pattern%/} 2>/dev/null || true
	done < <(grep -v '^!' .distignore)
	rm -rf .distignore
)

# vendor/ must survive the cleanup above.
[ -f "$STAGE/$SLUG/vendor/autoload.php" ] || { echo "FAIL: vendor/autoload.php missing from the release" >&2; exit 1; }

rm -f "$OUT"
(cd "$STAGE" && if command -v zip >/dev/null 2>&1; then zip -qr "$OUT" "$SLUG"; else tar -a -cf "$OUT" "$SLUG"; fi)

# Verify the finished archive, not just the staging folder.
LIST="$(unzip -l "$OUT" 2>/dev/null || tar -tf "$OUT")"
for required in "$SLUG/vendor/autoload.php" "$SLUG/build/frontend.js" "$SLUG/readme.txt"; do
	echo "$LIST" | grep -q "$required" || { echo "FAIL: $required missing from $OUT" >&2; exit 1; }
done

rm -rf "$STAGE"
echo "OK: $OUT"
sha256sum "$OUT"
