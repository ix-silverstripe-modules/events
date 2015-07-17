### Most Important

~~1) Event listing - switch between pagination/ajax~~

~~2) Add the "back to events" functionality just like news~~

~~3) Enable/Disable calendar functionality. Allow calendar to be both in the content/sidebar (an include)
 Hovering over a day in the calendar should show all the events on that day in a qtip.
 Clicking on a day in the calendar should take the user to an event listing page where all the events on that day are shown.~~
 
~~4) Event list should optionally start with the filter fields (i.e Start,End, Content, Category, Keyword) - see gtcc~~

~~5) Add event form. When a member of the public adds an event, that event is added but is in draft mode - steal this from gtcc~~

6) Add plenty of extension hooks.

### Desirable
1) Add an event registration page (extends userform). If a checkbox on the event is ticked, then a button on that event links the user to the registration page (where the event url-segment is in the url) This will assign the registration against that event. Look at the jobs module to see how this is done.

2) Upgrading the calendar functionality so that months directly before and after are loaded in the calendar. If the user clicks to the next month, the month after that is loaded behind the scenes. This should make the loading appear pretty instant.

3) Recurring events module continues to work

4) ICS / Add to Calendar function