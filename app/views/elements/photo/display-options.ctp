<?php  
	$passed = Configure::read('passedArgs.complete');
	
	$btn_active=array();
	/*
	 * Generate Paginator->sort() urls when sortin on field in associated models
	 */
	$this->Paginator->options['url']['plugin']='';
	
	// Sort select TAG
	$paginate_desc['url']['direction'] = 'desc';
	$orderBy_options = array();
	$orderBy_options['dateTaken'] = array('A_markup'=>$this->Paginator->sort('Date Taken', 'dateTaken'));
	$orderBy_options['rating'] = array('A_markup'=>$this->Paginator->sort('Top Rated', 'rating', $paginate_desc));
	$orderBy_options['batchId'] = array('A_markup'=>$this->Paginator->sort('Date Uploaded', 'batchId', $paginate_desc));
	$orderBy_options['owner_id'] = array('A_markup'=>$this->Paginator->sort('Owner', 'owner_id'));
	// $orderBy_options['provider_account_id'] = array('A_markup'=>$this->Paginator->sort('Provider', 'provider_account_id'));
	$orderBy_options['caption'] = array('A_markup'=>$this->Paginator->sort('caption'));
	$orderBy_options['keyword'] = array('A_markup'=>$this->Paginator->sort('keyword'));
	$orderBy_selected = !empty($passed['sort']) ? $passed['sort'] : 'dateTaken';
	$btn_active['orderBy'] = isset($passed['sort']);
	
	$orderBy_options[$orderBy_selected]['selected'] = ' selected ';
	// reformat as select option elements
	// $needle = array('a', 'href'); $replace = array('option', 'value');
	$needle = array('<a', 'href', 'a>'); 
	$replace = array('<option', 'value', 'option>');
	
	$btn_state['filter-rating'] = isset($passed['rating']) ? 'selected' : '';
	
	if (isset($passed['batchId'])) {
		$btn_state['filter-batchId'] = 'selected';
		$filter_option['batchId'] = array('value'=>$passed['batchId'], 'label'=>date('Y-m-d h:ia',$passed['batchId']) ); 
	} else {
		$btn_state['filter-batchId'] = false;
		$filter_option['batchId'] = array('value'=>'', 'label'=>'Date Uploaded' );
	}
	
	$btn_state['filter-tag'] = (!empty($passed['Tag']) || Session::read('lookup.context.keyName')=='Tag') ? 'selected' : '';
	
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	
?>

<?php	
	/*
	 * inner block
	 */ 
	$this->Layout->blockStart('inner_DisplayOptions'); ?> 
	    	<ul class="filter inline">
	    		<li class='label'>Filter</li>
				<li class='btn rating white <?php echo $btn_state['filter-rating']; ?>' title='Click on a star to filter by minimum rating or Ctrl-click for unrated Snaps'>
					<ul class='inline'>
						<span title="click to remove this filter" class="btn remove rounded-5   <?php echo $btn_state['filter-rating'] ? '' : 'hide'; ?>" action="filter:rating">x</span>
						<li class="label" >My Rating</li>
						<li id="filter-rating-parent">
							<div class="ratingGroup">
							</div>
						</li>
					</ul>
				</li>
				<li class="btn white  <?php echo $btn_state['filter-tag']; ?>"  action="filter:tag">
					<span class="btn remove rounded-5  <?php echo $btn_state['filter-tag'] ? '' : 'hide'; ?>"  title="click to REMOVE this filter" action="filter:tag">x</span>Tag&nbsp;<input type='text' class='tag copy-paste' maxlength='40'  value='' /></li>
				<li class="btn white batch-id <?php echo $btn_state['filter-batchId']; ?>  action="filter:batch-id"">
					<select onmousedown="SNAPPI.UIHelper.action.get.filterByOptions(this);" onchange="SNAPPI.UIHelper.action.filter.batchId(this);" title='Date Uploaded'>
	             		<option value='<?php echo $filter_option['batchId']['value'];  ?>' selected='<?php echo $btn_state['filter-batchId']; ?>'><?php echo $filter_option['batchId']['label'];  ?></option>
	             	</select>
				</li>
				<li class="btn white disabled">Date Taken <a><img src="/static/img/css-gui/arrow.png" alt=""></a></li>
			</ul>
	        <ul class="sort inline inline-break right">
	        	<li class='label'>Sort</li>
	            <li class='btn white <?php if ($btn_active['orderBy']) echo "selected" ?>'>
	             	<select onchange="SNAPPI.UIHelper.nav.orderBy(this);">
	             		<?php 
							foreach ($orderBy_options as $id => $option) {
								if (isset($option['A_markup'])) $html = str_replace($needle, $replace, $option['A_markup']);
								if (!empty($option['selected'])) $html = str_replace('value', " {$option['selected']} value", $html);
								echo $html;
							}                     		
	             		?>
	             	</select>
	            </li>
	      	</ul>	
<?php $this->Layout->blockEnd();?>

		
<?php 	//debug($this->viewVars['inner_DisplayOptions_for_layout']);
	if ($isWide) { 
		$this->Layout->output($this->viewVars['inner_DisplayOptions_for_layout']);
	} else {  ?>      	
<section class="gallery-display-options container_16 hide">
	<nav class="settings cf grid_16">
		<?php $this->Layout->output($this->viewVars['inner_DisplayOptions_for_layout']); ?>
	</nav>
</section>
<?php } ?>
