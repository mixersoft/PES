<?php
/**
 * @param array $collections - usually $data['Collection'] from $Model->find()
 */
// make helpers to format photo labels
// function label() {
	// return $this->Text->truncate($lookupField[$data[$labelField]], 10);
// }


$paginateModel = Configure::read('paginate.Model');
$collections = & $jsonData[$paginateModel];				// from $this->viewVars['jsonData']
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
$PREVIEW_LIMIT = $isPreview ? 8 : false;
?>

	<div class="container">
<?php
			if ($PREVIEW_LIMIT) $collections = array_slice($collections, 0, $PREVIEW_LIMIT); 
			foreach ($collections as $collection) {
				$collection['type'] = ($controllerAttrs['name'] == 'Collections') ? $controllerAttrs['label'] : 'Collection'; 
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['caption'] = $collection['description'] ? $collection['description'] : "[show description here]";
				$actionName = Configure::read('feeds.action');
				$fields['asset_count'] = (int)$collection['assets_collection_count'];
				$fields['group_count'] = (int)$collection['collections_group_count'];
				$fields['trim_caption'] = $this->Text->truncate($fields['caption'], $LONG);
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $collection['created'])) ? "<span class='new'>New! </span>" : '';
				$fields['title'] = $this->Text->truncate("{$collection['title']}", $SHORT);
				$fields['src_icon'] =  Stagehand::getSrc($collection['src_thumbnail'], $THUMBSIZE, $collection['type']);
				$controllerAlias = ($controllerAttrs['action'] == 'all') ? $controllerAttrs['alias'] : $controllerAttrs['action'];
				
				$options = array('plugin'=>'','controller'=>$controllerAlias, $collection['id']);
				$collection_circles['href'] = Router::url($options+array('action'=>'groups'));
				$collection_circles['label'] = String::insert(":group_count Circles", $fields);
				$lookup_privacyClassName = array(
					'0519'=>'public',
					'0071'=>'members',
					'0007'=>'private',
				);
				$collection_privacy['privacy'] = $lookup_privacyClassName[ $collection['perms'] ];
				$collection_privacy['label'] = $collection_privacy['privacy'];
				$collection_photos['href']  = Router::url($options+array('action'=>'photos'));
				$collection_photos['label'] = String::insert(":asset_count Snaps", $fields); 
				/*
				 * end move
				 */
				
				// TODO: change to .FigureBox.Collection
				?>
		<article class='FigureBox Collection <?php  echo $THUMBSIZE; ?>' id='<?php echo $collection['id'] ?>'>
			<figure>
				<?php
					$linkTo =  Router::url(array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $collection['id']));
					$options = array('linkTo'=>$linkTo); 
					if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
					echo $this->Html->image( $fields['src_icon'] , $options); ?>
				<figcaption>
					<div class="label"><?php echo $fields['title']; ?><span><?php echo " Story"?></span></div>
					<ul class="inline extras">
					<?php 
						echo String::insert("<li class='icon privacy :privacy' title=':label'></li>", $collection_privacy); 
						echo String::insert("<li class='snaps last'><a href=':href'>:label</a></li>", $collection_photos);
						echo String::insert("<li class='groups'><a href=':href' class='lock'>:label</a></li>", $collection_circles); 
					?>
					</ul>
					<div class="description" title="<?php echo $fields['caption']; ?>"><?php echo $fields['caption']; ?></div>
				</figcaption>
			</figure>
		</article>
		<?php } ?>
	</div>

