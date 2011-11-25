<?php 
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null)); 
	$this->Layout->blockEnd();		

	// tagCloud
	$paginateModel = Configure::read('paginate.Model');	
?>	
	<ul class='inline context'>
		<?php if (!$isPreview  && Configure::read("paginate.Options.{$paginateModel}.context")!='skip' ) { echo "<li>{$this->element('context')}</li>"; }?>
	</ul>
	
            <section id="tag-cloud" class="trends grid_16">
<?php 
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom), 'gallery'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='paging-tags-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
?>
			</section>
			
			
<?php $this->Layout->blockStart('relatedContent');?>
<aside id="related-content" class="related-content container_16 hide">		    	
        <div class="grid_11">
           	<div class="body">
           		<article>

           		</article>
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
					<a name='discussion'></a>
					<section class="discussion">
						<h1><?php __('Discussion'); ?></h1>			
					<?php
	$xhrSrc = array('plugin'=>'', 'action'=>'discussion', $this->passedArgs[0]);
	$xhrSrc = Router::url($xhrSrc);
	echo $this->element('comments/discussion-fragment', array('xhrSrc'=>$xhrSrc));
						?>	
					</section>
				</article>				
			</div>        	
		</div>
</aside>
<?php $this->Layout->blockEnd();?>


