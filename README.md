Internetrix Events Module (WIP)
=======================================

A module for adding news and news article pages to a site. Adds a NewsHolder and NewsArticle page type.

Provides two extensions for HomePage and Page to allow news articles to be displayed on the HomePage or in the sidebar of a page.

Maintainers
------------------
*  Stewart Wilson (<stewart.wilson@internetrix.com.au>)

## Requirements

* SilverStripe 3.1.13 or above

## Dependencies

* [silverstripe-modules/VersionedModelAdmin](https://gitlab.internetrix.net/silverstripe-modules/versionedmodeladmin) module
* [silverstripe-modules/listingsummary](https://gitlab.internetrix.net/silverstripe-modules/listingsummary) module
* [micschk/silverstripe-excludechildren](https://github.com/micschk/silverstripe-excludechildren) module

## Notable Features

* Integrates with Listing Summary Module
* Easily enable and disable sharing capabilities
* Easily enable and disable archieve page
* Two types of pagination available
* This ~~great~~ *WIP* README file!

## Configuration

You can disable certain features in the config.yml of your site.

	News:
	  enable_sharing: true
	  enable_archive: true
	  pagination_type: ajax

### Enable/Disable Archiving

You can set whether or not achiving is enabled via the configuration file. This is pretty self explanitory. Note: The archive page will 404 when disabled.

### Ajax Pagination Setup

For Ajax Pagination, you must set the config as below:

	pagination_type: ajax
	
Additionally, your news articles must be contained within a div and your more articles link/button must have a certain class

	<div id="news-container">
	<% include NewsList %>
	</div>
	
	<% if MoreNews %>
	<div class="show-more">
		<a href="$MoreLink">Show More...</a>
    </div>
	<% end_if %>

It is safe to leave both the AJAX and Static pagination template code in as they will only work when activated.

### Static Pagination Setup

For Static Pagination (ie, the next / prev buttons), you must set the config as below:

	pagination_type: static
	
Additionally, you must include the pagination code. It is included at the end of NewsList.ss. It is safe to leave both the AJAX and Static pagination template code in as they will only work when activated.

## Extensions

* IRXupdateNewsHolderCMSFields
* IRXupdateNewsHolderChildren
* IRXupdateNewsCMSFields
