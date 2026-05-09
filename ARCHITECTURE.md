# hypeWall — Architecture (Elgg 6.x)

## Summary

Rich wall/status-update plugin for Elgg 6.x. Users and groups have a "wall" where
they can post rich updates with text, embedded URLs, file attachments, and
friend tagging. Posts appear in the activity river and trigger Elgg notifications.

## Entity Types

| Type | Subtype | Class | Notes |
|------|---------|-------|-------|
| object | hjwall | `hypeJunction\Wall\Post` | Rich wall post |

### `Post` capabilities

- `searchable: true`

## Plugin Structure (Elgg 5.x)

```
hypewall/
├── elgg-plugin.php            # Declarative config — routes, entities, events, actions
├── composer.json              # Requires elgg/elgg ^5.0, php >=8.1
├── classes/hypeJunction/Wall/
│   ├── Bootstrap.php          # DefaultPluginBootstrap (empty lifecycle methods)
│   ├── Post.php               # ElggObject subclass for hjwall
│   ├── Menus.php              # Static event handlers for all menu events
│   ├── Notifications.php      # Notification formatting and custom notify logic
│   └── Permissions.php        # Container permissions check (friend-wall rule)
├── actions/
│   ├── wall/status.php        # Create/update wall post
│   └── wall/remove_tag.php    # Remove tagged_in relationship
├── views/default/
│   ├── forms/wall/status.php  # Wall post form
│   ├── resources/wall/        # Page controllers (owner, container, view, edit)
│   ├── framework/wall/        # Layout components (container, quick links, etc.)
│   ├── object/hjwall.php      # Entity view
│   └── river/object/hjwall/   # River item view
└── languages/                 # Translation files
```

## Registered Events (elgg-plugin.php)

| Event | Type | Handler |
|-------|------|---------|
| `publish` | `object` | `Notifications::sendCustomNotifications` |
| `prepare` | `notification:publish:object:hjwall` | `Notifications::formatMessage` |
| `likes:is_likable` | `object:hjwall` | `Elgg\Values::getTrue` |
| `container_permissions_check` | `object` | `Permissions::containerPermissionsCheck` |
| `register` | `menu:river` | `Menus::riverMenuSetup` |
| `register` | `menu:entity` | `Menus::entityMenuSetup` |
| `register` | `menu:owner_block` | `Menus::ownerBlockMenuSetup` |
| `register` | `menu:user_hover` | `Menus::userHoverMenuSetup` |
| `register` | `menu:scraper:card` | `Menus::setupCardMenu` |
| `register` | `menu:wall:quick_links` | `Menus::setupQuickLinks` |
| `aliases` | `graph` | `Post::getGraphAlias` |
| `graph:properties` | `object:hjwall` | `Post::getPostProperties` |

## Routes

| Route Key | Path | Notes |
|-----------|------|-------|
| `default:object:hjwall` | `/wall/owner` | Gatekeeper middleware (login required) |
| `collection:object:hjwall` | `/wall/{guid}` | Container wall |
| `collection:object:hjwall:owner` | `/wall/owner/{username?}/{post_guids?}` | User wall |
| `collection:object:hjwall:group` | `/wall/group/{guid}/{post_guids?}` | Group wall |
| `view:object:hjwall` | `/wall/post/{guid}` | Single post view |
| `edit:object:hjwall` | `/wall/edit/{guid}` | Edit post |

## Actions

- `wall/status` — Create or update a wall post (tagging, attachments, river, publish event)
- `wall/remove_tag` — Remove `tagged_in` relationship between user and post

## Permissions Logic

`Permissions::containerPermissionsCheck` allows posting on another user's wall if:
1. The poster and container are friends (`check_entity_relationship`), OR
2. The global setting `third_party_wall` is enabled AND the container user's
   personal setting `third_party_wall` is also enabled.

## Dependencies

No hard plugin dependencies declared. Optional integrations via events:
- `hypeapps` — `hypeapps_extract_tokens`, `hypeapps_attach`, `hypeapps_scrape`
- `hypegraph` — `graph:properties`, `aliases:graph` events consumed
- `hypeattachments` — `attached` relationship rendered in `output/wall/attachments`

## Migration Notes (4.x → 5.x)

- `'hooks'` key merged into `'events'` key in `elgg-plugin.php`
- All handler method signatures updated from 4-arg to single `\Elgg\Event $event`
- `\Elgg\Hook` type hints replaced with `\Elgg\Event`
- `elgg_trigger_plugin_hook()` replaced with `elgg_trigger_event_results()`
- `ElggUser::isFriend()` removed — replaced with `elgg_get_relationships()` count check
- `check_entity_relationship()` removed — replaced with `elgg_get_relationships()` count check
- `Post::getDisplayName()` now declares `: string` return type (required by Elgg 5.x parent)
- `composer.json` bumped to `php >=8.1`, `elgg/elgg ^5.0`
- Docker stack updated to PHP 8.1, MySQL 8.0, Elgg 5.1

## Migration Notes (5.x → 6.x)

- `composer.json`: `elgg/elgg ~6.1.0`, added `ext-intl`
- `elgg-plugin.php`: added `version => 6.0.0`
- `framework/wall/container.js`: converted from AMD `define(function(require){...})` to ES module; `elgg.echo()` → `i18n.echo()` (using `import i18n from 'elgg/i18n'`); `elgg.nullFunction()` → `function(){}` (removed in 6.x)
- `output/wall/attachments.js`: converted from AMD to ES module
- `framework/wall/container.php`: inline `require(['framework/wall/container'], cb)` → `<script type="module">` ES import
- `output/wall/attachments.php`: inline `require(['output/wall/attachments'], cb)` → `<script type="module">` ES import
- Docker stack updated to Elgg 6.x (PHPUnit ^10.5)
- No data migration required
