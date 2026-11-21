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

- **Docker stack**: PHP 8.5 FPM Alpine + Nginx Alpine + Traefik v3 reverse proxy (all official images)
- **Backend** (`html/fetch-giphys.php`): Authenticates with Giphy API using iOS SDK headers, fetches all user favourites paginated at 100/request, enforces a 10-minute cooldown between fetches via PHP sessions
- **Frontend** (`html/index.php`, `html/js/script.js`): jQuery + Bootstrap 5 SPA-style page. GIF data is cached in localStorage after first fetch. Client-side search filters by title and tags. URL query param `?s=` syncs with search bar.
- **Layout**: GIFs render in a Masonry (`masonry-layout`) grid; `imagesLoaded` drives per-image fade-in. Each item reserves space via a CSS `aspect-ratio` derived from the original's width/height, so there's no layout shift — a shimmer skeleton shows until the image loads. Items cached before width/height existed fall back to natural flow (`.flow` class).
- **Keyboard nav**: `/` focuses search, arrow keys move a selection highlight (geometric nearest-neighbour, works with the irregular Masonry layout), Enter copies the selected GIF, Esc blurs search.
- **Vendored assets** (`html/vendor/`): jQuery, Bootstrap CSS/JS, Masonry, and imagesLoaded are served locally — no external CDN dependencies.
- **Styles** (`html/css/style.css`): Custom styles on top of Bootstrap

## Production

Repo `Nicasso-org/giphy-favourites` (the app and its Docker wrapper, merged 2026-11-21).
A push to `master` tests in the shipped image and deploys on the mini through the org
runner into `/home/nico/Sites/giphy` (fixed path: the repo name differs). The deploy
gate checks health and the SSO gate. `html/settings.php` lives only on the mini and is
mounted read-only. Never `docker compose down -v`: the `gifs` volume holds the copies
that shared links point at.

Single-container build (PHP-FPM + Nginx in one Alpine image). Exposes port 80 internally only — Caddy on the host handles external traffic and reverse proxies into the `edge-giphy` Docker network.

## Key Behaviors

- `fetch-giphys.php` rate-limits to one fetch per 10 minutes per session
- On auth failure during fetch, it retries once after re-login
- Clicking a GIF: first click animates (swaps the still for the GIF) and copies its URL; subsequent clicks just copy
- **Every GIF is served from here, in full quality.** `fetch-giphys.php` answers
  with local paths (`/gifs/<id>.gif` = Giphy's `original`, `/gifs/still/<id>.gif`
  = `original_still`), then, after the response is sent (`fastcgi_finish_request`),
  downloads whatever is missing into the `gifs` volume (`/var/www/gifs`). nginx
  serves those as immutable and 302s a not-yet-downloaded one to Giphy, so the
  paths are valid from the first fetch on. No 200px renditions any more.
- **A click plays a lighter rendition, the clipboard gets the original.** `url` is
  `/gifs/play/<id>.gif` (Giphy's `downsized_medium`, max 5 MB) when that is
  smaller than the original, else the original; `url_original` is always
  `/gifs/<id>.gif`. A localStorage cache without any `/gifs/play/` url triggers
  the same silent refresh as one that predates the local copies.
- **`/gifs/*` is public** (Caddyfile: outside `import sso`, exempt from `geo_nl`
  and the link-preview block) so a copied link unfurls in Slack. Everything else
  stays behind the SSO. The volume is never pruned: unfavouriting a GIF must not
  break a link already shared.
- The service worker passes `/gifs/` through, so the library never lands in its cache.
- Anything in localStorage that does not point at `/gifs/` makes the page do one
  silent background refresh (never drop the cache to force this — the 10-minute
  cooldown would leave an empty grid)
- `health.php` is public (the SSO leaves it open): `{ok}` 200/503 on real
  credentials in `settings.php` and a writable gifs volume; the deploy gate checks it.
- **The app checks the SSO too.** `index.php` and `fetch-giphys.php` call
  `require_sso()` (`html/lib/auth.php`): 401 unless `Remote-User` equals
  `SSO_USER` (default `nico`, empty = nobody). `tests/sso_check.php` (outside the
  image) checks it; CI runs it inside the shipped image. The front end reloads into the login
  on a 401 or redirect.
- Manifest and icons sit on paths the SSO leaves open: `/manifest.webmanifest`,
  `/icons/*`. Do not move them back under `/assets/`.
- `settings.php` contains secrets and must never be committed — use `default.settings.php` as the template
