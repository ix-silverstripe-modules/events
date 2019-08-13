<div id="month-navigator" class="calendar-title ui-datepicker-header">
    <a class="calendar-prev ui-datepicker-prev" href="$PrevLink"></a>
    <a class="calendar-next ui-datepicker-next" href="$NextLink"></a>
    <div class="ui-datepicker-title">$MonthName $Year</div>
</div>

<table class="ui-datepicker-calendar">
    <tr class="days">
        <th class="days">Mon</th>
        <th class="days">Tue</th>
        <th class="days">Wed</th>
        <th class="days">Thu</th>
        <th class="days">Fri</th>
        <th class="days">Sat</th>
        <th class="days">Sun</th>
    </tr>

    <% loop $Weeks %>
        <tr>
            <% loop $Me %>
                <td class="$Num dates
                            <% if $InMonth %><% else %>unhighlighted<% end_if %>
                            <% if $Past %>past<% end_if %>
                            <% if $Today %>today<% end_if %>
                            <% if $HasEvent %>has-event<% end_if %>
                            <% if $Selected %>selected<% end_if %>">
                    <% if $InMonth %><a href="$Link" data-title="$Title"><% end_if %>
                        <% if $HasEvent %>
                            <div class="event-markers">
                                <% loop $Colours %>
                                    <div class="em-1" style="background-color: #{$Colour}"></div>
                                <% end_loop %>
                            </div>
                        <% end_if %>
                        $Num

                    <% if $InMonth %></a><% end_if %>
                </td>
            <% end_loop %>
        </tr>
    <% end_loop %>
</table>
