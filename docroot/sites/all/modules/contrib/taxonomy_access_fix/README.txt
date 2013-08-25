
Taxonomy access fix
====

This module

* adds 1 permission per vocabulary: "add terms in X"
* changes the way vocabulary specific permissions are handled
* changes the Taxonomy admin pages' access checks
* alters the vocabularies overview table to show only what you have access to edit or delete

The module does what native Taxonomy lacks: more specific Taxonomy permissions (and checking them correctly).

*Note*: In order to access the admin/structure/taxonomy page, you must first set permissions for the desired vocabularies.

*Note*: A module can't add permissions to another module, so the extra "add terms in X" permissions are located under "Taxonomy access fix" and not under "Taxonomy".
