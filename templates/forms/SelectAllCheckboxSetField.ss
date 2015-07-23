<ul id="$ID" class="$extraClass">
	<% if $Options.Count %>
		<% loop $Options %>
			<li class="$Class">
				<input id="$ID" class="checkbox" name="$Name" type="checkbox" value="$Value"<% if $isChecked %> checked="checked"<% end_if %><% if $isDisabled %> disabled="disabled"<% end_if %> />
				<label for="$ID">$Title</label>
			</li> 
		<% end_loop %>
	<% else %>
		<li>No options available</li>
	<% end_if %>
</ul>
<% if $Options.Count %>
	<span class="selections"><a class="select-all" href="#" >select all</a> / <a class="deselect-all" href="#">deselect all</a></span>
<% end_if %>

