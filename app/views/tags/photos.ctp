<?php
$ownerCount = $data['Tag']['tagged_count'];
$badge_src=Stagehand::$default_badges['Tag'];
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', compact('badge_src'));
?>
<div class="properties hide container_16">
	<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_2 alpha';
			$ddClass = 'grid_12 suffix_2 omega';
			$altClass = ' altrow ';
		?>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Id'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['id']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Identifier'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['identifier']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Name'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['name']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Keyname'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['keyname']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Weight'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['weight']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Created'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['created']; ?>
			&nbsp;
		</span>
		<span<?php $i++; echo $dtClass; if ($i % 2 == 0) echo $altClass;?>><?php __('Modified'); ?></span>
		<span<?php $ddClass; if ($i++ % 2 == 0) echo $altClass;?>>
			<?php echo $data['Tag']['modified']; ?>
			&nbsp;
		</span>
	</dl>
</div>
<?php $this->Layout->blockEnd();	} ?>

<div class="tags photos">
	<p>Show as <?php echo $this->Html->link('Photostream', $this->passedArgs + array('action'=>'photostreams') );?>
	</p>	
	<?php echo $this->element('/photo/roll', compact('badge_src', 'ownerCount'));?>
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
				<article>
					<section class="circles tabbed-area cur-nav-fix">  
						<h1>Circles</h1>      		
						<section class="wrap">
						  <section id="circles">
<?php
// this should be only the circles which include user photos
	$ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'groups', '?'=>array('preview'=>1)));
	echo "<div id='groups-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'  delay='500'></div>";
	
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
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
		$xhrSrc = Router::url($xhrSrc);	
		echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}' delay='8000'></div>";	
?>	
			</section>
        	<section class="people">
        		<h1>People</h1>
			</section>
		</div>	
</aside>
<?php $this->Layout->blockEnd();?>	
