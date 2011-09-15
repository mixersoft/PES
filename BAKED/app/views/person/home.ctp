<?php
// $isOwner = in_array($data['User']['id'], AppController::$iserid);
$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');	 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail'])); 
?>
	<div class='properties placehoder container_16'>
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
	$ajaxSrc = Router::url(Configure::read('passedArgs.complete') + array('action'=>'photos', '?'=>array('preview'=>1)));
	echo "<div id='photos-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}' nodelay='1'></div>";
?>

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
						  <section id="snaps">
<?php
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}' ></div>";
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
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='grid_5 fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
				</section>
            	<section class="people">
            		<h3 class="people">People</h3>
				</section>
			</aside>
		</div>	
		
</aside>
<?php $this->Layout->blockEnd();?>	