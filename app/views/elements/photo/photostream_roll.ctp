<?php
/**
 * @param array $photos - usually $data['Asset'] from $Model->find()
 * @param string $labelField - $data['Asset'][n][$labelField]
 * @param aa $lookupField - if set, use $data['Asset'][n][$labelField] as key to find label value
 * @param int $limit - truncate roll to limit
 * @param int $total - total count of photos 
 * @param uuid $uuid - the id of the current context, i.e. user/group/asset/collection
 */

$total = isset($total) ? $total : count($photos);
if (isset($limit) && $limit) {
	if (!isset($this->params['named']['sort']))  shuffle($photos);
	$photos = array_splice($photos, 0, $limit);
}
?>

<div class='element-roll photo placeholder'>
	<ul class='photo-roll'>
		<?php 
			$CHAR = 12;
			foreach ($photos as $photo) { 
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['caption'] = $photo['caption'];
				$fields['trim_caption'] = $this->Text->truncate($fields['caption'], $CHAR);
				$fields['dateTaken'] = $this->Time->nice($photo['dateTaken']);
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $photo['created'])) ? "<span class='new'>New! </span>" : '';
				if (Session::read('lookup.context.keyName')!='person' && $this->name!='Users') {
//				if ($labelField=='owner_id' && isset($lookupField)) {
					// show owner name/link in label
					$fields['owner'] = $lookupField[$photo[$labelField]];
					$fields['trim_owner'] = $this->Text->truncate($fields['owner'], $CHAR-4);
					$fields['title'] = "From {$fields['owner']}, taken {$fields['dateTaken']}";
					$options['title'] = $fields['title'];
					$fields['ownerLink'] = $this->Html->link($fields['trim_owner'], array('plugin'=>'','controller'=>'users', 'action'=>'home', $photo[$labelField]), $options );
				} else {
					$fields['title'] = "Taken {$fields['dateTaken']}";
				}
				/*
				 * end move
				 */
				?>
		<li class='thumbnail sq' id='<?php echo $photo['id'] ?>'>
			<div class='thumb'>
				<?php $options = array('url'=>array_merge(array('plugin'=>'','controller'=>'photos', 'action'=>'home', $photo['id']))); 
					if (isset($fields['title'])) $options['title'] = $fields['title'];
					echo $this->Html->image( Stagehand::getSrc($photo['src_thumbnail'], 'sq') , $options) ?>
			</div>
			<div class='thumb-label'>
				<?php
						if (Session::read('lookup.context.keyName')!='person' && $this->name!='Users') {
							echo String::insert(":new From :ownerLink", $fields);
						} else {
							 echo String::insert(":new :trim_caption", $fields);
						}
				 ?>
			</div>
		</li>
		<?php } ?>
	</ul>
	<p class='center'><?php if (count($photos)<$total) echo $this->Html->link('more...', array('controller'=>'users','action'=>'photos', $photos[0]['owner_id'])); ?></p>
	
</div>
<?php echo $this->element('/lightbox'); ?>