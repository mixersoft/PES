<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
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
	$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
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