# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module Overview

Drupal migrate module that imports TV shows, episodes, and cast members from the TV Maze REST API. Creates three content types (`tv_show`, `tv_episode`, `tv_cast_member`) and two taxonomy vocabularies (`tv_genre`, `tv_network`).

## Development Commands

```bash
# Install module (also installs migrate_plus, migrate_tools)
drush en tv_maze_migrate

# Run migrations (ORDER MATTERS - shows must complete before episodes/cast)
drush migrate:import tv_maze_shows
drush migrate:import tv_maze_episodes
drush migrate:import tv_maze_cast

# Check status
drush migrate:status --group=tv_maze

# Re-import with updates
drush migrate:import tv_maze_shows --update

# Rollback (reverse order)
drush migrate:rollback --group=tv_maze

# Export config changes (run from Drupal root)
drush config:export --diff
```

## Architecture

### Migration Dependency Chain

```
tv_maze_shows → tv_maze_episodes (uses migration_lookup to reference show)
             → tv_maze_cast (uses migration_lookup to reference show)
```

### Custom Plugins (`src/Plugin/`)

| Plugin | Location | Purpose |
|--------|----------|---------|
| `json_object` | `migrate_plus/data_parser/JsonObject.php` | Wraps single-object API responses in array for migrate |
| `tvmaze_terms` | `migrate/process/TvMazeTerms.php` | Get-or-create taxonomy terms from string values |
| `tvmaze_image_download` | `migrate/process/TvMazeImageDownload.php` | Download remote images to managed files |
| `tv_maze_cast_url` | `migrate/source/TvMazeCastUrl.php` | Extract show ID from URL, inject into cast rows |

### Config in Database

Migration config is copied to the database on install. To modify migrations after install:

```bash
# Update via drush php:eval (example in README.md)
# Then clear cache
drush cr
```

## API Notes

- TV Maze rate limits: ~20 requests/10 seconds
- For large imports: `drush migrate:import tv_maze_episodes --limit=50 --feedback=10`
- Find show IDs at: `https://api.tvmaze.com/search/shows?q=<name>`
