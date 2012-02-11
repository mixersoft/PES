<?php  
	$passed = Configure::read('passedArgs.complete');
	
	$btn_active=array();
	/*
	 * Generate Paginator->sort() urls when sortin on field in associated models
	 */
	$this->Paginator->options['url']['plugin']='';
	// setup order by rating
	$orderBy= '0.rating'; $default = 'desc';
	$rating_markup = $this->Paginator->sort('Top Rated', $orderBy);	// this will ALWAYS BE direction:asc
	// default = 'desc';
	$isActive = isset($passed['sort']) && $passed['sort'] == $orderBy;
	if ($isActive) {
		$rating_markup = (@ifed($passed['direction'],null) == 'desc') ? $rating_markup : str_replace('asc', 'desc', $rating_markup);  
		$rating_markup = isset($passed['direction']) && $passed['direction'] == '$desc' ? $rating_markup : str_replace('asc', 'desc', $rating_markup);
	} else {
		$rating_markup = ($default=='desc') ? str_replace('asc', 'desc', $rating_markup) : $rating_markup;
	}
	
	// Sort select TAG
	$paginate_desc['url']['direction'] = 'desc';
	$orderBy_options = array();
	$orderBy_options['dateTaken'] = array('A_markup'=>$this->Paginator->sort('Date Taken', 'dateTaken'));
	$orderBy_options['0.rating'] = array('A_markup'=>$rating_markup);
	$orderBy_options['batchId'] = array('A_markup'=>$this->Paginator->sort('Date Uploaded', 'batchId', $paginate_desc));
	$orderBy_options['owner_id'] = array('A_markup'=>$this->Paginator->sort('Owner', 'owner_id'));
	$orderBy_options['provider_account_id'] = array('A_markup'=>$this->Paginator->sort('Provider', 'provider_account_id'));
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
					<span class="btn remove rounded-5  <?php echo $btn_state['filter-tag'] ? '' : 'hide'; ?>"  title="click to REMOVE this filter" action="filter:tag">x</span>
					Tag&nbsp;<input type='text' class='tag copy-paste' maxlength='40'  value='' /></li>
				<li class="btn white disabled">Date Taken <a><img src="/css/images/arrow.png" alt=""></a></li>
			</ul>
	        <ul class="sort inline inline-break right">
	        	<li class='label'>Sort</li>
	            <li class='btn white <?php if ($btn_active['orderBy']) echo "selected" ?>'>
	             	<select onchange="SNAPPI.UIHelper.nav.orderBy(this);">
	             		<?php 
							foreach ($orderBy_options as $id => $option) {
								$html = str_replace($needle, $replace, $option['A_markup']);
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
