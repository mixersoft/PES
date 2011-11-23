<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'person');
		echo $this->element('nav/section', array('badge_src'=>$badge_src)); 
	$this->Layout->blockEnd();	
}
?>
<?php echo $this->element('/group/roll');?>

<?php $this->Layout->blockStart('relatedContent');?>
<aside id="related-content" class="container_16">		    	
        <div class="grid_11">
           	<section class="left">
				<article>
        	    	<section class="tabbed-area cur-nav-fix">  
            		    <h3 class="recent">Recent Activity</h3>      		
                		<section class="box-wrap">
                            <section id="snaps">
                          </section>
                        </section>
					</section>
				</article>
				<article>
					<section class="tabbed-area cur-nav-fix">  
						<h3 class="person">People</h3>      		
						<section class="box-wrap">
						  <section id="members">
<?php 
	// $ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'fragment', 'a'=>'members', 'e'=>'preview-members'));
	// $ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'members', '?'=>array('preview'=>1)));
	// echo "<div id='members-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
?>							  	
						  </section>
						</section>
					</section>
				</article>
			</section>        	
		</div>
		<div class="grid_5">
        	<aside>
                <section id="tag-cloud" class="popular">
					<h3 class="popular"><?php __('Trends');?></h3>
<?php 
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
		// tag form 	
	echo $this->element('tags', array('domId'=>'groups-tags', 'data'=>&$group));
?>
	
				</section>
			</aside>
		</div>	
		
</aside>
<?php 
	$this->Layout->blockEnd();
?>	


<?php  $this->Layout->blockStart('markup');
		if (Configure::read('controller.alias') == 'my') {	?>
			<div class='empty-circle-gallery-message hide'><div class='related-content message blue rounded-5 wrap-v'>
				<h1>Circle Gallery</h1>
				<p>This is where you find your Circles - the Groups or Events you have joined.</p>
				<p>Join a Circle and share Snaps and Stories with other members. If you can't find one you like, create a new one now.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/groups/all'>Explore Circles now.<a></li></ul>
			</div></div>
	<?php } ?>		
<?php 	$this->Layout->blockEnd(); ?>	