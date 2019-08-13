<% loop $Events %>
    <% if $Image %>
        <img src="$Image.CroppedImage(237,140).URL" />
    <% end_if %>

    <h6>$Title</h6>
    <p class="date">$Start.format(dd MMM Y)</p>
    <p>$Content.Summary</p>
    <a href="$Link" class="hash-more">More</a>
    <div class="clear"></div>
<% end_loop %>

<div class="clear"></div>

<% if $MoreEvents %>
    <a href="$MoreLink" class="show-more">Show More...</a>
<% end_if %>
