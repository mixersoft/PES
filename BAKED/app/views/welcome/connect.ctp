<div class='one-col'>
    <div>
        <h2>Grab Your Photos</h2>
        <p>
            Before you begin, you'll have to grab your photos and bring them over to Snaphappi. 
            To make it easy we can connect to accounts at many of the most popular photo sharing sites.
            Just click on the icon to begin.
        </p>
        <h3>Connect to your account at:</h3>
        <div id='provider-list' class='center'>
        	<a href='/provider_accounts/view/src:snappi?autocreate=1'><img title='grab your photos from Snaphappi' src='/img/providers/snappi.png'></a>
            <a href='/flickr/connect?auth=1'><img title='grab your photos from flickr' src='/img/providers/flickr.png'></a>
			<img title='grab your photos from picasaweb (not yet implemented)' src='/img/providers/picasaweb-disabled.png'>
			<img title='grab your photos from facebook (not yet implemented)' src='/img/providers/fb-disabled.png'>
        </div><h2>Try our Sample Album</h2>
        <p>
            Or, if you just want a quick demo of our service, feel free to play with photos from our sample photo album.
        </p>
		<br>
        <div class='center'>
            <input type='submit' class="nav-btn" value='Use Sample Album' onclick='location.href="/gallery/sample_album"'>
        </div><h2>Search Online</h2>
        <p>
            You can also search Flickr and grab public photos play with. 
        </p>
		<br>
        <div class='center'>
        	<form id="Flickr/searchForm" style="width:auto" method="get" action='javascript:location.href="/flickr/connect?tags="+document.getElementById("search").value'>
        		<input class='one-text' id='search' name="data[flickr][tags]" type="text" value="" id="flickrTags" size="20"  />
				<input class='nav-btn' type="submit" value="Search Flickr" />
			</form>			
        </div>
        <div class='center'>
        </div>
    </div>
</div>
<?php // echo $layout->getFooterBar(); ?>
