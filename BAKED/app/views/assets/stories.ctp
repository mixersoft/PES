<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Asset']['src_thumbnail'], 'sq');
		echo $this->element('nav/section', compact('badge_src'));
	$this->Layout->blockEnd();
?>
	
<?php echo $this->element('/collections/roll');?>

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
						<h1>People</h1>      		
						<section class="wrap">
						  <section id="members">
<?php 
	// $ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'fragment', 'a'=>'members', 'e'=>'preview-members'));
	// $ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'members', '?'=>array('preview'=>1)));
	// echo "<div id='members-preview-xhr' class='xhr-get gallery person' xhrSrc='{$ajaxSrc}'></div>";
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
<?php 
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='DISABLE-xhr-get' xhrSrc='{$xhrSrc}' delay='2000'></div>";
?>
	
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>



<?php  $this->Layout->blockStart('markup');
		if (Configure::read('controller.alias') == 'my') {	?>
			<div class='empty-circle-gallery-message hide'><div class='message blue rounded-5 wrap-v'>
				<h1>Story Gallery</h1>
				<p>This is where you find the Stories you have created or have access to.</p>
				<p>If you don't see one you like, grab a few Snaps and create a new one now.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/stories/all'>Explore Stories now.<a></li></ul>
			</div></div>
	<?php } ?>		
<?php 	$this->Layout->blockEnd(); ?>	