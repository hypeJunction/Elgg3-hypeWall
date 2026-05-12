# hypeWall

![Elgg 6.x](https://img.shields.io/badge/Elgg-6.x-orange.svg?style=flat-square)

![Elgg 5.0](https://img.shields.io/badge/Elgg-5.0-orange.svg?style=flat-square)

Adds a social activity wall to Elgg sites, letting users post rich status updates with URL embeds, photo attachments, friend tagging, and geolocation.

## Features

- Rich status update composer with URL auto-embed and preview card generation
- Inline multi-file photo attachments via drag-and-drop
- Geolocation tagging based on browser location (reverse geocoded via Nominatim)
- Friend tagging in posts
- Extensible wall tabs and form sections via the `framework/wall/container/extend` view
- Integrates with hypeScraper (URL previews), hypeDropzone (drag-and-drop uploads), and hypeLists (real-time updates)

## Installation

**Via Composer (recommended):**

```bash
composer require hypejunction/hypewall
```

**Manual:**

Download the zip, extract into your Elgg `mod/` directory, and activate in the admin panel.

## License

GPL-2.0-or-later
