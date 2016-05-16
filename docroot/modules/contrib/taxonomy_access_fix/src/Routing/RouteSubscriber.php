<?php

namespace Drupal\taxonomy_access_fix\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * New and improved hook_menu_alter().
   */
  public function alterRoutes(RouteCollection $collection) {
    // admin/structure/taxonomy
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      $route->setRequirements(array(
        '_custom_access' => '\taxonomy_access_fix_route_access',
      ));
      $route->setOption('op', 'index');
    }

    // admin/structure/taxonomy/%vocabulary
    if ($route = $collection->get('entity.taxonomy_vocabulary.overview_form')) {
      $route->setRequirements(array(
        '_custom_access' => '\taxonomy_access_fix_route_access',
      ));
      $route->setOption('op', 'list terms');
    }

    // admin/structure/taxonomy/%vocabulary/add
    if ($route = $collection->get('entity.taxonomy_term.add_form')) {
      $route->setRequirements(array(
        '_custom_access' => '\taxonomy_access_fix_route_access',
      ));
      $route->setOption('op', 'add terms');
    }
  }

}
