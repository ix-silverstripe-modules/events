<% if not $HideSearchBox %>
<div class="events-search">
	<form action="$SearchEventsFormAction" method="GET">
    	<div class="row">
            <div class="large-4 medium-6 columns">
                $StartDateField
            </div>
            <div class="large-4 medium-6 columns">
            	$EndDateField
            </div>
            <div class="large-4 medium-12 columns">
                $CategoriesField
            </div>
            <div class="large-8 medium-12 columns">
        		$searchQueryField
            </div>
        	<div class="large-4 medium-12 columns">
            	<input type="submit" value="Filter/Search" />
            </div>
        </div>
  	</form>
</div>
<% end_if %>