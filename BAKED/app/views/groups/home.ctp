<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		echo $this->element('nav/section', 
			array('badge_src'=>$badge_src,
				'classLabel'=>$data['Group']['type'],
				'label'=>$data['Group']['title'],
		));
?>
<div class="properties hide container_16">	
	<dl class="grid_16">
		<?php $i = 0;
			$dtClass = 'grid_3 alpha';
			$ddClass = 'grid_12 suffix_1 omega';
			$altClass = ' altrow ';
		?>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Owner'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'person', 'action' => 'home', $data['Owner']['id'])); ?>
			&nbsp;
		</span>	
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Photos'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $this->Html->link("{$data['Group']['assets_group_count']} photos","photos/{$data['Group']['id']}"); ?>
			&nbsp;
		</span>				
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Description'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['description']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Membership Policy'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['membership_policy']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Invitation Policy'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['invitation_policy']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Is NC17'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php if(1 == $data['Group']['isNC17']){ echo __('Yes'); } else { echo __('No');}?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Last Visit'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</span>
		<span class='<?php $i++;  echo $dtClass; if ($i % 2 == 0) echo $altClass;?>'><?php __('Created On'); ?></span>
		<span class='<?php echo $ddClass; if ($i % 2 == 0) echo $altClass;?>'>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</span>
	</dl>
</div>	
<?php	$this->Layout->blockEnd();	} ?>
<?php 
	$browseContentOk = $data['Group']['perms'];
	if (!$browseContentOk) return; 
	/*
	 *  *************** stop here if Group Privacy setting should hide content  *******
	 */
?>
<?php 
	$ajaxSrc = Router::url(Configure::read('passedArgs.complete') + array('action'=>'photos', 'perpage'=>24, '?'=>array('gallery'=>1)));
	echo "<div id='gallery-photo-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' nodelay='1'></div>";
?>
<?php  if ( $data['Group']['assets_group_count']>0 ) {
		$this->Layout->blockStart('markup'); 		?>
			<div class='empty-photo-gallery-message hide'><div class='related-content message blue rounded-5 wrap-v'>
				<h1>Snap Gallery</h1>
				<p>You must be a member of this Circle to see these Snaps from this Group.
				Join this Circle to share Snaps, Stories, and more with other members.</p>
				<ul class='inline' ><li class='btn orange rounded-5'><a href='/groups/join/<?php echo AppController::$uuid; ?>'>Join now.<a></li></ul>
			</div></div>
<?php 	$this->Layout->blockEnd(); } ?>	

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
					<section class="people tabbed-area cur-nav-fix">  
						<h1>Members</h1>      		
						<section class="wrap">
						  <section id="members">
<?php 
	// $ajaxSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'xhr-get', 'a'=>'members', 'e'=>'preview-members'));
	$xhrSrc = Router::url(Configure::read('passedArgs.min') + array('action'=>'members', '?'=>array('preview'=>1)));
	echo "<div id='members-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
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
						echo "<div id='paging-comments' class='paging-content wrap xhr-get' xhrSrc='{$xhrSrc}'  xhrTarget='paging-comments'></div>";	
						// echo $this->element('comments/discussion-fragment', array('xhrSrc'=>$xhrSrc));
					?>	
					</section>
				</article>				
			</div>        	
		</div>
		<div class="grid_5 body-right">
            <section id="tag-cloud" class="trends">
				<h1><?php __('Trends');?></h1>
<?php 
	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
	// tag form 	
	echo $this->element('tags', array('domId'=>'groups-tags', 'data'=>$data));
?>
			</section>
        	<section class="people">
        		<h1>People</h1>
			</section>
		</div>	
		
</aside>
<?php 
	$this->Layout->blockEnd();
?>	