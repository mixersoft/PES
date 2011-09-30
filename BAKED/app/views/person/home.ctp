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
	$ajaxSrc = Router::url(Configure::read('passedArgs.complete') + array('action'=>'photos', '?'=>array('gallery'=>1)));
	echo "<div id='gallery-photo-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' nodelay='1'></div>";
?>

<?php $this->Layout->blockStart('relatedContent');?>
<aside id="related-content" class="container_16">		    	
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
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' ></div>";
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
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
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