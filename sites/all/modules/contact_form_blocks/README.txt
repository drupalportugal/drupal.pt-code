$Id: README.txt,v 1.1.4.2 2009/06/27 11:45:19 fuerst Exp $

Overview
--------
The Contact form blocks module makes your site-wide contact forms 
available as Drupal Blocks.

Using this module you can show your contact forms at any place
where you can show a Drupal Block. For instance you may add a contact
form to the right sidebar of your website which should be shown at 
every page. Or you want to add another contact form (aka category) 
to a certain node only. Add the contact form block to the content 
region and use the Block visibility settings to hide it from all 
pages but that special one.


Requirements
------------
Drupal's contact module must be enabled.


Installation
------------
Get the module from http://drupal.org/project/contact_form_blocks
Copy the contact_form_blocks directory to the 
module directory of your site and enable it via 
Administer > Site building > Modules ("Other" section).

To modify the width of the input fields you have to add 
something like this to your CSS rules:

div.block-contact_form_blocks input[type=text] {
  width: 95%;
}

Prepend this rule by any CSS selector like div.left-region to 
limit it to the left sidebar for instance.


Usage
-----
Create at least one site-wide contact form category at 
Administer > Site building > Contact form.

Now for every contact form category you get one block at 
Administer > Site building > Blocks. Use the visibility 
settings at the block configuration to show the block at 
one page or some pages only or use other settings there.

If you don't want all Contact form categories to be shown 
in the site wide contact form you can switch them off at 
Administer > Site configuration > Contact blocks.


Author/Maintainer
-----------------
Bernhard Fürst <bernhard.fuerst@fuerstnet.de>
http://fuerstnet.de
