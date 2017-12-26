INTRODUCTION
------------

The Commerce License allows the creation of products that sell access to some
aspect of the site. This could be a role, publication of a node, and so on.

This access is controlled by a License entity, which is created for the user
when the product is purchased.

The nature of what a License entity grants is handled by License type plugins.
Each License entity will have one License type plugin associated with it.

A product variation that sells a License will have a configured License type
plugin field value. This acts as template to create the License when a user
purchases that product variation.


REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (https://drupal.org/project/commerce)
 * Recurring Period (https://drupal.org/project/recurring_period)

The following patches are recommended:

 * https://www.drupal.org/project/commerce/issues/2930979: Don't show the
  'added to your cart' message if the item quantity is unchanged.

INSTALLATION
------------

 1 Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
 2 Configure or create a checkout flow which does not allow anonymous checkout.
 3 Configure or create an Order Type to use the checkout flow.
 4 Configure or create an Order Item Type to use the Order Type, and work with
   Licenses.
 5 Configure or create a Product Variation Type to use the Order Item Type, and
   provide Licenses.

KNOWN ISSUES AND LIMITATIONS
----------------------------

This module is still incomplete, and has the following limitations:

- The admin forms to create/edit licenses are not yet complete. They should only
  be used by developers who know what they are doing. Changing values here can
  break things!
