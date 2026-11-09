# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A personal Giphy favourites viewer that fetches a user's Giphy favourites via the Giphy API and displays them in a searchable grid. Clicking a GIF plays it and copies the URL to clipboard.

## Running Locally

```bash
docker-compose up
```

Site is accessible at `http://giphy.docker.localhost`. Traefik dashboard at `http://localhost:8080`.

## Setup

Copy `html/default.settings.php` to `html/settings.php` and fill in your Giphy email, password, and API key (from https://developers.giphy.com/dashboard/).

## Architecture

- **Docker stack**: PHP 8.2 FPM Alpine + Nginx Alpine + Traefik v3 reverse proxy (all official images)
- **Backend** (`html/fetch-giphys.php`): Authenticates with Giphy API using iOS SDK headers, fetches all user favourites paginated at 100/request, enforces a 10-minute cooldown between fetches via PHP sessions
- **Frontend** (`html/index.php`, `html/js/script.js`): jQuery + Bootstrap 5 SPA-style page. GIF data is cached in localStorage after first fetch. Client-side search filters by title and tags. URL query param `?s=` syncs with search bar.
- **Layout**: GIFs render in a Masonry (`masonry-layout`) grid; `imagesLoaded` drives per-image fade-in. Each item reserves space via a CSS `aspect-ratio` derived from the API's `fixed_height` width/height, so there's no layout shift — a shimmer skeleton shows until the image loads. Items cached before width/height existed fall back to natural flow (`.flow` class).
- **Keyboard nav**: `/` focuses search, arrow keys move a selection highlight (geometric nearest-neighbour, works with the irregular Masonry layout), Enter copies the selected GIF, Esc blurs search.
- **Vendored assets** (`html/vendor/`): jQuery, Bootstrap CSS/JS, Masonry, and imagesLoaded are served locally — no external CDN dependencies.
- **Styles** (`html/css/style.css`): Custom styles on top of Bootstrap

## Production

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Single-container build (PHP-FPM + Nginx in one Alpine image). Exposes port 80 internally only — Caddy on the host handles external traffic and reverse proxies into the `edge-giphy` Docker network.

## Key Behaviors

- `fetch-giphys.php` rate-limits to one fetch per 10 minutes per session
- On auth failure during fetch, it retries once after re-login
- Clicking a GIF: first click animates (swaps still image for animated URL) and copies URL; subsequent clicks just copy
- **Two URLs per GIF, deliberately.** The grid shows/plays `url` (Giphy's
  `fixed_height`, always 200px tall) so the page stays light; the clipboard and
  the `<a href>` get `url_original` (`images.original`, unscaled, tracking query
  params stripped). Don't "simplify" these into one. `original` is occasionally
  smaller in pixels than `fixed_height` — Giphy upscales short GIFs to reach
  200px — which is correct: unscaled is the point.
- `url_original` is absent from anything cached in localStorage before it
  existed, so `addGiphy` falls back to `url` and the page kicks off one silent
  background refresh to upgrade the cache (never drop the cache to force this —
  the 10-minute cooldown would leave an empty grid)
- `settings.php` contains secrets and must never be committed — use `default.settings.php` as the template
