<div class="content-area">
    <div class="row">

        <!-- Main Content Area-->
        <div class="medium-8 small-12 large-9 columns <% if $Menu(2) || $LoadPageBanners %>medium-push-4 large-push-3<% else %>large-centered<% end_if %> main-content">
            <div class="blog blog-single">
                <div style="background-image: url(<% if $LeadingImage %>$LeadingImage.URL<% else %>$ThemeDir/images/placeholder.jpg<% end_if %>);" class="blog-image"></div>
                <div class="blog-info news-listing category-a">
                    <div class="blog-info-head medium-12 column">
                        <h1>$Title</h1>
                    </div>
                    <ul class="inline medium-12 column">
                        <li><i class="fa fa-clock-o"></i>
                        $Start.format(EEEE), <span class="num">$Start.format(MMM d) <% if not $HideStartAndEndTimes %>$Start.format(h:mma)<% end_if %></span>
                        <% if $OneDay %>
                            <% if not $HideStartAndEndTimes %> - $End.format(h:mma)<% end_if %>
                        <% else %>
                             - $End.format(EEEE), <span class="num">$End.format(MMM d) <% if not $HideStartAndEndTimes %>$End.format(h:mma)<% end_if %></span>
                        <% end_if %>
                        </li>
                        <% if $Address %><li><i class="fa fa-map-marker"></i> $Address</li><% end_if %>
                    </ul>

                    $Content

                    <a href="$Link(ics)" class="button">Add to Calendar</a>

                    <% if $Finished %>Thank you, your response has been recorded. <% end_if %>

                    $RegistrationForm

                    <% if $Location || $hasAddress %>
                        <!-- Google Map -->
                        <div class="embed-container">
                            <iframe id="gmap" width="100%" height="250" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://maps.google.com.au/maps?ie=UTF8&amp;q=<% if $Location %>$Location<% else %>$FullAddress<% end_if %>&amp;t=m&amp;z=14&amp;output=embed&amp;iwloc=near"></iframe>
                        </div>
                    <% end_if %>

                    <!-- share/ prev next -->
                    <% if $ShareLinksEnabled %>
                    <div class="social-share container">
                        <div class="medium-8 columns">
                            <h3>Share This Post</h3>
                        </div>
                        <div class="medium-4 columns">
                            <ul class="share-buttons">
                                <li>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=$AbsoluteLink.URLATT" target="_blank">
                                        <i class="fa fa-facebook"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://twitter.com/intent/tweet?source=$BaseHref.URLATT&text=$Title.URLATT:$AbsoluteLink" target="_blank" title="Tweet"><i class="fa fa-twitter"></i></a>
                                </li>
                                <li>
                                    <a href="https://plus.google.com/share?url=$AbsoluteLink.URLATT" target="_blank" title="Share on Google+">
                                        <i class="fa fa-google-plus"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="http://www.linkedin.com/shareArticle?mini=true&url=$AbsoluteLink.URLATT&summary=$Title.URLATT&source=$BaseHref.URLATT" target="_blank" title="Share on LinkedIn">
                                        <i class="fa fa-linkedin"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <% end_if %>

                    <div class="news-nav container">
                        <div class="medium-4 small-6 column">
                            <% if $PrevNextPage(prev) %><a href="$PrevNextPage(prev).Link"><i class="fa fa-chevron-left"></i>  Previous</a><% else %>&nbsp;<% end_if %>
                        </div>
                        <div class="medium-4 small-6 column medium-push-4 text-right">
                            <% if $PrevNextPage %><a href="$PrevNextPage.Link">Next  <i class="fa fa-chevron-right"></i></a><% else %>&nbsp;<% end_if %>
                        </div>
                        <div class="medium-4 small-12 column medium-pull-4 text-center">
                            <a href="$BackLink">Back to Listing</a>
                        </div>
                    </div>

                 </div>
            </div>
        </div><!-- Main Content Area -->

        <% include SideBar %>

    </div> <!--Row end-->
</div> <!--content-area-->
