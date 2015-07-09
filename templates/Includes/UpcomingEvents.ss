<% if ShowUpcomingEvents && UpcomingEvents %>
	<div class="event-sidebar">
	    <div class="latestevents-head">All Upcoming Events</div>
	    <div class="events-body">
	        <ul class="events-list">
	        	<% loop UpcomingEvents %>
		            <li>
		                <h3>$MenuTitle</h3>
		                <a href="$Link">$Start.format(M d), $Start.format(Y) | Learn More &raquo;</a>
		            </li>
		        <% end_loop %>
	        </ul>
	    </div>
	</div>
<% end_if %>