
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.xhrFetch.init(); 
};
PAGE.init.push(initOnce);
</script>
<?php 

if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src=Stagehand::$default_badges['Asset'];
		echo $this->element('nav/section', compact('badge_src'));
	$this->Layout->blockEnd();
}
?>
<div class="photos all ">
	<div id='paging-photos' class='paging-content' xhrTarget='paging-photos-inner'>
	<?php echo $this->element('/collections/roll');?>
	</div>
</div>		

<?php $this->Layout->blockStart('relatedContent');?>
<aside id="related-content" class="related-content container_16 hide">		    	
        <div class="grid_11">
           	<div class="body">
				<article>
        	    	<section class="recent tabbed-area cur-nav-fix">  
            		    <h1>Recent Activity</h1>      		
                		<section class="wrap">
                          <section id="stories">
                          </section>
                        </section>
					</section>
				</article>
			</div>        	
		</div>
		<div class="grid_5 body-right">
            <section id="tag-cloud" class="trends">
				<h1><?php __('Trends');?></h1>
<?php // tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Collection');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}' delay='8000'></div>";
?>
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>