#!/bin/bash
#
# Regenerates the iOS launch images behind the apple-touch-startup-image links
# in <head>. Byte-identical across the portfolio apart from the config block.
#
# Rendered with headless Chrome, not a baked font file, so the wordmark keeps
# the site's own system-font stack.
#
# Chrome enforces a ~502px minimum layout viewport, so a phone-width window
# centres the content over 502px and then crops to the real width. Hence:
# render at physical pixels with device scale 1 and scale every size by the
# pixel ratio here.
#
set -euo pipefail

# --- app-specific ------------------------------------------------------------
BG="#121212"
FG="#ffffff"
TITLE="Giphy"
ICON="html/icons/android-chrome-512x512.png"
# Ground behind a glyph icon that has no background of its own; `transparent`
# for an icon that is already a full tile.
TILE="transparent"
OUT_REL="html/splash"
# --- end app-specific --------------------------------------------------------

ROOT="$(cd "$(dirname "$0")" && pwd)"
CHROME="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
OUT="$ROOT/$OUT_REL"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

mkdir -p "$OUT"
ICON_URI="data:image/png;base64,$(base64 -i "$ROOT/$ICON" | tr -d '\n')"
[ "$TILE" = transparent ] && INSET=100 || INSET=78

# Portrait css width, css height, device pixel ratio. Landscape is derived.
DEVICES="320 568 2
375 667 2
414 736 3
375 812 3
414 896 2
414 896 3
390 844 3
428 926 3
393 852 3
430 932 3
402 874 3
440 956 3
744 1133 2
768 1024 2
810 1080 2
820 1180 2
834 1112 2
834 1194 2
1024 1366 2"

echo "$DEVICES" | while read -r w h d; do
  # Every size comes off the portrait width, so one device shows the same
  # splash however it is held.
  fs=$(python3 -c "print(round(min(0.076*$w, 40)*$d, 2))")
  ic=$(python3 -c "print(round(min(0.30*$w, 148)*$d, 2))")
  gp=$(python3 -c "print(round(min(0.055*$w, 28)*$d, 2))")
  sh=$(python3 -c "print(round(min(0.02*$w, 10)*$d, 2))")

  cat > "$TMP/splash.html" <<HTML
<!doctype html>
<html><head><meta charset="utf-8"><style>
  html,body{margin:0;padding:0;width:100%;height:100%;overflow:hidden;background:$BG;}
  body{display:flex;flex-direction:column;align-items:center;justify-content:center;}
  .icon{
    width:${ic}px;height:${ic}px;
    border-radius:22.37%;
    background:$TILE;
    box-shadow:0 ${sh}px $(python3 -c "print(round($sh*2.4,2))")px rgba(0,0,0,.22);
    display:flex;align-items:center;justify-content:center;overflow:hidden;
  }
  .icon img{width:${INSET}%;height:${INSET}%;display:block;}
  .brand{
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif;
    font-weight:700;
    font-size:${fs}px;
    line-height:1.2;
    color:$FG;
    letter-spacing:-0.01em;
    white-space:nowrap;
    margin-top:${gp}px;
  }
</style></head><body><div class="icon"><img src="$ICON_URI" alt=""></div><div class="brand">$TITLE</div></body></html>
HTML

  for orientation in portrait landscape; do
    if [ "$orientation" = portrait ]; then pw=$((w*d)); ph=$((h*d)); else pw=$((h*d)); ph=$((w*d)); fi
    f="$OUT/splash-${pw}x${ph}.png"
    "$CHROME" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=1 \
      --window-size="$pw,$ph" --screenshot="$f" "file://$TMP/splash.html" >/dev/null 2>&1
    # Flat ground plus one small icon: a 256-colour palette is visually lossless
    # and takes the set from megabytes to a few hundred KB.
    magick "$f" -strip -colors 256 -define png:compression-level=9 PNG8:"$f.new"
    mv "$f.new" "$f"
  done
  echo "  ${w}x${h}@${d}"
done

echo "Done — $(ls "$OUT" | wc -l | tr -d ' ') images in $OUT_REL."
echo "Keep the device table in sync with the one in the page head."
