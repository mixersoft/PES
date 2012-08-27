<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src=Stagehand::$default_badges['Person'];
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();
}
?>
<div class="users all">
	<?php echo $this->element('/member/roll');	?>
</div>

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
			</div>        	
		</div>
		<div class="grid_5 body-right">
            <section id="tag-cloud" class="trends">
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>