<?php

namespace Drupal\diff;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Diff\Diff;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Xss;

/**
 * Entity comparison service that prepares a diff of a pair of entities.
 */
class DiffEntityComparison {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Wrapper object for writing/reading simple configuration from diff.plugins.yml
   */
  protected $pluginsConfig;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * A list of all the field types from the system and their definitions.
   */
  protected $fieldTypeDefinitions;

  /**
   * The entity parser.
   *
   * @var \Drupal\diff\DiffEntityParser
   */
  protected $entityParser;

  /**
   * The field diff plugin manager service.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Constructs a DiffEntityComparison object.
   *
   * @param ConfigFactory $config_factory
   *   Diff formatter service.
   * @param DiffFormatter $diff_formatter
   *   Diff formatter service.
   * @param PluginManagerInterface $plugin_manager
   *   The Plugin manager service.
   * @param DiffEntityParser $entity_parser
   *   The diff field builder plugin manager.
   * @param DiffBuilderManager $diff_builder_manager
   *   The diff builder manager.
   */
  public function __construct(ConfigFactory $config_factory, DiffFormatter $diff_formatter, PluginManagerInterface $plugin_manager, DiffEntityParser $entity_parser, DiffBuilderManager $diff_builder_manager) {
    $this->configFactory = $config_factory;
    $this->pluginsConfig = $this->configFactory->get('diff.plugins');
    $this->diffFormatter = $diff_formatter;
    $this->fieldTypeDefinitions = $plugin_manager->getDefinitions();
    $this->entityParser = $entity_parser;
    $this->diffBuilderManager = $diff_builder_manager;
  }

  /**
   * This method should return an array of items ready to be compared.
   *
   * @param ContentEntityInterface $left_entity
   *   The left entity.
   * @param ContentEntityInterface $right_entity
   *   The right entity.
   *
   * @return array
   *   Items ready to be compared by the Diff component.
   */
  public function compareRevisions(ContentEntityInterface $left_entity, ContentEntityInterface $right_entity) {
    $result = array();

    $left_values = $this->entityParser->parseEntity($left_entity);
    $right_values = $this->entityParser->parseEntity($right_entity);

    foreach ($left_values as $left_key => $values) {
      list (, $field_key) = explode(':', $left_key);
      // Get the compare settings for this field type.
      $compare_settings = $this->pluginsConfig->get('fields.' . $field_key);
      $result[$left_key] = [
        '#name' => (isset($compare_settings['settings']['show_header']) && $compare_settings['settings']['show_header'] == 0) ? '' : $values['label'],
        '#settings' => $compare_settings,
        '#data' => [],
      ];

      // Fields which exist on the right entity also.
      if (isset($right_values[$left_key])) {
        $result[$left_key]['#data'] += $this->combineFields($left_values[$left_key], $right_values[$left_key]);
        // Unset the field from the right entity so that we know if the right
        // entity has any fields that left entity doesn't have.
        unset($right_values[$left_key]);
      }
      // This field exists only on the left entity.
      else {
        $result[$left_key]['#data'] += $this->combineFields($left_values[$left_key], []);
      }
    }

    // Fields which exist only on the right entity.
    foreach ($right_values as $right_key => $values) {
      list (, $field_key) = explode(':', $right_key);
      $compare_settings = $this->pluginsConfig->get('fields.' . $field_key);
      $result[$right_key] = [
        '#name' => (isset($compare_settings['settings']['show_header']) && $compare_settings['settings']['show_header'] == 0) ? '' : $values['label'],
        '#settings' => $compare_settings,
        '#data' => [],
      ];
      $result[$right_key]['#data'] += $this->combineFields([], $right_values[$right_key]);
    }

    // Field rows. Recurse through all child elements.
    foreach (Element::children($result) as $key) {
      // Ensure that the element follows the #states format.
      if (isset($result[$key]['#data']['#left'])) {
        // We need to trim spaces and new lines from the end of the string
        // otherwise in some cases we have a blank not needed line.
        $result[$key]['#data']['#left'] = trim($result[$key]['#data']['#left']);
      }
      if (isset($result[$key]['#data']['#right'])) {
        $result[$key]['#data']['#right'] = trim($result[$key]['#data']['#right']);
      }
    }

    return $result;
  }

  /**
   * Combine two fields into an array with keys '#left' and '#right'.
   *
   * @param $left_values
   *   Entity field formatted into an array of strings.
   * @param $right_values
   *   Entity field formatted into an array of strings.
   *
   * @return array
   *   Array resulted after combining the left and right values.
   */
  protected function combineFields($left_values, $right_values) {
    $result = array(
      '#left' => array(),
      '#right' => array(),
    );
    $max = max(array(count($left_values), count($right_values)));
    for ($delta = 0; $delta < $max; $delta++) {
      if (isset($left_values[$delta])) {
        $value = $left_values[$delta];
        $result['#left'][] = is_array($value) ? implode("\n", $value) : $value;
      }
      if (isset($right_values[$delta])) {
        $value = $right_values[$delta];
        $result['#right'][] = is_array($value) ? implode("\n", $value) : $value;
      }
    }

    // If a field has multiple values combine them into one single string.
    $result['#left'] = implode("\n", $result['#left']);
    $result['#right'] = implode("\n", $result['#right']);

    return $result;
  }

  /**
   * Prepare the table rows for theme 'table'.
   *
   * @param string $a
   *   The source string to compare from.
   * @param string $b
   *   The target string to compare to.
   * @param boolean $show_header
   *   Display diff context headers. For example, "Line x".
   * @param array $line_stats
   *   This structure tracks line numbers across multiple calls to DiffFormatter.
   *
   * @return array
   *   Array of rows usable with theme('table').
   */
  public function getRows($a, $b, $show_header = FALSE, &$line_stats = NULL) {
    $a = is_array($a) ? $a : explode("\n", $a);
    $b = is_array($b) ? $b : explode("\n", $b);

    // Temporary workaround: when comparing with an empty string, Diff Component
    // returns a change OP instead of an add OP.
    if (count($a) == 1 && $a[0] == "") {
      $a = array();
    }

    if (!isset($line_stats)) {
      $line_stats = array(
        'counter' => array('x' => 0, 'y' => 0),
        'offset' => array('x' => 0, 'y' => 0),
      );
    }

    // Header is the line counter.
    $this->diffFormatter->show_header = $show_header;
    $diff = new Diff($a, $b);

    return $this->diffFormatter->format($diff);
  }

  /**
   * Splits the strings into lines and counts the resulted number of lines.
   *
   * @param $diff
   *   Array of strings.
   */
  public function processStateLine(&$diff) {
    $data = $diff['#data'];
    if (isset($data['#left'])) {
      if (is_string($data['#left'])) {
        $diff['#data']['#left'] = explode("\n", $data['#left']);
      }
      $diff['#data']['#count_left'] = count($diff['#data']['#left']);
    }
    else {
      $diff['#data']['#count_left'] = 0;
    }
    if (isset($data['#right'])) {
      if (is_string($data['#right'])) {
        $diff['#data']['#right'] = explode("\n", $data['#right']);
      }
      $diff['#data']['#count_right'] = count($diff['#data']['#right']);
    }
    else {
      $diff['#data']['#count_right'] = 0;
    }
    // Do an extra trim for removing whitespaces after explode the data.
    foreach ($diff['#data']['#left'] as $key => $value) {
      $diff['#data']['#left'][$key] = trim($diff['#data']['#left'][$key]);
    }
    foreach ($diff['#data']['#right'] as $key => $value) {
      $diff['#data']['#right'][$key] = trim($diff['#data']['#right'][$key]);
    }
  }

  /**
   * Gets the revision description of the revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The current revision.
   * @param $previous_revision_id
   *   The previous revision.
   *
   * @return string
   *   The revision log message.
   */
  public function getRevisionDescription($revision, $previous_revision_id) {
    if ($revision instanceof RevisionLogInterface) {
      $revision_summary = Xss::filter($revision->getRevisionLogMessage());
      if ($revision_summary == '') {
        $revision_summary = $this->summary($revision, $previous_revision_id);
      }
    }
    else {
      $revision_summary = $this->summary($revision, $previous_revision_id);
    }
    return $revision_summary;
  }

  /**
   * Creates an log message based on the changes of entity fields.
   *
   * @param $revision
   *   The current revision.
   * @param $previous_revision_id
   *   The previous revision id.
   *
   * @return string
   *   The revision log message.
   */
  protected function summary($revision, $previous_revision_id) {
    $storage = \Drupal::entityTypeManager()
      ->getStorage($revision->getEntityTypeId());
    $summary = [];
    if ($previous_revision_id) {
      $previous_revision = $storage->loadRevision($previous_revision_id);
      foreach ($previous_revision as $key => $value) {
        if ($previous_revision->get($key)
            ->getValue() != $revision->get($key)->getValue()
          && $this->diffBuilderManager->getSelectedPluginForFieldDefinition($value->getFieldDefinition())
        ) {
          $summary[] = $value->getFieldDefinition()->getLabel();
        }
      }
    }
    if (count($summary) > 0) {
      $summary = 'Changes on: ' . implode(', ', $summary);
    }
    else {
      $summary = 'No changes.';
    }
    return $summary;
  }

}
