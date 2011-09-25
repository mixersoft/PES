<?php echo $this->element('/photo/roll');?>

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
						<h3 class="circle">Circles</h3>      		
						<section class="box-wrap">
						  <section id="circles">
<?php
// this should be only the circles which include user photos
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' ></div>";
	
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
<?php	// tagCloud
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='grid_5 xhr-get' xhrSrc='{$ajaxSrc}'></div>";
?>	
				</section>
            	<section class="people">
            		<h3 class="people">People</h3>
				</section>
			</aside>
		</div>	
		
</aside>
<?php $this->Layout->blockEnd();?>	