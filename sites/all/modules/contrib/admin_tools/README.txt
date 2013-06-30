INTRODUCTION
------------
Admin Tools is an addon module for the Admin module, which provides a sidebar
navigation for selected roles.
The Admin Tools module adds functionality and quick access for clearing caches,
running cron and updates much like Admin Menu. Additionally, administrators can
add their own custom links via hook_admin_tools_links().

INSTALLATION
------------
- Download Admin Tools module
- Enable Admin Tools module at admin/modules
- Show Admin Tools "block" in the Admin sidebar
    (admin/config/user-interface/admin) if it doesn't show up automatically.

CUSTOM MENU LINKS
-----------------
You can create custom menu links at admin/config/user-interface/admin/tools.
  - Title: This is the menu link title.
  - URL: This is the internal or external menu link
  - Weight: This is the weight of the menu link. Higher values will appear lower
      in the list of links.
  - Permission: If this option is checked, then Admin Tools will create a
      permission for this menu item so that you can choose which role(s) can
      view the menu link with the Admin Tools menu. If this option is left
      unchecked then any role that can access the Admin Tools menu will see the
      link.
      NOTE: If the role doesn't have access to the path, the link will still not
      be displayed. Also, this permission doesn't prevent a user of a role to
      access the link, it only controls whether or not the link appears in the
      Admin Tools menu.
  - Redirect: If this option is checked, the user will be sent back to the
      previous page when clicking on form or when an automatic process
      (i.e. running a clear cache function) resubmits the page.

API
---
hook_admin_tools_links()

Define custom links to show in the Admin Tools menu
** Refer to admin_tools_admin_tools_links() in admin_tools.module

Format:
  $links = array(
    '[machine_name]' => array( 
      'title'      => '[title]', (REQUIRED)
      'href'       => '[path_to_link_to]', (REQUIRED)
      'permission' => '[permission segment]',
      'query'      => drupal_get_destination(),
      'weight'     => [weight],
    ),
  );
  return $links;

Notes:
- title and href are the only two REQUIRED elements.
- The machine name must be unique. See admin_tools_admin_tools_links() in
    admin_tools.module for machine names already in use by the core Admin Tools
    module.
- Permission: If this parameter is set, Admin Tools will provide the
    administrator with the option to set which role(s) this link will show up
    for in the Admin Tools menu.
- Query: If set, this is where the user will be redirected to upon page reload.
    The most common use for this is to redirect the user back to the page they
    were on when clearing caches.
- Weight: Setting the weight allows the link to be placed in any spot within the
     Admin Tools menu.

There is a link provided by Admin Tools in case a function you want to execute
doesn't have a menu link attached to it.
Use admin/admin-tools/function/[function_name] which will execute the function.
