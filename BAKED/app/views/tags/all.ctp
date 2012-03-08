<?php
/**
 * CakePHP Tags Plugin
 *
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 */
?>
<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src=Stagehand::$default_badges['Tag'];
		echo $this->element('nav/section', compact('badge_src')); 
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
			</div>        	
		</div>
</aside>
<?php $this->Layout->blockEnd();?>