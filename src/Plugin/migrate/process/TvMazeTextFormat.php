<?php

namespace Drupal\tv_maze_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Returns an available text format, preferring HTML-capable formats.
 *
 * Dynamically checks which text formats exist on the site and returns
 * the most appropriate one for HTML content.
 *
 * Usage:
 * @code
 * field_summary/format:
 *   plugin: tvmaze_text_format
 * @endcode
 */
#[MigrateProcess('tvmaze_text_format')]
class TvMazeTextFormat extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    // Preferred formats for HTML content, in order of preference.
    $preferred = ['full_html', 'basic_html', 'filtered_html', 'restricted_html'];

    $formats = $this->entityTypeManager->getStorage('filter_format')->loadMultiple();

    foreach ($preferred as $format_id) {
      if (isset($formats[$format_id]) && $formats[$format_id]->status()) {
        return $format_id;
      }
    }

    // Fallback to first available enabled format.
    foreach ($formats as $format) {
      if ($format->status()) {
        return $format->id();
      }
    }

    return 'plain_text';
  }

}
