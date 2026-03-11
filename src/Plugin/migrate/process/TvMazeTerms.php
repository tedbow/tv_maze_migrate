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
 * Looks up or creates taxonomy terms from an array of string values.
 *
 * Returns an array of ['target_id' => tid] suitable for entity reference fields.
 *
 * Configuration:
 *   - vocabulary: The taxonomy vocabulary machine name.
 *
 * Usage:
 * @code
 * field_genres:
 *   plugin: tvmaze_terms
 *   source: genres
 *   vocabulary: tv_genre
 * @endcode
 *
 * @MigrateProcess("tvmaze_terms")
 */
#[MigrateProcess('tvmaze_terms')]
class TvMazeTerms extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
   *
   * Accepts a single genre string, looks up or creates the taxonomy term,
   * and returns ['target_id' => tid]. Migrate automatically iterates source
   * arrays so this is called once per genre value.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): array|null {
    $name = trim((string) $value);
    if ($name === '') {
      return NULL;
    }

    $vocabulary = $this->configuration['vocabulary'];
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $terms = $storage->loadByProperties(['name' => $name, 'vid' => $vocabulary]);
    if (!empty($terms)) {
      $term = reset($terms);
    }
    else {
      $term = $storage->create(['name' => $name, 'vid' => $vocabulary]);
      $term->save();
    }

    return ['target_id' => $term->id()];
  }

}
