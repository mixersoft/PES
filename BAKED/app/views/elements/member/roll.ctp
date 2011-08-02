<?php
/**
 * @param array $members - usually $data['Member'] from $Model->find()
 */

$paginateModel = Configure::read('paginate.Model');
$members = $jsonData[$paginateModel];

$state['displayPage'] = array_filter_keys($this->params['paging'][$paginateModel], array('page', 'count', 'pageCount', 'current'));
$state['displayPage']['perpage'] = $this->params['paging'][$paginateModel]['options']['limit'] ;
// save for jsonData ouput 
$this->viewVars['jsonData']['STATE'] = $state;

$isPreview = (!empty($this->params['url']['preview']));
$total = $state['displayPage']['count'];
?>
<div id='paging-members-inner'>
<ul class='inline member-roll-header'>
	<?php //$total = isset($this->params['paging']['total']['Asset']) ? $this->params['paging']['total']['Asset'] : count($data['Asset']); ?>
	<li>Total of <?php echo $total; ?> Members</li>
</ul>
<div class='element-roll member placeholder'>
	<ul class='member-roll'>
		<?php 
			$SHORT = 12;
			$DEFAULT_SRC_ICON = Configure::read('path.blank_user_photo');
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
				$options['title'] = $fields['title'];
				$fields['ownerLink'] = $this->Html->link($fields['trim_owner'], "/users/home/{$member['id']}", $options );
				$fields['src_icon'] =  $member['src_thumbnail'] ? Session::read('stagepath_baseurl').getImageSrcBySize($member['src_thumbnail'], 'sq') : $DEFAULT_SRC_ICON;
				/*
				 * end move
				 */
				?>
		<li class='member-label thumbnail sq' id='<?php echo $member['id'] ?>'>
			<div class='thumb'>
				<?php $options = array('url'=>array_merge(array('plugin'=>'','controller'=>'person', 'action'=>'home', $member['id']))); 
					if (isset($fields['title'])) $options['title'] = $fields['title'];
					echo $this->Html->image($fields['src_icon'] , $options) ?>
			</div>
			<div class='thumb-label'>
				<?php echo String::insert(":new :ownerLink", $fields); ?>
			</div>
		</li>				
		<?php } ?>
	</ul>
	<p class='center'><?php if ($isPreview && $total > $state['displayPage']['perpage']) echo $this->Html->link('more...', ($this->passedArgs+array('plugin'=>'','action'=>'members'))); ?>
</div>
<?php if (!$isPreview) { ?>
	<div class="paging-control paging-numbers">
	<?php echo $this->Paginator->prev(' '.__('«', true), array(), null, array('class'=>'disabled'));?>
	<?php echo $this->Paginator->numbers(array('separator'=>null, 'modulus'=>'20'));?>
	<?php echo $this->Paginator->next(__('»', true).' ', array(), null, array('class' => 'disabled'));?>
	</div>
	<div style="text-align:center;"><span id="perpage_button" class="button">Perpage</span></div>
<?php } ?>
</div>
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.mergeSessionData();
	SNAPPI.cfg.MenuCfg.renderPerpageMenu();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>