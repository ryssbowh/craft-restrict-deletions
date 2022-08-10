# Restrict Element deletions

This plugin can prevent deletion of elements (entries, assets, categories and users) if they are referenced in other elements.

The idea is taken from the plugin [Restrict Asset Delete](https://github.com/la-haute-societe/craft-restrict-asset-delete), improved to apply to all elements, and made Craft 4 ready.  

It's only available for Craft 4.

## Policies

You will need to define policies for each section/volume/category groups and users in the settings, there are 4 policies :

- Do not restrict
- Restrict all : Prevent deletions when the element is referenced in another element (in drafts and revisions also)
- Restrict all but revisions : Prevent deletions when the element is referenced in another element (in drafts also but not in revisions)
- Restrict all but drafts : Prevent deletions when the element is referenced in another element (in revisions also but not in drafts)
- Restrict all but drafts and revisions : Prevent deletions when the element is referenced in another element (not in drafts or revisions)

By default this plugin will not restrict anything.

## Permissions

A permission is added for each section/volume/category groups and users to bypass the restriction and allow deletion anyway.

## Admins

Admin users have all permissions on Craft, but not with this plugin. Admins **will be prevented to delete**, unless allowed specifically in the setting "Admins can skip all deletion policies".

## Known issues

The button delete on an index element page will still be active even if you're not allowed to delete. This is discussed [here](https://github.com/craftcms/cms/issues/11755) and cannot be fixed.  
The deletion is prevented anyway, just the button isn't disabled.