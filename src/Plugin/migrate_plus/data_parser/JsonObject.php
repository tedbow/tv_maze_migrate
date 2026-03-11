<?php

namespace Drupal\tv_maze_migrate\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Parses JSON responses that may return a single object instead of an array.
 *
 * The standard JSON parser requires the source data to be an indexed array of
 * items. When the API returns a single JSON object (e.g. /shows/{id}), this
 * parser wraps it in an array so the migration processes exactly one row per
 * URL. When the response is already an indexed array, it behaves identically
 * to the standard JSON parser.
 *
 * @DataParser(
 *   id = "json_object",
 *   title = @Translation("JSON (single object or array)")
 * )
 */
class JsonObject extends Json {

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl(string $url): bool {
    $source_data = $this->getSourceData($url, $this->itemSelector);

    if (is_null($source_data)) {
      return FALSE;
    }

    // If the source data is an associative array (single JSON object), wrap it
    // so the ArrayIterator yields one row.
    if (is_array($source_data) && !isset($source_data[0]) && !empty($source_data)) {
      $source_data = [$source_data];
    }

    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

}
