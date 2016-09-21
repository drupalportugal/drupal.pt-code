<?php

namespace Drupal\diff\Form;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\diff\DiffEntityComparison;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a form for revision overview page.
 */
class RevisionOverviewForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * Wrapper object for writing/reading simple configuration from diff.settings.yml
   */
  protected $config;

  /**
   * The field diff layout plugin manager service.
   *
   * @var \Drupal\diff\DiffLayoutManager
   */
  protected $diffLayoutManager;

  /**
   * The diff entity comparison service.
   */
  protected $entityComparison;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatter $date
   *   The date service.
   * @param  \Drupal\Core\Render\RendererInterface
   *   The renderer service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param DiffLayoutManager $diff_layout_manager
   *   DiffLayoutManager service.
   * @param \Drupal\diff\DiffEntityComparison $entity_comparison
   *   The diff entity comparison service.
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $currentUser, DateFormatter $date, RendererInterface $renderer, LanguageManagerInterface $language_manager, DiffLayoutManager $diff_layout_manager, DiffEntityComparison $entity_comparison) {
    $this->entityManager = $entityManager;
    $this->currentUser = $currentUser;
    $this->date = $date;
    $this->renderer = $renderer;
    $this->languageManager = $language_manager;
    $this->config = $this->config('diff.settings');
    $this->diffLayoutManager = $diff_layout_manager;
    $this->entityComparison = $entity_comparison;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('plugin.manager.diff.layout'),
      $container->get('diff.entity_comparison')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revision_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $account = $this->currentUser;
    $langcode = $node->language()->getId();
    $langname = $node->language()->getName();
    $languages = $node->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $node_storage = $this->entityManager->getStorage('node');
    $type = $node->getType();

    $pagerLimit = $this->config->get('general_settings.revision_pager_limit');

    $query = \Drupal::entityQuery('node')
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->pager($pagerLimit)
      ->allRevisions()
      ->sort($node->getEntityType()->getKey('revision'), 'DESC')
      ->execute();
    $vids = array_keys($query);

    $revision_count = count($vids);

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $node->label()]) : $this->t('Revisions for %title', ['%title' => $node->label()]);
    $build['nid'] = array(
      '#type' => 'hidden',
      '#value' => $node->id(),
    );

    $table_header = array(
      'revision' => $this->t('Revision'),
      'summary' => $this->t('Summary'),
      'operations' => $this->t('Operations'),
    );

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $table_header += array(
        'select_column_one' => '',
        'select_column_two' => '',
      );
    }

    $rev_revert_perm = $account->hasPermission("revert $type revisions") ||
      $account->hasPermission('revert all revisions') ||
      $account->hasPermission('administer nodes');
    $rev_delete_perm = $account->hasPermission("delete $type revisions") ||
      $account->hasPermission('delete all revisions') ||
      $account->hasPermission('administer nodes');
    $revert_permission = $rev_revert_perm && $node->access('update');
    $delete_permission = $rev_delete_perm && $node->access('delete');

    // Contains the table listing the revisions.
    $build['node_revisions_table'] = array(
      '#type' => 'table',
      '#header' => $table_header,
      '#attributes' => array('class' => array('diff-revisions')),
    );

    $build['node_revisions_table']['#attached']['library'][] = 'diff/diff.general';
    $build['node_revisions_table']['#attached']['drupalSettings']['diffRevisionRadios'] = $this->config->get('general_settings.radio_behavior');

    $page = \Drupal::request()->query->get('page');
    $latest_revision = empty($page);

    // Add rows to the table.
    foreach ($vids as $key => $vid) {
      if ($revision = $node_storage->loadRevision($vid)) {
        if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $username = array(
            '#theme' => 'username',
            '#account' => $revision->getRevisionAuthor(),
          );
          $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
          // Use revision link to link to revisions that are not active.
          if ($vid != $node->getRevisionId()) {
            $link = $this->l($revision_date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid]));
          }
          else {
            $link = $node->link($revision_date);
          }

          // Default revision.
          if ($latest_revision) {
            $row = array(
              'revision' => array(
                '#type' => 'inline_template',
                '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
                '#context' => [
                  'date' => $link,
                  'username' => $this->renderer->renderPlain($username),
                  'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
                ],
              ),
              'summary' => [
                '#type' => 'markup',
                '#markup' => $this->entityComparison->getRevisionDescription($revision, isset($vids[$key + 1]) ? $vids[$key + 1] : $vids[$key]),
              ],
            );
            // Allow comparisons only if there are 2 or more revisions.
            if ($revision_count > 1) {
              $row += array(
                'select_column_one' => array(
                  '#type' => 'radio',
                  '#title_display' => 'invisible',
                  '#name' => 'radios_left',
                  '#return_value' => $vid,
                  '#default_value' => FALSE,
                ),
                'select_column_two' => array(
                  '#type' => 'radio',
                  '#title_display' => 'invisible',
                  '#name' => 'radios_right',
                  '#default_value' => $vid,
                  '#return_value' => $vid,
                ),
              );
            }
            $row['operations'] = array(
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
              '#attributes' => array(
                'class' => array('revision-current'),
              )
            );
            $latest_revision = FALSE;
          }
          else {
            $route_params = array(
              'node' => $node->id(),
              'node_revision' => $vid,
              'langcode' => $langcode,
            );
            $links = array();
            if ($revert_permission) {
              $links['revert'] = [
                'title' => $this->t('Revert'),
                'url' => $has_translations ?
                  Url::fromRoute('node.revision_revert_translation_confirm', ['node' => $node->id(), 'node_revision' => $vid, 'langcode' => $langcode]) :
                  Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
              ];
            }
            if ($delete_permission) {
              $links['delete'] = array(
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('node.revision_delete_confirm', $route_params)
              );
            }

            // Here we don't have to deal with 'only one revision' case because
            // if there's only one revision it will also be the default one,
            // entering on the first branch of this if else statement.
            $row = array(
              'revision' => array(
                '#type' => 'inline_template',
                '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
                '#context' => [
                  'date' => $link,
                  'username' => $this->renderer->renderPlain($username),
                  'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
                ],
              ),
              'summary' => [
                '#type' => 'markup',
                '#markup' => $this->entityComparison->getRevisionDescription($revision, isset($vids[$key + 1]) ? $vids[$key + 1] : $vids[$key]),
              ],
              'select_column_one' => array(
                '#type' => 'radio',
                '#title_display' => 'invisible',
                '#name' => 'radios_left',
                '#return_value' => $vid,
                '#default_value' => isset ($vids[1]) ? $vids[1] : FALSE,
              ),
              'select_column_two' => array(
                '#type' => 'radio',
                '#title_display' => 'invisible',
                '#name' => 'radios_right',
                '#return_value' => $vid,
                '#default_value' => FALSE,
              ),
              'operations' => array(
                '#type' => 'operations',
                '#links' => $links,
              ),
            );
          }
          // Add the row to the table.
          $build['node_revisions_table'][] = $row;
        }
      }
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $build['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Compare'),
        '#attributes' => array(
          'class' => array(
            'diff-button',
          ),
        ),
      );
    }
    $build['pager'] = array(
      '#type' => 'pager',
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    if (count($form_state->getValue('node_revisions_table')) <= 1) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Multiple revisions are needed for comparison.'));
    }
    elseif (!isset($input['radios_left']) || !isset($input['radios_right'])) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Select two revisions to compare.'));
    }
    elseif ($input['radios_left'] == $input['radios_right']) {
      // @todo Radio-boxes selection resets if there are errors.
      $form_state->setErrorByName('node_revisions_table', $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $nid = $input['nid'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }
    // Builds the redirect Url.
    $redirect_url = Url::fromRoute(
      'diff.revisions_diff',
      array(
        'node' => $nid,
        'left_revision' => $vid_left,
        'right_revision' => $vid_right,
        'filter' => $this->diffLayoutManager->getDefaultLayout(),
      )
    );
    $form_state->setRedirectUrl($redirect_url);
  }

}
