		<?php  
			$userAgent =  env('HTTP_USER_AGENT');
			if (isset($this->params['url']['platform']) && $this->params['url']['platform'] == 'all') {
					$downloads['Windows'] = 'snappi-uploader.exe';
					$downloads['Macintosh'] = 'snappi-uploader.dmg';
			} else {
				if (strpos($userAgent, 'Windows') !== false) {
					$downloads['Windows'] = 'snappi-uploader.exe';
				} else if (strpos($userAgent, 'Macintosh') !== false) {
					$downloads['Macintosh'] = 'snappi-uploader.dmg';
				}
				$all_platforms=$this->here.'?platform=all';
			}
		?>
	<p>The Snaphappi Desktop Uploader makes uploading 1000s of photos a snap. It offers the following key benefits:</p> 
	<blockquote style="margin:20px 40px;">
	<ul>
		<li>easily scan entire folders for JPG photo files, </li>
		<li>finish up to <b>50x faster</b> by uploading resized copies, and</li>
		<li>safely restart upload sessions at any time.</li>
	</ul>
	</blockquote>
	<p>Just click the button below to download, and then open the saved file. </p>
		<div id="download-uploader" >
					<div class='center' >
						<ul class='inline'>
				<?php foreach ($downloads as $platform =>$file) { ?>
							<li class='btn orange rounded-5'>
								<a href="/files/<?php echo $file ?>" >
									Snaphappi Desktop Uploader for <b><?php echo $platform ?></b>
									</a>
							</li>
				<?php } ?>	
						</ul>
						<?php if (count($downloads) == 1) echo "(<a href='{$all_platforms}'>Show all platforms</a>)"; ?>
					</div>
		</div>
		<p><b>Requirements:</b> The Snaphappi Desktop Uploader requires <a href='http://get.adobe.com/air/'>Adobe AIR 3.0</a>. 
		If it is not already available, the installation will <b>also download the latest version of AIR</b>, and prompt you to install.</p> 
