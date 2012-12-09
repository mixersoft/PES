		<?php  
			$default_uploader_type = 'AIR';
			$available_default = Configure::read("uploader.{$default_uploader_type}");
			
			$userAgent =  env('HTTP_USER_AGENT');
			if (strpos($userAgent, 'Windows') !== false) $client_platform = 'Windows'; 
			else if (strpos($userAgent, 'Macintosh') !== false) $client_platform = 'Macintosh';
			else $client_platform = 'Other';
			if (isset($uploader_type)) {
				$available = Configure::read("uploader.{$uploader_type}");	
			} else $available = array();
			$available = array_merge($available_default, $available );
			
			// customize offered download to match client_platform
			if (isset($this->params['url']['platform']) && $this->params['url']['platform'] == 'all') {
				$offered = $available;
			} else {
				if (strpos($userAgent, 'Windows') !== false) {
					$offered['Windows']  = $available['Windows'];
					// $downloads['Windows'] = array('file'=>'snappi-uploader.exe', 'os'=>'Windows 7/Vista/XP');
				} else if (strpos($userAgent, 'Macintosh') !== false) {
					$offered['Macintosh']  = $available['Macintosh'];
					// $downloads['Macintosh'] = array('file'=>'snappi-uploader.dmg', 'os'=>'OSX 10.5+');
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
				<?php foreach ($offered as $platform =>$download) { ?>
							<li class='btn orange rounded-5'>
								<a href="/files/<?php echo $download['file'] ?>" >
									Snaphappi Desktop Uploader for <b><?php echo $platform ?></b>
									<span class="os"><br />(<?php echo $download['osLabel'];  ?>)</span>
									</a>
							</li>
				<?php } ?>	
						</ul>
						<?php if (count($offered) == 1) echo "(<a href='{$all_platforms}'>Show all platforms</a>)"; ?>
					</div>
		</div>
		<p><b>Requirements:</b> The Snaphappi Desktop Uploader requires <a href='http://get.adobe.com/air/'>Adobe AIR 3.0</a>. 
		If it is not already available, the installation will <b>also download the latest version of AIR</b>, and prompt you to install.</p> 
