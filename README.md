# hypeWall

![Elgg 7.x](https://img.shields.io/badge/Elgg-7.x-orange.svg?style=flat-square)

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

## Compatibility

| Plugin version | Elgg version |
|---|---|
| 7.0.0   | 7.x  |
| 6.0.0   | 6.x  |
| 5.0.0   | 5.x  |
| 4.0.0   | 4.x  |
| 3.0.0   | 3.x  |
