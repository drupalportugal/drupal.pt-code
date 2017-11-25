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
