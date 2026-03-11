<?php

namespace Drupal\tv_maze_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Extends the migrate_plus URL source to inject show_tvmaze_id from the URL.
 *
 * The TV Maze cast endpoint (/shows/{id}/cast) returns an array of cast
 * members with no reference back to the show. This plugin reads the current
 * URL from the data parser plugin and injects the show ID as `show_tvmaze_id`
 * on every row, allowing the migration to use migration_lookup to link cast
 * members back to their show node.
 *
 * @MigrateSource(
 *   id = "tv_maze_cast_url",
 *   source_module = "tv_maze_migrate"
 * )
 */
class TvMazeCastUrl extends Url {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    $result = parent::prepareRow($row);

    if ($result) {
      $currentUrl = $this->getDataParserPlugin()->currentUrl();
      if ($currentUrl && preg_match('|/shows/(\d+)/cast|', $currentUrl, $matches)) {
        $row->setSourceProperty('show_tvmaze_id', (int) $matches[1]);
      }
    }

    return $result;
  }

}
