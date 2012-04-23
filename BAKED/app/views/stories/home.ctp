<?php
	$isTouch =  (1 || strpos(env('SERVER_NAME'),'touch')===0); 
	$iFrameHeight = $isTouch ? 'height="515"' : 'height="800"';  // only for ipad landscape

	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Collection']['src_thumbnail'], 'sq');
		echo $this->element('nav/section', compact('badge_src'));
?>
	<div class='properties hide container_16'>
		<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_2 alpha';
			$ddClass = 'grid_12 suffix_2 omega';
			$altClass = ' altrow ';
		?>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Owner'); ?></span>
	<span <?php echo $ddClass; if ($i++ % 2 == 0) echo $altClass;?>><?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'users', 'action' => 'home', $data['Owner']['id'])); ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Title'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Collection']['title']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Description'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Collection']['description']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Snaps'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Collection']['assets_collection_count']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Circles'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Collection']['collections_group_count']; ?>
	&nbsp;</span>
	<span <?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Created On'); ?></span>
	<span <?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>><?php echo $data['Collection']['created']; ?>
	&nbsp;</span>
		</dl>
	</div>
<?php	$this->Layout->blockEnd(); ?>	
<section class='story pagemaker-stage cf' <?php echo "uuid='".AppController::$uuid."'"; ?> >
<?php 
	$ajaxSrc = Router::url(Configure::read('passedArgs.complete') + array('action'=>'story', '?'=>array('iframe'=>1)));
	// echo "<div id='gallery-story-xhr' class='xhr-get stage-body' xhrSrc='{$ajaxSrc}' delay='0'></div>";
	echo "<iframe src='{$ajaxSrc}' frameborder='0' {$iFrameHeight} width='960'></iframe>";
?>

</section>	
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
			$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
			echo "<div id='groups-preview-xhr' class='DISABLED-xhr-get gallery group' xhrSrc='{$ajaxSrc}' delay='500'></div>";
		?>						  	
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
						echo "<div id='paging-comments' class='paging-content wrap xhr-get' xhrSrc='{$xhrSrc}'  xhrTarget='paging-comments' delay='8000'></div>";	
					?>	
					</section>
				</article>	
			</div>        	
		</div>
		<div class="grid_5 body-right">
            <section id="tag-cloud" class="trends">
				<h1><?php __('Trends');?></h1>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Collection');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}' delay='8000'></div>";
	// tag form 	
	echo $this->element('tags', array('uuid'=>AppController::$uuid, 'model'=>'Collection'));
?>	
			</section>
        	<section class="people">
        		<h1>People</h1>
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>		
<?php 	$this->Layout->blockStart('javascript'); ?>
<script type="text/javascript">
	var initOnce = function() {
		var Y = SNAPPI.Y;
		SNAPPI.mergeSessionData();
		SNAPPI.xhrFetch.init(); 
		var iframe = Y.one('section.pagemaker-stage > iframe');
		if (iframe.getAttribute('qsrc')) {
			var h = iframe.get('winHeight')-iframe.get('offsetTop')-16; 
			h = Math.min(800,Math.max(500,h));	// 500 < h < 800px
			iframe.setAttribute('height', h);
alert(iframe.getAttribute('height'));
			iframe.set('src',iframe.getAttribute('qsrc')).setAttribute('qsrc','');
		}
	};
	try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
	catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<?php $this->Layout->blockEnd();?>