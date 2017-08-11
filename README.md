# WP-SUBS

## Integration of Troly with WordPress and Woocommerce

See full documentation:
[Release 1](https://docs.google.com/document/d/1kOcorMqAPXH6b533y2ybN_MczkspUVIQjmpCHbxqvJQ/)

## Structure and Important Functions

lib/class-wp99234-woocommerce.php

`filter_get_price` - Look up the price of a product in the cart against the current user

lib/class-wp99234-products.php

`import_product` - Import a single product

## Validating your syntax
Before committing, make sure you have run a final validation of your files' syntax! This can be achieved thusly:

1. Open a Terminal window and navigate to the root of your wp-99234 workspace.
2. Run the following command:
			for f in $(find . -name "*.php"); do php -l $f 1>/dev/null; done
			
Errors will be shown; no output means all files are correct in their PHP syntax

## Release Process

See here: https://docs.google.com/document/d/1sO-AomjGz-3fQJ99f1Ymk-I6ezpNMXW7eV_IoJ_UUNM/edit for full release documentation (below is a quick version) 

Wordpress plugins are most easily distributed through the Wordpress plugin directory. This plugin is no exception. 

The Wordpress directory is based on Subversion (svn) rather than git. The release process must take this into account. Here are the steps to take: 

1. Make your changes in their own branch (branching off master)
2. Once your changes are complete, tested, and ready for release, open your P.R in Github, against master. 
3. After merging, copy the contents of the Master branch into the trunk folder of the subversion repository (locally). 
4. Tag this version. To do so, enter the following: 

		svn cp trunk tags/2.1
		
	(replace 2.1 with the actual version of the plugin you are tagging). 
	
At this point, the stable version remains unchanged. The stable version is the one Wordpress lets users download on the plugins page.

5. After testing the tagged version in a live environment, update the readme.txt file in the git repository. Changes need to include: 

	5.1. Updated stable tag, to point to the latest version
	
	5.2. Updated `Changelog`	
	
	5.3. Any new feature should be explained where appropriate
	
	5.4. In the plugin's main file (`wp99234.php`), make sure to change the version number to match the tag's version.
	
6. Push your git changes to the remote repo, then copy the entire folder in the subversion trunk folder. Only 2 files should be changed (wp99234.php and readme.txt)
7. **Run the following command (this will push to Wordpress, be careful!)**:

		svn ci -m "relevant commit message"

8. Changes should be pushed and made available within a few minutes (the Wordpress repository updates every 10 minutes or so). 

### To add a new field from the response to be stored against a product
1. Add new field to `product_meta_map` in `lib/class-wp99234-company.php`
2. Add new field to `export_product` in `lib/class-wp99234-company.php`
3. Trigger a re-import of all products for the company so the new field is populated


### Important note
The version number of the plugin is always the one of the `wp99234.php` file contained in the current Stable tag folder. If you want Wordpress to notify users that a new version is available, you must update the version number in `wp99234.php` (not just `readme.txt`)
