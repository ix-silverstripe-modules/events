Internetrix Events Module (WIP)
=======================================

A module for adding a EventPage and CalendarEvent page to your SilverStripe project. Has the ability to create categories and has two types of loading methods.

Soon: ICS Support

Maintainers
------------------
*  Stewart Wilson (<stewart.wilson@internetrix.com.au>)

## Requirements

* SilverStripe 3.1.13 or above

## Dependencies

* silverstripe-modules/VersionedModelAdmin
* silverstripe-modules/listingsummary
* micschk/silverstripe-excludechildren
* tractorcow/silverstripe-colorpicker
* silverstripe/timedropdownfield
* silverstripe-australia/addressable
* silverstripe-australia/grouped-cms-menu

## Notable Features

* Integrates with Listing Summary Module
* Easily enable and disable sharing capabilities
* Two types of pagination available
* This ~~great~~ *WIP* README file!

## Configuration

You can disable certain features in the config.yml of your site.

	Events:
	  event_fields_before: 'Content'
	  enable_sharing: false
	  pagination_type: ajax
	  page_search_type: refine
	  enable_public_add_event: true

### Page Search Type

There are two types of page search types. The "refine" type is for a search box which appears in the sidebar and allows you to refine via start date, end date and category (ex: Strathfield Council). The other type is "" and it will show a search box on the top of the events and allow refinement of start date, end date, category and search query (ex: GTCC)

### Public Event Adding

You can choose to allow the public to add events to the calendar. First set "enable_public_add_event" in the YML as true and then access the page via /events/add. The public will be able to add the event and then you will receive an email about it. You can choose to delete or publish the event in the CMS.

### Ajax Pagination Setup

For Ajax Pagination, you must set the config as below:

	pagination_type: ajax
	
Additionally, your news articles must be contained within a div and your more articles link/button must have a certain class

	<div id="events-container">
		<% if $Events %>
		<% include EventsList %>
		<% end_if %>
	</div>
	
	<% if MoreEvents %>
	<div>
		<a href="$MoreLink" class="show-more">Show More...</a>
    </div>
	<% end_if %>

It is safe to leave both the AJAX and Static pagination template code in as they will only work when activated.

### Static Pagination Setup

For Static Pagination (ie, the next / prev buttons), you must set the config as below:

	pagination_type: static
	
Additionally, you must include the pagination code. It is included at the end of NewsList.ss. It is safe to leave both the AJAX and Static pagination template code in as they will only work when activated.

## Page Extension

You can include the "EventPageExtension" to the desired Page class to enable functionality of showing the event calendar and upcoming events on the page. A template has been provided "CalendarSidebar" to get you started.

	SiteTree:
		extensions:
			- 'EventPageExtension'

A new tab in the CMS will allow you to control the display of the calendar.
