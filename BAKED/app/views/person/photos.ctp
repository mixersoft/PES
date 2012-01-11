<?php echo $this->element('/photo/roll');?>

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
// this should be only the circles which include user photos
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='xhr-get gallery group' xhrSrc='{$ajaxSrc}' ></div>";
	
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
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
		$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
?>	
			</section>
        	<section class="people">
        		<h1>People</h1>
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>	

<?php  $this->Layout->blockStart('markup');
		if (Configure::read('controller.alias') == 'my') {	?>
			<div class='empty-photo-gallery-message hide'><div class=' message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
				<p>This is where you find your Snaps (i.e. the photos you have uploaded).</p>
				<p>For best results, we recommend you download and install our Desktop Uploader to quickly upload <b>all your photos</b> - even if you have 1000s. 
					Once uploaded, you can return here to organize and share your Snaps.  
					Or better yet, you can (someday soon) ask us to do it for you.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/my/upload'>Get started now.<a></li></ul>
			</div></div>
	<?php } ?>		
			<div class='empty-lightbox-gallery-message hide'><div class=' message blue rounded-5 wrap-v'>
				<p>Drag Snaps from above into the Lightbox. You can select multiple Snaps by pressing the Control or Shift key.</p>
			</div></div>
<?php 	$this->Layout->blockEnd(); ?>	