<?php

namespace Drupal\tv_maze_migrate\Plugin\migrate\process;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileRepositoryInterface;

/**
 * Downloads a remote image URL and saves it as a Drupal managed file.
 *
 * Returns the managed file entity ID (fid) suitable for image field target_id.
 *
 * Usage:
 * @code
 * field_show_image/target_id:
 *   plugin: tvmaze_image_download
 *   source: image_url
 *   destination: 'public://tvmaze/shows'
 * @endcode
 *
 * @MigrateProcess("tvmaze_image_download")
 */
#[MigrateProcess('tvmaze_image_download')]
class TvMazeImageDownload extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FileSystemInterface $fileSystem,
    protected FileRepositoryInterface $fileRepository,
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
      $container->get('file_system'),
      $container->get('file.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): mixed {
    if (empty($value)) {
      return NULL;
    }

    $destination = $this->configuration['destination'] ?? 'public://tvmaze';
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $filename = basename(parse_url($value, PHP_URL_PATH));
    $destination_uri = rtrim($destination, '/') . '/' . $filename;

    // Return existing managed file if already downloaded.
    $existing = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $destination_uri]);
    if (!empty($existing)) {
      return reset($existing)->id();
    }

    // Download the remote file.
    $data = @file_get_contents($value);
    if ($data === FALSE) {
      $migrate_executable->saveMessage("Failed to download image: $value", 2);
      return NULL;
    }

    $file = $this->fileRepository->writeData($data, $destination_uri, FileExists::Replace);
    return $file ? $file->id() : NULL;
  }

}
