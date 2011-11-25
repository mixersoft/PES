<?php
/**
 * @param array $members - usually $data['User'] from $Model->find()
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
		$SHORT = 20; $LONG = 255;
		break;
	case "sq":
		$SHORT = 12; $LONG = 255;
		break;
}
$PREVIEW_LIMIT = $isPreview ? 6 : false;
?>
<section class="<?php if ($isWide) echo "wide "; ?>gallery person">
	<div class="container">
<?php
			if ($PREVIEW_LIMIT) $members = array_slice($members, 0, $PREVIEW_LIMIT);
			$lookup_role = Configure::read('lookup.roles');
			foreach ($members as $member) {
				$role = array_search($member['primary_group_id'], $lookup_role, true);
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $member['created'])) ? "<span class='new'>New! </span>" : '';
				// show owner name/link in label
				// show count photos in titles, context sensitive?
				$fields['owner'] = $member['username'];
				if ($role == 'GUEST') {
					$fields['trim_owner'] = 'Guest';
				} else $fields['trim_owner'] = $this->Text->truncate($fields['owner'], $SHORT);
				$fields['title'] = "member since {$this->Time->nice($member['created'])}";
				$fields['last_login'] = "last visit: {$this->Time->timeAgoInWords($member['last_login'])}";
				$link_options['title'] = $fields['title'];
				// $fields['ownerLink'] = $this->Html->link($fields['trim_owner'], "/person/home/{$member['id']}", $link_options );
				$fields['src_icon'] =  Stagehand::getSrc($member['src_thumbnail'], $THUMBSIZE, 'person');
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
					<div class="label" title="<?php echo $fields['owner'] ?>"><?php echo String::insert(":new :trim_owner", $fields); ?></div>
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
