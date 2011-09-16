<?php
/**
 * @param array $groups - usually $data['Group'] from $Model->find()
 */
// make helpers to format photo labels
// function label() {
	// return $this->Text->truncate($lookupField[$data[$labelField]], 10);
// }


$paginateModel = Configure::read('paginate.Model');
$groups = & $jsonData[$paginateModel];

$passedArgs = Configure::read('passedArgs.min');
$THUMBSIZE = isset($passedArgs['thumbSize']) ?  $passedArgs['thumbSize'] : 'll';
$THUMBSIZE = $isPreview ? 'sq' : $THUMBSIZE;
switch ($THUMBSIZE) {
	case "ll" :
		$SHORT = 255; $LONG = 255;
		break;
	case "sq":
		$SHORT = 20; $LONG = 255;
		break;
}
$PREVIEW_LIMIT = $isPreview ? 2 : false;
$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
?>
<section class="<?php if ($isWide) echo "wide "; ?>gallery group container_16">
<?php if ($isPreview) { ?>	
	<h1 class='count'>
		<?php
			if ($total==0) {
				echo "You do not belong to any Circles yet.";
			} else {
				echo "Total <span>{$total}</span> Circle" . ($total>1 ? "s. " : ". ");
				echo $this->Html->link('Show all', array('action'=>'groups')+$passedArgs); 
			}
		?> 
		</h1>
<?php } ?>		
	<div class="container">
<?php
			if ($PREVIEW_LIMIT) $groups = array_slice($groups, 0, $PREVIEW_LIMIT); 
			foreach ($groups as $group) { 
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['caption'] = $group['description'] ? $group['description'] : "[show description here]";
				$actionName = Configure::read('feeds.action');
				$fields['user_count'] = (int)$group['groups_user_count'];
				$fields['asset_count'] = (int)$group['assets_group_count'];
				$fields['trim_caption'] = $this->Text->truncate($fields['caption'], $LONG);
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $group['created'])) ? "<span class='new'>New! </span>" : '';
				$fields['title'] = $this->Text->truncate("{$group['title']}", $SHORT);
				$fields['src_icon'] =  $group['src_thumbnail'] ? Session::read('stagepath_baseurl').getImageSrcBySize($group['src_thumbnail'], $THUMBSIZE) : $DEFAULT_SRC_ICON;
				$controllerAlias = Configure::read('controller.action');
				
				$options = array('plugin'=>'','controller'=>$controllerAlias, $group['id']);
				$group_members['href'] = Router::url($options+array('action'=>'members'));
				$group_members['label'] = String::insert(":user_count Members", $fields);
				$lookup_privacyClassName = array(
					'0567'=>'public',
					'0631'=>'listing',
					'0119'=>'members',
					'0063'=>'admin',
				);
				$group_privacy['privacy'] = $lookup_privacyClassName[ $group['perms'] ];
				$group_privacy['label'] = $group_privacy['privacy'];
				$group_photos['href']  = Router::url($options+array('action'=>'photos'));
				$group_photos['label'] = String::insert(":asset_count Snaps", $fields); 
				/*
				 * end move
				 */
				?>
		<article class='FigureBox Group <?php  echo $THUMBSIZE; ?>' id='<?php echo $group['id'] ?>'>
			<figure>
				<?php $options = array('url'=>array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $group['id'])); 
					if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
					echo $this->Html->image( $fields['src_icon'] , $options); ?>
				<figcaption>
					<div class="label"><?php echo $fields['title']; ?><span><?php echo " ".$group['type']?></span></div>
					<ul class="inline extras">
					<?php 
						echo String::insert("<li class='icon privacy :privacy' title=':label'></li>", $group_privacy); 
						echo String::insert("<li class='members'><a href=':href' class='lock'>:label</a></li>", $group_members); 
						echo String::insert("<li class='snaps last'><a href=':href'>:label</a></li>", $group_photos);
					?>
					</ul>
					<div class="description" title="<?php echo $fields['caption']; ?>"><?php echo $fields['caption']; ?></div>
				</figcaption>
			</figure>
		</article>
		<?php } ?>
	</div>
</section>
