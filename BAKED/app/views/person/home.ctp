<?php
// $isOwner = in_array($data['User']['id'], AppController::$iserid);
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src')); 
?>
	<div class='properties hide container_16'>
		<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_2 alpha';
			$ddClass = 'grid_12 suffix_2 omega';
			$altClass = ' altrow ';
		?>
			<span class='<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php __('Username'); ?></span>
			<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php echo $data['User']['username']; ?>
			</span>
			<span class='<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Last Visit'); ?></span>
			<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php echo $this->Time->timeAgoInWords($data['User']['lastVisit']); ?>
			</span>
			<span class='<?php  $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Member Since'); ?></span>
			<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
				<?php echo $this->Time->nice($data['User']['created']); ?>
			</span>
		</dl>
	</div>
<?php	$this->Layout->blockEnd();	} ?>	
<?php 
	$ajaxSrc = Router::url(Configure::read('passedArgs.complete') + array('action'=>'photos', '?'=>array('gallery'=>1)));
	echo "<div id='gallery-photo-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' delay='0'></div>";
?>

<?php  $this->Layout->blockStart('markup');
		if (Configure::read('controller.alias') == 'my') {	?>
			<div class='empty-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
				<p>This is where you find your Snaps (i.e. the photos you have uploaded).</p>
				<p>For best results, we recommend you download and install our Desktop Uploader to quickly upload <b>all your photos</b> - even if you have 1000s. 
					Once uploaded, you can return here to organize and share your Snaps.  
					Or better yet, you can (someday soon) ask us to do it for you.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/my/upload'>Get started now.<a></li></ul>
			</div></div>
	<?php } else if ( Configure::read('controller.alias') == 'person' && $data['User']['asset_count'] >0 ) { ?>
			<div class='empty-photo-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
				<p>You are not connected with this Person.</p>
				<p>You can connect with other members by joining the same Circle. Send your friends an invitation to join your Circles.</p>
			</div></div>			
	<?php } ?>		
			<div class='empty-lightbox-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<p>Drag selected Snaps from above into the Lightbox. 
					Use <span class='keypress multiselect-single'>Ctrl-Click</span> or <span class='keypress'>Shift-Click</span> to select multiple Snaps.
				</p>
			</div></div>
<?php 	$this->Layout->blockEnd(); ?>	
			
<?php $this->Layout->blockStart('relatedContent');?>
<aside id="related-content" class="related-content container_16 hide">		    	
        <div class="grid_11">
           	<div class="body">
				<article>
         	    	<section class="recent tabbed-area cur-nav-fix">  
            		    <h1>Recent Activity</h1>      		
                		<section class="wrap">
                            <section id="snaps">
                          </section>
                        </section>
					</section>
				</article>
				<article>
					<section class="circles tabbed-area cur-nav-fix">  
						<h1>Circles</h1>      		
						<section class="wrap">
						  <section id="circles">
<?php
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='xhr-get gallery group' xhrSrc='{$ajaxSrc}' delay='500' ></div>";
?>							  	
						  </section>
						</section>
					</section>
				</article>
			</div>        	
		</div>
		<div class="grid_5 body-right">
            <section id="tag-cloud" class="trends">
				<h1><?php __('Trends');?></h1>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}' delay='8000'></div>";
?>	
			</section>
        	<section class="people">
        		<h1>People</h1>
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>	