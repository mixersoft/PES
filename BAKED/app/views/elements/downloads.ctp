<div>
			<?php  
			$userAgent =  env('HTTP_USER_AGENT');
			if (isset($this->params['url']['platform']) && $this->params['url']['platform'] == 'all') {
					$downloads['Windows'] = 'snappi-uploader.exe';
					$downloads['Macintosh'] = 'snappi-uploader.osx.dmg';
			} else {
				if (strpos($userAgent, 'Windows') !== false) {
					$downloads['Windows'] = 'snappi-uploader.exe';
				} else if (strpos($userAgent, 'Macintosh') !== false) {
					$downloads['Macintosh'] = 'snappi-uploader.dmg';
				}
				$all_platforms=$this->here.'?platform=all';
			}
		?>
<div class="placeholder">
<h3>Upload a lot of Photos</h3>
<p>The Snaphappi Desktop Uploader is the recommended way to upload a large number of photos. 
Use it to add entire folders of photos at once, and easily restart or resume an upload in case there was a problem.
</p>
<p>Our Desktop Uploader makes adding 1000s of photos a snap. Just click to download, and then open the saved file to install. 
	
	<?php if (count($downloads) == 1) echo "(<a href='{$all_platforms}'>Show all platforms</a>)"; ?></p>
	<div id="download-uploader" >
			<?php foreach ($downloads as $platform =>$file) { ?>
				<div class='center' >
				<a href="/files/<?php echo $file ?>" >
					Snaphappi Desktop Uploader for <?php echo $platform ?>
					</a>
				</div>
			<?php } ?>	
	</div>
	<p /><b>Requirements:</b> The Snaphappi Desktop Uploader requires <a href='http://get.adobe.com/air/'>Adobe AIR 2.6+</a>. 
	You will prompted to download &amp install the latest version of if it is not available. 
</div>