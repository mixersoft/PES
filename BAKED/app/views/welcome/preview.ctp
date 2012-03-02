<style type="text/css">
#body-container.plain {
	padding: 10px 0 !important;
}
</style>
<?php 
switch (env('SERVER_NAME')) {
	case 'preview.snaphappi.com':
	case 'dev.snaphappi.com':
		$preview_badge_src = '/svc/STAGING/stage6/.thumbs/sq~923BB005-BA32-48A5-A2A3-F72A24DEDF8A.jpg';
		break; 
	case 'git3':
	default:
		$preview_badge_src = '/svc/STAGING/stage7/.thumbs/sq~C6E72021-9BBA-4F90-9548-217180605066.jpg';
		break;
}
	
$this->Layout->blockStart('itemHeader'); ?>
<section class="item-header container_16" id="aui_3_3_0_2424">
	<div class="wrap">
		<ul class="inline grid_14">
			<li class="thumbnail sq">
				<a href='/person/home/4f279575-29bc-4c87-9d86-094b0afc480d'>
				<img width="50" height="50" alt="" src="<?php echo $preview_badge_src; ?>">
				</a></li>
			<li>
				<div class="item-class">A few words from our founder</div>
				<h1 class="label">Michael</h1>
			</li>
		</ul>
	</div>
</section>	
<?php	$this->Layout->blockEnd(); ?>


<section class='welcome-preview prefix_1 grid_14 suffix_1'>
	<div class='alpha grid_14 omega'>	
		
<div class="hint message blue rounded-5 cf">	
	<h2 class="alpha">Welcome to our Friends and Family Preview</h2>
	<p>We've put a lot of time and effort developing our vision for Snaphappi, and this site. 
		And while it is not yet &ldquo;done&rdquo;, it is ready to share with our <b>Friends and Family</b> 
		<img src="/static/img/css-gui/smiley.gif">.
	</p>
	<p>Our vision is to build a service that delivers surprise and delight from your rated photos.
		We know that rating the 10,000+ photos on your hard drive is tedious <img src="/static/img/css-gui/frownie.gif">, 
		and we plan to offer an elegant solution. But for now, you'll have to do it by hand. 
		This preview is only the tip of our iceberg.
		</p>
	
	<p>If you follow the Yellow Brick Road things should be fine, 
		but if you wander off you may notice some things have gone awry. 
		</p>
	<p>Thanks for your support and understanding, Michael.</p>
	<div class='right cf'>
		<article class="FigureBox Photo">
	    	<figure>
				<img class='' src='/static/img/hints/yellow-brick-road.jpg'>	    		
				<figcaption>
	    		 <div class="label"><b>Follow the Yellow Brick Road...</b></div>
	    		 </figcaption>
			</figure>
		</article>
		</div>
	<ul><b>The Yellow Brick Road:</b> 
		<li>Browse Snaps and Circles at Snaphappi</li>
		<li>Sign-up and manage your account at Snaphappi</li>
		<li>Upload 1 or 1000+ photos from your PC or Mac</li>
		<li>Orangize your photos by adding Ratings <div class="ratingGroup" style="background-position: -28px bottom;display:inline-block;"></div>, Tags, and Bestshots (i.e. hiding duplicates)</li>
		<li>Join or create Circles; share Snaps; and invite your friends to do the same</li>
		<li>Create wonderful Stories to share with your friends <img class='rounded-5 button' src="/static/img/hints/create.jpg"></li>
		<li>Determine who can see your photos through privacy and sharing</li>
		<li>Report a problem or post a question or comment in the Help section. <img class='rounded-5 button' src="/static/img/hints/help.jpg"></li>		
	</ul>
	<br />	
	<div>
		<button class="continue orange" type="submit"'>Continue to Snaphappi</button>
		<span class="input checkbox">&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="WelcomePreviewSkip" name="data[Profile][welcome_preview_skip]"><label for="WelcomePreviewSkip"> Skip this page</label>
			</span>
	</div>	
	
</div>

	</div>
</section>


<?php $this->Layout->blockStart('javascript'); ?>
<script type="text/javascript">
	namespace('SNAPPI.onYready');
	var _Y = null;
	SNAPPI.onYready.preview = function(Y){
		if (_Y === null) _Y = Y;
		var skip;
		_Y.on('click', function(e){
			skip = _Y.one('input#WelcomePreviewSkip:checked');
			if (skip) {
				_Y.Cookie.setSub('donotshow', 'welcome-preview', 1, {
					// path: 'preview.snaphappi.com',
					expires: new Date(+new Date + 12096e5),
				});		
			}
			SNAPPI.setPageLoading(true);
			window.location.href = '/';
		}, 'button.continue');
	}
</script>
<?php $this->Layout->blockEnd(); ?>	
