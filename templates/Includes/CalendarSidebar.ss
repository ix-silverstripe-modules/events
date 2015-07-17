<!-- Calendar Sidebar -->
<% if $ShowCalendar %>
<!-- Calendar Sidebar -->
<% include Calendar %>
<% end_if %>

<% if $UpcomingEvents %>
	<h2>Upcoming Events</h2>
	<% loop $UpcomingEvents %>
	<a href="$Link">$Title</a><br />
	<% end_loop %>
<% end_if %>