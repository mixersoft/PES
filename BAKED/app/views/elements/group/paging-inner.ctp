<?php
/**
 * @param array $groups - usually $data['Group'] from $Model->find()
 */
// make helpers to format photo labels
function label() {
	return $this->Text->truncate($lookupField[$data[$labelField]], 10);
}


$this->Paginator->options['url']['plugin']='';
$paginateModel = Configure::read('paginate.Model');
$groups = & $jsonData[$paginateModel];

$state['displayPage'] = array_filter_keys($this->params['paging'][$paginateModel], array('page', 'count', 'pageCount', 'current'));
$state['displayPage']['perpage'] = $this->params['paging'][$paginateModel]['options']['limit'] ;
// save for jsonData ouput 
$total = $state['displayPage']['count'] + 0;	// as int
$state['displayPage']['total'] = $total;	
$this->viewVars['jsonData']['STATE'] = $state;

$THUMBSIZE = 'sq';
$SHORT = 12; $LONG = 255;
$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');

?>
<ul class='group-roll circle-thumb'>
<?php
			foreach ($groups as $group) { 
				
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['caption'] = $group['description'] ? $group['description'] : "[show description here]";
				$actionName = Configure::read('feeds.action');
				$fields['user_count'] = (int)$group['groups_user_count'];
				$fields['asset_count'] = (int)$group['assets_group_count'];
				// if($actionName == 'most_members'){
					// $fields['count'] = (int)$group['groups_user_count'];
				// }else{
					// $fields['count'] = (int)$group['assets_group_count'];
				// }
				// $fields['count'] = (int)$group['assets_group_count'];
				$fields['trim_caption'] = $this->Text->truncate($fields['caption'], $LONG);
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $group['created'])) ? "<span class='new'>New! </span>" : '';
				$fields['title'] = $this->Text->truncate("{$group['title']}", $SHORT);
				$fields['src_icon'] =  $group['src_thumbnail'] ? Session::read('stagepath_baseurl').getImageSrcBySize($group['src_thumbnail'], $THUMBSIZE) : $DEFAULT_SRC_ICON;
				$controllerAlias = Configure::read('controller.action');
				
				$options = array('plugin'=>'','controller'=>$controllerAlias, $group['id']);
				$group_members['href'] = Router::url($options+array('action'=>'members'));
				$group_members['label'] = String::insert(":user_count Members", $fields);
				$group_members['privacy'] = 'lock';
				$group_photos['href']  = Router::url($options+array('action'=>'photos'));
				$group_photos['label'] = String::insert(":asset_count Snaps", $fields); 
				/*
				 * end move
				 */
				?>
		<li class='thumbnail <?php  echo $THUMBSIZE; ?>' id='<?php echo $group['id'] ?>'>
			<figure class='thumb'>
				<?php $options = array('url'=>array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $group['id'])); 
					if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
					echo $this->Html->image( $fields['src_icon'] , $options); ?>
			</figure>
			<figure class='thumb-label'>
				<p><?php echo $fields['title']; ?></p>
				<?php 
					echo String::insert("<a href=':href' class='lock'>:label</a>", $group_members); 
					echo String::insert("<a href=':href'>:label</a>", $group_photos);
				?>
<!-- 				<?php 
					if($actionName == 'most_members'){
						echo String::insert(":new :title (:count members)", $fields); 
					}else{
						echo String::insert(":new :title (:count pics)", $fields); 
					}
				?>
				<?php //TODO: we should put unshare into the mouse context menu
					if (Configure::read('controller.alias')=='photos') { 
						// echo '<br>'.$this->Html->link('unShare', array('controller'=>'photos','action'=>'unshare', AppController::$uuid, '?'=>array('data[Group][gids]'=>$group['id'])), array('style'=>'font-size:0.7em;', 'class' => 'hide')); 
					}
				?> -->
			</figure>
		</li>
		<?php } ?>
</ul>		
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.mergeSessionData();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>	