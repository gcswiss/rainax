#!/usr/bin/env bash
# Bereitet ein Video als Hintergrundvideo für die Webseite auf.
#
#   tools/encode-video.sh <eingabe.mp4> [name]
#
# Erzeugt in assets/video/:
#   <name>.mp4         H.264, 1920 px breit, ohne Ton  (läuft überall, auch iPhone)
#   <name>.webm        VP9, 1920 px breit, ohne Ton    (kleiner, für Chrome/Firefox)
#   <name>-poster.jpg  Standbild, wird angezeigt, bis das Video läuft
#
# Standardname ist "hero". Benötigt ffmpeg (https://ffmpeg.org).
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Aufruf: $0 <eingabe-video> [name]" >&2
  exit 1
fi

IN="$1"
NAME="${2:-hero}"
OUT="$(cd "$(dirname "$0")/.." && pwd)/assets/video"
mkdir -p "$OUT"

# Höchstens 1920 px breit, Höhe gerade, Bildrate wie im Original
SCALE="scale='min(1920,iw)':-2"

echo "→ $NAME.mp4"
ffmpeg -hide_banner -loglevel error -y -i "$IN" -an -vf "$SCALE" \
  -c:v libx264 -profile:v high -preset slow -crf 28 -pix_fmt yuv420p \
  -movflags +faststart "$OUT/$NAME.mp4"

echo "→ $NAME.webm"
ffmpeg -hide_banner -loglevel error -y -i "$IN" -an -vf "$SCALE" \
  -c:v libvpx-vp9 -b:v 0 -crf 42 -row-mt 1 -deadline good -cpu-used 2 \
  "$OUT/$NAME.webm"

echo "→ $NAME-poster.jpg"
ffmpeg -hide_banner -loglevel error -y -i "$IN" -vf "scale='min(1920,iw)':-2" \
  -frames:v 1 -q:v 6 "$OUT/$NAME-poster.jpg"

ls -lh "$OUT/$NAME.mp4" "$OUT/$NAME.webm" "$OUT/$NAME-poster.jpg"
