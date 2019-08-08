<% if not $HideSearchBox %>
<% if $filterSearchEnabled %>
<div class="events-search">
    <form action="$SearchEventsFormAction" id="filter-search" method="GET">
        <div class="row">
            <div class="large-4 medium-6 columns">
                $StartDateField.SmallFieldHolder
            </div>
            <div class="large-4 medium-6 columns">
                $EndDateField.SmallFieldHolder
            </div>
            <div class="large-4 medium-12 columns">
                $CategoriesField.FieldHolder
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
<% end_if %>
