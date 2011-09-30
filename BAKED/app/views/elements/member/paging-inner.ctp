<?php
/**
 * @param array $groups - usually $data['Group'] from $Model->find()
 */
// make helpers to format photo labels
// function label() {
	// return $this->Text->truncate($lookupField[$data[$labelField]], 10);
// }

$paginateModel = Configure::read('paginate.Model');
$members = & $jsonData[$paginateModel];

$passedArgs = Configure::read('passedArgs.min');
$THUMBSIZE = isset($passedArgs['thumbSize']) ?  $passedArgs['thumbSize'] : 'lm';
$THUMBSIZE = $isPreview ? 'sq' : $THUMBSIZE;
switch ($THUMBSIZE) {
	case "lm" :
		$SHORT = 24; $LONG = 255;
		break;
	case "sq":
		$SHORT = 12; $LONG = 255;
		break;
}
$PREVIEW_LIMIT = $isPreview ? 6 : false;
$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
?>
<section class="<?php if ($isWide) echo "wide "; ?>gallery person">
<?php if ($isPreview) { ?>	
	<h2>
		<?php
			if ($total==0) {
				echo "This Circle has <span class='count'>no</span> members. Join now.";
			} else {
				echo "Total <span class='count'>{$total}</span> Member" . ($total>1 ? "s. " : ". ");
				echo $this->Html->link('Show all', array('action'=>'members')+$passedArgs); 
			}
		?> 
		</h2>
<?php } ?>		
	<div class="container">
<?php
			if ($PREVIEW_LIMIT) $members = array_slice($members, 0, $PREVIEW_LIMIT);
			foreach ($members as $member) { 
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $member['created'])) ? "<span class='new'>New! </span>" : '';
				// show owner name/link in label
				// show count photos in titles, context sensitive?
				$fields['owner'] = $member['username'];
				$fields['trim_owner'] = $this->Text->truncate($fields['owner'], $SHORT);
				$fields['title'] = "member since {$this->Time->nice($member['created'])}";
				$fields['last_login'] = "last visit: {$this->Time->timeAgoInWords($member['last_login'])}";
				$link_options['title'] = $fields['title'];
				// $fields['ownerLink'] = $this->Html->link($fields['trim_owner'], "/person/home/{$member['id']}", $link_options );
				$fields['src_icon'] =  $member['src_thumbnail'] ? Session::read('stagepath_baseurl').getImageSrcBySize($member['src_thumbnail'], $THUMBSIZE) : $DEFAULT_SRC_ICON;
				$controllerAlias = Configure::read('controller.action');
				
				$options = array('plugin'=>'','controller'=>'person', $member['id']);
				$person_snaps['label'] = String::insert(":asset_count Snaps", $member); 
				$person_snaps['href'] = Router::url($options+array('action'=>'photos'));
				$person_memberships['label'] = String::insert(":groups_user_count Circles", $member); 
				$person_memberships['href'] =  Router::url($options+array('action'=>'groups'));
				/*
				 * end move
				 */
				?>
		<article class='FigureBox Person <?php  echo $THUMBSIZE; ?>' id='<?php echo $member['id'] ?>'>
			<figure>
				<?php $options = array('url'=>array_merge(array('plugin'=>'','controller'=>'person', 'action'=>'home', $member['id'])));
					$img = $this->Html->image( $fields['src_icon'] , $link_options);
					echo $this->Html->link($img, "/person/home/{$member['id']}", array('escape'=>false) );
				?>
				<figcaption>
					<div class="label"><?php echo String::insert(":new :owner", $fields); ?></div>
					<ul class="inline extras">
					<?php 
						echo String::insert("<li class='snaps'><a href=':href' title='see Snaps'>:label</a></li>", $person_snaps); 
						echo String::insert("<li class='circles last'><a href=':href' title='see Circles'>:label</a></li>", $person_memberships);
					?>
					</ul>
					<div class="description"><?php echo $fields['last_login']; ?></div>
				</figcaption>
			</figure>
		</article>
		<?php } ?>
	</div>
</section>
