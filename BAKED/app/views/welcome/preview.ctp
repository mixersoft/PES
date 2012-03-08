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
		<img src="/static/img/css-gui/smiley.gif" title="I love you guys...">.
	</p>
	<p>Our vision is to build a service that delivers surprise and delight from your rated photos.
		<span class="info-button orange">Show me more</span> </p> 
	<p>We know that rating the 10,000+ photos on your hard drive is <img src="/static/img/css-gui/frownie.gif">, 
		and we plan to offer an elegant, automated solution. But for now, you&#39;ll have to rate a few photos yourself &#151; this preview is only the tip of our iceberg.
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
		<li>Create wonderful Stories to share with your friends <img class='rounded-5 button' src="/static/img/hints/create.jpg" title="Look for this button to create new Circles, Stories, or upload Snaps"></li>
		<li>Determine who can see your photos through privacy and sharing</li>
		<li>Report a problem or post a question or comment in the Help section. 
			<span class='align-preview-icons'>
			<img class='rounded-5 button' src="/static/img/hints/help.jpg" title="Look for this button to ask for Help.">		
			<a href="http://www.facebook.com/pages/Snaphappi/16486082015"><img src="/static/img/comingsoon/facebook_32.png" alt="Find us on Facebook" title="Find us on Facebook" /></a> 
			<a href="http://www.twitter.com/snaphappi"><img src="/static/img/comingsoon/twitter_32.png" alt="Follow @snaphappi on Twitter" title="Follow @snaphappi on Twitter for the latest updates" /></a>	
			</span>
			</li>
	</ul>
	<br />	
	<div>
		<button class="continue orange" type="submit">Continue to Snaphappi</button>
		<span class="input checkbox">&nbsp;&nbsp;&nbsp;
			<input type="checkbox" id="WelcomePreviewSkip" name="data[Profile][welcome_preview_skip]"><label for="WelcomePreviewSkip"> Skip this page</label>
			</span>
	</div>	
	
</div>

	</div>
</section>
<nav class='section-header'></nav>

<?php $this->Layout->blockStart('javascript'); ?>
<script type="text/javascript">
	namespace('SNAPPI.onYready');
	var _Y = null;
	SNAPPI.onYready.preview = function(Y){
		if (_Y === null) _Y = Y;
		PAGE.jsonData['castingCall'] = {"CastingCall":{"ID":1330699845,"Timestamp":1330699845,"ProviderName":"snappi","Auditions":{"Audition":[{"id":"4bbb3976-b76c-4907-a195-11a0f67883f5","Photo":{"id":"4bbb3976-b76c-4907-a195-11a0f67883f5","W":2672,"H":4000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.2","Votes":"5"},"Img":{"Src":{"W":428,"H":640,"rootSrc":"stage6\/4bbb3976-b76c-4907-a195-11a0f67883f5.jpg","Orientation":1,"isRGB":true}},"isOwner":true,"DateTaken":"2009-09-09 18:01:42","TS":1252519302,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010598","origSrc":"\/venice\/P1010598.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":1336,"Y":2000},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-32d0-4727-87d9-11a0f67883f5","Photo":{"id":"4bbb3976-32d0-4727-87d9-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"5.00","Rotate":"1","Scrub":"","Score":"4.5","Votes":"2"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage6\/4bbb3976-32d0-4727-87d9-11a0f67883f5.jpg","Orientation":1,"isRGB":true}},"isOwner":true,"DateTaken":"2009-09-09 19:02:14","TS":1252522934,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010614","origSrc":"\/venice\/P1010614.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":"4de449d2-ad14-4847-a435-0494f67883f5","Shot":{"id":"4de449d2-ad14-4847-a435-0494f67883f5","count":"2"},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-5204-4280-8a76-11a0f67883f5","Photo":{"id":"4bbb3976-5204-4280-8a76-11a0f67883f5","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage3\/4bbb3976-5204-4280-8a76-11a0f67883f5.jpg","Orientation":1,"isRGB":true}},"isOwner":true,"DateTaken":"2009-09-10 11:30:30","TS":1252582230,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010646","origSrc":"\/venice\/P1010646.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-069c-4fff-90d9-11a0f67883f5","Photo":{"id":"4bbb3976-069c-4fff-90d9-11a0f67883f5","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"2.5","Votes":"2"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage4\/4bbb3976-069c-4fff-90d9-11a0f67883f5.jpg","Orientation":1,"isRGB":true}},"isOwner":true,"DateTaken":"2009-09-10 12:10:03","TS":1252584603,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010658","origSrc":"\/venice\/P1010658.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":"4ddc78bf-056c-416a-a36c-0494f67883f5","Shot":{"id":"4ddc78bf-056c-416a-a36c-0494f67883f5","count":"2"},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-528c-4912-ad0d-11a0f67883f5","Photo":{"id":"4bbb3976-528c-4912-ad0d-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage0\/4bbb3976-528c-4912-ad0d-11a0f67883f5.jpg","Orientation":1,"isRGB":true}},"isOwner":true,"DateTaken":"2009-09-10 12:47:01","TS":1252586821,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010667","origSrc":"\/venice\/P1010667.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-6414-47d4-b44c-11a0f67883f5","Photo":{"id":"4bbb3976-6414-47d4-b44c-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage3\/4bbb3976-6414-47d4-b44c-11a0f67883f5.jpg","Orientation":1,"isRGB":true}},"isOwner":true,"DateTaken":"2009-09-10 18:55:28","TS":1252608928,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010713","origSrc":"\/venice\/P1010713.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:03"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":"4ddc52eb-cdc0-468b-8f48-0494f67883f5","Shot":{"id":"4ddc52eb-cdc0-468b-8f48-0494f67883f5","count":"2"},"Tags":[],"Clusters":"","Credits":""}],"Bestshot":{"4de449d2-ad14-4847-a435-0494f67883f5":"4bbb3976-32d0-4727-87d9-11a0f67883f5","4ddc78bf-056c-416a-a36c-0494f67883f5":"4bbb3976-069c-4fff-90d9-11a0f67883f5","4ddc52eb-cdc0-468b-8f48-0494f67883f5":"4bbb3976-6414-47d4-b44c-11a0f67883f5"},"Total":6,"Perpage":null,"Pages":1,"Page":1,"Baseurl":"\/svc\/STAGING\/","ShotType":"Groupshot"},"Request":"lightbox","GroupAsShotPerm":"Groupshot","ShowHidden":true}};
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
			window.location.href = '/photos/all';
		}, 'button.continue');
		
		// get Montage
		_Y.on('click', function(e){
			var go = function(){
				var storyCfg = {
					castingCall: PAGE.jsonData.castingCall,
					roleCount: {lo: 3, hi:6},
					getStage: SNAPPI.UIHelper.create.getStage_modal,
					stageType: 'preview-ratings',		// determines menu to load
					stageTitle: 'Snaphappi Stories',
					hintId: 'HINT_Preview_StoryByRatings',
				}
				SNAPPI.UIHelper.create._GET_MONTAGE(storyCfg);
			};
			if (!SNAPPI.Dialog) {
				var cfg = {
					module_group: 'preview',
					ready: go,
				}
				SNAPPI.LazyLoad.extras(cfg);				
			} else go();
		}, 'span.info-button');
	}
	
</script>
<?php $this->Layout->blockEnd(); ?>	
