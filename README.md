# Restrict Element deletions

This plugin can prevent deletion of elements (entries, assets, categories and users) if they are referenced in other elements.

The idea is taken from the plugin [Restrict Asset Delete](https://github.com/la-haute-societe/craft-restrict-asset-delete), improved to apply to all elements, and made Craft 4 ready.  

## Installation

`composer require ryssbowh/craft-restrict-deletions:^1.0` for Craft 3  
`composer require ryssbowh/craft-restrict-deletions:^2.0` for Craft 4

## Policies

You will need to define policies for each section/volume/category groups and users in the settings, there are 4 policies :

- Do not restrict
- Restrict all : Prevent deletions when the element is referenced in another element, in drafts and revisions also
- Restrict all but revisions : Same as above but not in revisions
- Restrict all but drafts : Same as above but not in drafts
- Restrict all but drafts and revisions : Same as above but not in drafts or revisions

By default this plugin will not restrict anything.

## Permissions

A permission is added for each section/volume/category groups/product types and users to bypass the restriction and allow deletion anyway.

## Admins

Admin users have all permissions on Craft, but not with this plugin. Admins **will be prevented to delete**, unless allowed specifically in the setting "Admins can skip all deletion policies".

## Known issues

In Craft 4 the button delete on an index element page will still be active even if you're not allowed to delete. This is discussed [here](https://github.com/craftcms/cms/issues/11755) and cannot be fixed at the moment.  
The deletion is prevented anyway, just the button isn't disabled.