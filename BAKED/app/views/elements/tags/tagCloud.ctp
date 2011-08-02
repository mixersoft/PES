<div id="paging-tags-inner" class='paging-content placeholder'>
<a name='trends'></a>
	<?php 
//		if (!isset($cloudTags)) return;
Configure::write('debug',1);
		$this->Paginator->options['url']['plugin']='';
		$paginateModel = Configure::read('paginate.Model');		
		
		$state = (array)Configure::read('state');
		$state['displayPage'] = array_filter_keys($this->params['paging']['Tagged'], array('page', 'count', 'pageCount', 'current'));
		$state['displayPage']['perpage'] = $this->params['paging']['Tagged']['options']['limit']; 
		if (!isset($isPreview))  {
			$isPreview = isset($this->params['url']['preview']) ? $this->params['url']['preview'] : 1;  // default for tagCloud == preview
		}
		$total = $state['displayPage']['count'];
	?>
	
	<ul class='inline tag-roll-header'>
	<li>Total of <?php echo $total; ?> Tags</li>
	<li><?php if (empty($isPreview)  && Configure::read("paginate.Options.{$paginateModel}.context")!='skip' ) { echo $this->element('context'); }?></li>
	</ul>
	<?php 
		echo $this->TagCloud->display($cloudTags, array(
			'url' => array('plugin'=>'','controller' => 'tags', 'action'=>'home'),
			'before' => '<span style="font-size: %size%pt" class="tag">',
			'after' => '</span>',
			'minSize' => '12',
			'maxSize' => '20',
			'shuffle' => 0
		));
	?>
	<?php  		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if (!$isPreview) {
			$this->Paginator->options['url']['?']=array('xhrfrom'=>implode('~', $xhrFrom))+array('preview'=>0);
	?>
		<div class="paging-control paging-numbers">
		<?php echo $this->Paginator->prev(' '.__('«', true), array(), null, array('class'=>'disabled'));?>
		<?php echo $this->Paginator->numbers(array('separator'=>null, 'modulus'=>'20'));?>
		<?php echo $this->Paginator->next(__('»', true).' ', array(), null, array('class' => 'disabled'));?>
		</div>
	<?php } else {
			if (isset($state['displayPage']['pageCount']) && $state['displayPage']['pageCount'] > 1) {
				$controllerAlias = Configure::read("lookup.xfr.{$xhrFrom['keyName']}.ControllerAlias");
				$next = $this->passedArgs+array('controller'=>$controllerAlias,'action'=>'trends', $xhrFrom['uuid']);
				echo $this->Html->link('more...', $next);
			}
		}
	?>
</div>
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.ajax.initPaging();
//	SNAPPI.Menu.renderPerpageMenu();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>