<form action="$SearchEventsFormAction" method="GET" id="refine-events">
<!-- Events Filter -->
<div class="refineoptions">
    <div class="title">Refine by:</div>

	<!-- Type -->
    <div class="sec-title">Type</div>
    <div class="field">
        <input type="checkbox" id="all-types" />
        <label for="all-types"><span></span>All Types</label>
    </div>
    <% loop EventsCategories %>
    	<% if $NumberOfUpcomingEvents %>
            <div class="field type">
            	<input type="checkbox" id="$URLSegment" value="$URLSegment" <% if $IsChecked %>checked="checked"<% end_if %>  />
                <label for="$URLSegment"><span></span>$Title</label>
            </div>
    	<% end_if %>
    <% end_loop %>
    
    <input type="hidden" id="types" name="types" value="" />

    <!-- Dates -->
    <div class="sec-title">Dates</div>
    $StartDateField
    $EndDateField
    
    <div class="field go">
    	<input type="submit" value="Filter/Search" />
    </div>
</form>