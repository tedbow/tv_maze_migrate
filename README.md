# TV Maze Migrate

A custom Drupal module that imports TV shows, episodes, and cast members from
the [TV Maze REST API](https://www.tvmaze.com/api) using Drupal Migrate.

## Requirements

- Drupal 10 or 11
- [Migrate Plus](https://www.drupal.org/project/migrate_plus)
- [Migrate Tools](https://www.drupal.org/project/migrate_tools)
- Drush

All dependencies are declared in `tv_maze_migrate.info.yml` and will be enabled
automatically when this module is installed.

## What Gets Created

### Taxonomy Vocabularies

| Vocabulary | Machine name | Description |
|---|---|---|
| TV Genre | `tv_genre` | Genres (Drama, Comedy, etc.) — created automatically during import |
| TV Network | `tv_network` | Broadcast/streaming networks — created automatically during import |

### Content Types

| Content Type | Machine name | Description |
|---|---|---|
| TV Show | `tv_show` | A television series |
| TV Episode | `tv_episode` | An individual episode, referenced to its show |
| TV Cast Member | `tv_cast_member` | An actor/character pairing, referenced to its show |

### Fields — TV Show

| Field | Machine name | Type |
|---|---|---|
| TV Maze ID | `field_tvmaze_id` | Integer |
| Summary | `field_summary` | Text (formatted, long) |
| Premiered | `field_premiered` | Date |
| Ended | `field_ended` | Date |
| Status | `field_show_status` | List (Running / Ended / To Be Determined / In Development) |
| Genres | `field_genres` | Entity reference → `tv_genre` (multi-value) |
| Rating | `field_rating` | Decimal |
| Network | `field_network` | Entity reference → `tv_network` |
| Official Site | `field_official_site` | Link |
| IMDB ID | `field_imdb_id` | Text (plain) |
| Show Image | `field_show_image` | Image (downloaded to `public://tvmaze/shows/`) |

### Fields — TV Episode

| Field | Machine name | Type |
|---|---|---|
| TV Maze Episode ID | `field_tvmaze_episode_id` | Integer |
| Season | `field_season` | Integer |
| Episode Number | `field_episode_number` | Integer |
| Air Date | `field_airdate` | Date |
| Runtime | `field_runtime` | Integer (minutes) |
| Episode Summary | `field_episode_summary` | Text (formatted, long) |
| Episode Image | `field_episode_image` | Image (downloaded to `public://tvmaze/episodes/`) |
| TV Show | `field_tv_show` | Entity reference → `tv_show` node |

### Fields — TV Cast Member

| Field | Machine name | Type |
|---|---|---|
| TV Maze Person ID | `field_tvmaze_person_id` | Integer |
| Character Name | `field_character_name` | Text (plain) |
| Birthday | `field_birthday` | Date |
| Gender | `field_gender` | Text (plain) |
| Person Image | `field_person_image` | Image (downloaded to `public://tvmaze/people/`) |
| TV Show | `field_tv_show` | Entity reference → `tv_show` node |

## Installation

```bash
drush en tv_maze_migrate
```

This installs the module along with `migrate_plus` and `migrate_tools` if they
are not already enabled, and creates all content types, fields, and taxonomy
vocabularies.

## Running Migrations

Migrations must be run in this order because episodes and cast depend on shows:

```bash
vendor/bin/drush migrate:import tv_maze_shows
vendor/bin/drush migrate:import tv_maze_episodes
vendor/bin/drush migrate:import tv_maze_cast
```

Check status at any time:

```bash
drush migrate:status --group=tv_maze
```

Re-import with updates (for already-imported items):

```bash
drush migrate:import tv_maze_shows --update
drush migrate:import tv_maze_episodes --update
drush migrate:import tv_maze_cast --update
```

Roll back all imported content:

```bash
drush migrate:rollback tv_maze_cast
drush migrate:rollback tv_maze_episodes
drush migrate:rollback tv_maze_shows
```

Or roll back the entire group at once:

```bash
drush migrate:rollback --group=tv_maze
```

## Configuring Which Shows to Import

The shows to import are defined by TV Maze show IDs in the `urls` arrays of the
three migration config files. By default shows 1–5 are included:

| ID | Show          |
|--|---------------|
| 23470 | Succession    |
| 157 | The Americans |
| 1871 | Mr Robot      |
| 75632 | The Pitt      |
| 60213 | The Diplomat  |

To find a show's ID, search at `https://api.tvmaze.com/search/shows?q=<name>`.

### Updating the URL lists

Since migration config is stored in the database after installation, update it
with Drush:

```bash
# Example: add show ID 82 (Game of Thrones)
vendor/bin/drush php:eval "
\$ids = [23470, 157, 1871, 75632, 60213];
foreach (['tv_maze_shows', 'tv_maze_episodes', 'tv_maze_cast'] as \$migration) {
  \$suffix = (strpos(\$migration, 'shows') !== FALSE) ? '' : (strpos(\$migration, 'episodes') !== FALSE ? '/episodes' : '/cast');
  \$urls = array_map(fn(\$id) => \"https://api.tvmaze.com/shows/{\$id}{\$suffix}\", \$ids);
  \Drupal::configFactory()->getEditable(\"migrate_plus.migration.{\$migration}\")
    ->set('source.urls', \$urls)
    ->save();
}
echo 'Done';
"
vendor/bin/drushdrush cr
```

Then re-run the migrations with `--update` to import the new shows while
preserving already-imported content.

## Rate Limiting

TV Maze limits unauthenticated requests to approximately 20 per 10 seconds.
For large imports use the `--limit` and `--feedback` flags:

```bash
drush migrate:import tv_maze_episodes --limit=50 --feedback=10
```

## Custom Plugins

This module ships four custom plugins to handle TV Maze API specifics:

| Plugin | Type | Purpose |
|---|---|---|
| `json_object` | Data parser | Parses single-object JSON responses (e.g. `/shows/{id}`) |
| `tvmaze_terms` | Process | Looks up or creates a taxonomy term from a single string value; called once per genre/network by migrate's built-in array iteration |
| `tvmaze_image_download` | Process | Downloads a remote image URL and saves it as a Drupal managed file; returns the file entity ID |
| `tv_maze_cast_url` | Migrate source | Extends the migrate_plus `url` source plugin; injects `show_tvmaze_id` into each cast row by parsing the current request URL |

## API Endpoints Used

| Data | Endpoint |
|---|---|
| Show details | `https://api.tvmaze.com/shows/{id}` |
| Episode list | `https://api.tvmaze.com/shows/{id}/episodes` |
| Cast list | `https://api.tvmaze.com/shows/{id}/cast` |
