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
$this->viewVars['jsonData']['STATE'] = $state;

$isPreview = (!empty($this->params['url']['preview']));
$total = $state['displayPage']['count'];
?>

<div id='paging-groups-inner'>
<div class='element-roll group placeholder'>
<ul class='inline group-roll-header'>
	<li>Total of <?php echo $total; ?> Groups</li>
	<li><?php echo $this->element('context'); ?></li>
</ul>
	<div id='json-groups' Groups='<?php echo json_encode(array('Groups'=>$groups)); // echo $this->element('/json/serialize', array('data'=>array('Groups'=>$groups))); ?>'></div>
	<ul class='group-roll'>
		<?php 
			$SHORT = 12; $LONG = 255;
			$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
			foreach ($groups as $group) { 
				
				/*
				 * TODO: move this to LabelHelper when complete
				 */
				$fields = array();
				$fields['caption'] = $group['description'] ? $group['description'] : "[show description here]";
				$actionName = Configure::read('feeds.action');
				if($actionName == 'most_members'){
					$fields['count'] = (int)$group['groups_user_count'];
				}else{
					$fields['count'] = (int)$group['assets_group_count'];
				}
				$fields['count'] = (int)$group['assets_group_count'];
				$fields['trim_caption'] = $this->Text->truncate($fields['caption'], $LONG);
				$fields['new'] = ($this->Time->wasWithinLast('3 day', $group['created'])) ? "<span class='new'>New! </span>" : '';
				$fields['title'] = $this->Text->truncate("{$group['title']}", $SHORT);
				$fields['src_icon'] =  $group['src_thumbnail'] ? Session::read('stagepath_baseurl').getImageSrcBySize($group['src_thumbnail'], 'tn') : $DEFAULT_SRC_ICON;
				$controllerAlias = @ifed($group['class'], 'groups');	// [groups|events|weddings]
				/*
				 * end move
				 */
				?>
		<li class='thumbnail tn' id='<?php echo $group['id'] ?>'>
			<div class='thumb'>
				<?php $options = array('url'=>array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $group['id'])); 
					if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
					echo $this->Html->image( $fields['src_icon'] , $options); ?>
			</div>
			<div class='thumb-label'>
				<?php 
					if($actionName == 'most_members'){
						echo String::insert(":new :title (:count members)", $fields); 
					}else{
						echo String::insert(":new :title (:count pics)", $fields); 
					}
				?>
				<?php //TODO: we should put unshare into the mouse context menu
					if (Configure::read('controller.alias')=='photos') { 
						echo '<br>'.$this->Html->link('unShare', array('controller'=>'photos','action'=>'unshare', AppController::$uuid, '?'=>array('data[Group][gids]'=>$group['id'])), array('style'=>'font-size:0.7em;', 'class' => 'hide')); 
					}
				?>
			</div>
		</li>
		<?php } ?>
	</ul>
	<p class='center'><?php if ($isPreview && $total > $state['displayPage']['perpage']) echo $this->Html->link('more...', ($this->passedArgs+array('plugin'=>'','action'=>'groups'))); ?>
	</p>
</div>
<?php if (!$isPreview) { 
	$options = array('model'=>$paginateModel); // uses Model->alias, i.e. Group, Member, Membership
	?>
	<div class="paging-control paging-numbers">
	<?php echo $this->Paginator->prev(' '.__('«', true), $options, null, array('class'=>'disabled'));?>
	<?php echo $this->Paginator->numbers($options+array('separator'=>null, 'modulus'=>'20'));?>
	<?php echo $this->Paginator->next(__('»', true).' ', $options, null, array('class' => 'disabled'));?>
	</div>
	<div style="text-align:center;"><span id="perpage_button" class="button">Perpage</span></div>
<?php } ?>	
</div>
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.cfg.MenuCfg.renderPerpageMenu();
	SNAPPI.cfg.MenuCfg.listenToGroupRoll();
	SNAPPI.cfg.MenuCfg.listenToSubstituteGroupRoll();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
