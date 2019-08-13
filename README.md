Internetrix Events Module (WIP)
=======================================

A module for adding a EventPage and CalendarEvent page to your SilverStripe project. Has the ability to create categories and has two types of loading methods.

[TO DO List](TODO.md)

Maintainers
------------------
*  Stewart Wilson (<stewart.wilson@internetrix.com.au>)

## Requirements

* SilverStripe 4.4.0 or above

## Dependencies

* silverstripe-modules/listingsummary
* silverstripe/lumberjack
* tractorcow/silverstripe-colorpicker
* symbiote/silverstripe-addressable
* symbiote/silverstripe-gridfieldextensions
* symbiote/silverstripe-grouped-cms-menu
* userforms

## Notable Features

* Integrates with Listing Summary Module
* Easily enable and disable sharing capabilities
* Two types of pagination available

## Configuration

You can disable certain features in the config.yml of your site.

    Internetrix\Events\Pages\EventsPage:
      event_fields_before: 'Content'
      enable_sharing: false
      pagination_type: ajax
      page_search_type: refine
      enable_public_add_event: true

### Page Search Type

There are two types of page search types. The "refine" type is for a search box which appears in the sidebar and allows you to refine via start date, end date and category (ex: Strathfield Council). The other type is "filter" and it will show a search box on the top of the events and allow refinement of start date, end date, category and search query (ex: GTCC)

    Op 1: page_search_type: refine
    Op 2: page_search_type: filter

### Public Event Adding

You can choose to allow the public to add events to the calendar. First set "enable_public_add_event" in the YML as true and then access the page via /events/add. The public will be able to add the event and then you will receive an email about it. You can choose to delete or publish the event in the CMS.

This page will source the fields from the DataObject. So any extensions that modify the CMSFields will also show. Additionally, fields such as Submitter Name etc are added in at the end. You can modify what fields are shown or not by modifying the static variable $block_frontend_fields in CalendarEvent. Obvious fields like Navigation Label and Metadata are removed.

### Ajax Pagination Setup

For Ajax Pagination, you must set the config as below:

    Internetrix\Events\Pages\EventsPage:
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

    Internetrix\Events\Pages\EventsPage:
      pagination_type: static

Additionally, you must include the pagination code. It is included at the end of NewsList.ss. It is safe to leave both the AJAX and Static pagination template code in as they will only work when activated.

### Event Registration Page

Something here.

    Internetrix\Events\Pages\EventsPage:
      required_event_fields:
        FullName:
          Title: 'Full Name'
          Type: SilverStripe\UserForms\Model\EditableFormField\EditableTextField
        EmailAddress:
          Title: 'Email Address'
          Type: SilverStripe\UserForms\Model\EditableFormField\EditableEmailField
        ContactNumber:
          Title: 'Contact Number'
          Type: SilverStripe\UserForms\Model\EditableFormField\EditableNumericField

### Page Extension

You can include the "EventPageExtension" to the desired Page class to enable functionality of showing the event calendar and upcoming events on the page. A template has been provided "CalendarSidebar" to get you started.

    Page:
      extensions:
        - Internetrix\Events\Extensions\EventPageExtension
    PageController:
      extensions:
        - Internetrix\Events\Extensions\EventPageExtensionController

A new tab in the CMS will allow you to control the display of the calendar. Be sure to include the template in your template file.
    <% include Internetrix/Events/Includes/CalendarSidebar %>

### Other Extensions

Plenty are provided throughout the module. Including (but not limited to):

* updateEventAddFields
* updateEventAddActions
* updateEventAddValidator
* updateEventsCategories
* updateEventsPageChildren
* updateEventCMSFields
