<?php

namespace Drupal\diff\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\diff\EntityComparisonBase;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Language\LanguageInterface;

class GenericRevisionController extends EntityComparisonBase {

  /**
   * Get all the revision ids of given entity id.
   *
   * @param $storage
   *   The entity storage manager.
   * @param $entity_id
   *   The entity to find revisions of.
   *
   * @return array
   */
  protected function getVids(EntityStorageInterface $storage, $entity_id) {
    $result = $storage->getQuery()
      ->allRevisions()
      ->condition($storage->getEntityType()->getKey('id'), $entity_id)
      ->execute();
    $result_array = array_keys($result);
    sort($result_array);
    return $result_array;
  }

  /**
   * Returns a table which shows the differences between two entity revisions.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   The left revision
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   The right revision.
   * @param string $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If filter == 'raw-plain' markdown function is applied to the text before comparison.
   *
   * @return array
   *   Table showing the diff between the two entity revisions.
   */
  public function compareEntityRevisions(RouteMatchInterface $route_match, EntityInterface $left_revision, EntityInterface $right_revision, $filter) {
    $entity_type_id = $left_revision->getEntityTypeId();
    $entity = $route_match->getParameter($entity_type_id);
    $diff_rows = array();
    $build = array(
      '#title' => $this->t('Revisions for %title', array('%title' => $entity->label())),
    );
    if (!in_array($filter, array('raw', 'raw-plain'))) {
      $filter = 'raw';
    }
    elseif ($filter == 'raw-plain') {
      $filter = 'raw_plain';
    }

    $entity_type_id = $entity->getEntityTypeId();
    $storage = $this->entityTypeManager()->getStorage($entity_type_id);

    // Get language from the entity context.
    $langcode = $entity->language()->getId();

    // Get left and right revision in current language.
    $left_revision = $left_revision->getTranslation($langcode);
    $right_revision = $right_revision->getTranslation($langcode);

    $vids = [];
    // Filter revisions of current translation and where the translation is
    // affected.
    foreach ($this->getVids($storage, $entity->id()) as $vid) {
      $revision = $storage->loadRevision($vid);
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $vids[] = $vid;
      }
    }
    $diff_rows[] = $this->buildRevisionsNavigation($entity, $vids, $left_revision->getRevisionId(), $right_revision->getRevisionId());
    $diff_rows[] = $this->buildMarkdownNavigation($entity, $left_revision->getRevisionId(), $right_revision->getRevisionId(), $filter);
    $diff_header = $this->buildTableHeader($left_revision, $right_revision);

    // Perform comparison only if both entity revisions loaded successfully.
    if ($left_revision != FALSE && $right_revision != FALSE) {
      $fields = $this->compareRevisions($left_revision, $right_revision);
      $entity_base_fields = $this->entityManager()->getBaseFieldDefinitions($entity_type_id);
      // Check to see if we need to display certain fields or not based on
      // selected view mode display settings.
      foreach ($fields as $field_name => $field) {
        // If we are dealing with entities only compare those fields
        // set as visible from the selected view mode.
        $view_mode = $this->config->get('content_type_settings.' . $entity->bundle() . '.view_mode');
        // If no view mode is selected use the default view mode.
        if ($view_mode == NULL) {
          $view_mode = 'default';
        }
        list(, $field_machine_name) = explode('.', $field_name);
        $visible = entity_get_display($entity_type_id, $entity->bundle(), $view_mode)->getComponent($field_machine_name);
        if ($visible == NULL && !array_key_exists($field_name, $entity_base_fields)) {
          unset($fields[$field_name]);
        }
      }
      // Build the diff rows for each field and append the field rows
      // to the table rows.
      foreach ($fields as $field) {
        $field_label_row = '';
        if (!empty($field['#name'])) {
          $field_label_row = array(
            'data' => $this->t('Changes to %name', array('%name' => $field['#name'])),
            'colspan' => 4,
            'class' => array('field-name'),
          );
        }
        $field_diff_rows = $this->getRows(
          $field['#states'][$filter]['#left'],
          $field['#states'][$filter]['#right']
        );

        // Add the field label to the table only if there are changes to that field.
        if (!empty($field_diff_rows) && !empty($field_label_row)) {
          $diff_rows[] = array($field_label_row);
        }

        // Add field diff rows to the table rows.
        $diff_rows = array_merge($diff_rows, $field_diff_rows);
      }

      // Add the CSS for the diff.
      $build['#attached']['library'][] = 'diff/diff.general';
      $theme = $this->config->get('general_settings.theme');
      if ($theme) {
        if ($theme == 'default') {
          $build['#attached']['library'][] = 'diff/diff.default';
        }
        elseif ($theme == 'github') {
          $build['#attached']['library'][] = 'diff/diff.github';
        }
      }
      // If the setting could not be loaded or is missing use the default theme.
      elseif ($theme == NULL) {
        $build['#attached']['library'][] = 'diff/diff.github';
      }

      $build['diff'] = array(
        '#type' => 'table',
        '#header' => $diff_header,
        '#rows' => $diff_rows,
        '#empty' => $this->t('No visible changes'),
        '#attributes' => array(
          'class' => array('diff'),
        ),
      );

      if ($entity->hasLinkTemplate('version-history')) {
        $build['back'] = array(
          '#type' => 'link',
          '#attributes' => array(
            'class' => array(
              'button',
              'diff-button',
            ),
          ),
          '#title' => $this->t('Back to Revision Overview'),
          '#url' => Url::fromRoute("entity.$entity_type_id.version_history", [$entity_type_id => $entity->id()]),
        );
      }

      return $build;
    }
    else {
      // @todo When task 'Convert drupal_set_message() to a service' (2278383)
      //   will be merged use the corresponding service instead.
      drupal_set_message($this->t('Selected @label revisions could not be loaded.', ['@label' => $entity->getEntityType()->getLabel()]), 'error');
    }
  }

  /**
   * Build the header for the diff table.
   *
   * @param \Drupal\Core\Entity\EntityInterface $left_revision
   *   Revision from the left hand side.
   * @param \Drupal\Core\Entity\EntityInterface $right_revision
   *   Revision from the right hand side.
   *
   * @return array
   *   Header for Diff table.
   */
  protected function buildTableHeader(EntityInterface $left_revision, EntityInterface $right_revision) {
    $entity_type_id = $left_revision->getEntityTypeId();
    $revisions = array($left_revision, $right_revision);
    $header = array();

    foreach ($revisions as $revision) {
      if ($revision instanceof EntityRevisionLogInterface || $revision instanceof NodeInterface) {
        $revision_log = $this->nonBreakingSpace;

        if ($revision instanceof EntityRevisionLogInterface) {
          $revision_log = Xss::filter($revision->getRevisionLogMessage());
        }
        elseif ($revision instanceof NodeInterface) {
          $revision_log = $revision->revision_log->value;
        }
        $username = array(
          '#theme' => 'username',
          '#account' => $revision->uid->entity,
        );
        $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
        $revision_link = $this->t($revision_log . '@date', array(
            '@date' => $this->l($revision_date, Url::fromRoute("entity.$entity_type_id.revision", array(
              $entity_type_id => $revision->id(),
              $entity_type_id . '_revision' => $revision->getRevisionId(),
          ))),
        ));
      }
      else {
        $revision_link = $this->l($revision->label(), $revision->toUrl('revision'));
      }

      // @todo When theming think about where in the table to integrate this
      //   link to the revision user. There is some issue about multi-line headers
      //   for theme table.
      // $header[] = array(
      //   'data' => $this->t('by' . '!username', array('!username' => drupal_render($username))),
      //   'colspan' => 1,
      // );
      $header[] = array(
        'data' => array('#markup' => $this->nonBreakingSpace),
        'colspan' => 1,
      );
      $header[] = array(
        'data' => array('#markup' => $revision_link),
        'colspan' => 1,
      );
    }

    return $header;
  }

  /**
   * Returns the navigation row for diff table.
   */
  protected function buildRevisionsNavigation(EntityInterface $entity, $vids, $left_vid, $right_vid) {
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $revisions_count = count($vids);
    $i = 0;

    $row = array();
    // Find the previous revision.
    while ($left_vid > $vids[$i]) {
      $i += 1;
    }
    if ($i != 0) {
      // Second column.
      $row[] = array(
        'data' => $this->l(
          $this->t('< Previous difference'),
          Url::fromRoute("entity.$entity_type_id.revisions_diff",
            array(
              $entity_type_id => $entity_id,
              'left_revision' => $vids[$i - 1],
              'right_revision' => $left_vid,
            ))
        ),
        'colspan' => 2,
        'class' => 'rev-navigation',
      );
    }
    else {
      // Second column.
      $row[] = $this->nonBreakingSpace;
    }
    // Third column.
    $row[] = $this->nonBreakingSpace;
    // Find the next revision.
    $i = 0;
    while ($i < $revisions_count && $right_vid >= $vids[$i]) {
      $i += 1;
    }
    if ($revisions_count != $i && $vids[$i - 1] != $vids[$revisions_count - 1]) {
      // Forth column.
      $row[] = array(
        'data' => $this->l(
          $this->t('Next difference >'),
          Url::fromRoute("entity.$entity_type_id.revisions_diff",
            array(
              $entity_type_id => $entity_id,
              'left_revision' => $right_vid,
              'right_revision' => $vids[$i],
            ))
        ),
        'colspan' => 2,
        'class' => 'rev-navigation',
      );
    }
    else {
      // Forth column.
      $row[] = $this->nonBreakingSpace;
    }

    // If there are only 2 revision return an empty row.
    if ($revisions_count == 2) {
      return array();
    }
    else {
      return $row;
    }
  }

  /**
   * Builds a table row with navigation between raw and raw-plain formats.
   */
  protected function buildMarkdownNavigation(EntityInterface $entity, $left_vid, $right_vid, $active_filter) {
    $entity_type_id = $entity->getEntityTypeId();

    $links['raw'] = array(
      'title' => $this->t('Standard'),
      'url' => Url::fromRoute("entity.$entity_type_id.revisions_diff", array(
        $entity_type_id => $entity->id(),
        'left_revision' => $left_vid,
        'right_revision' => $right_vid,
      )),
    );
    $links['raw_plain'] = array(
      'title' => $this->t('Markdown'),
      'url' => Url::fromRoute("entity.$entity_type_id.revisions_diff", array(
        $entity_type_id => $entity->id(),
        'left_revision' => $left_vid,
        'right_revision' => $right_vid,
        'filter' => 'raw-plain',
      )),
    );

    // Set as the first element the current filter.
    $filter = $links[$active_filter];
    unset($links[$active_filter]);
    array_unshift($links, $filter);

    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
      'colspan' => 4,
    );

    return $row;
  }
}
