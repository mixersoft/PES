<?php  
	$passed = Configure::read('passedArgs');
	/*
	 * Generate Paginator->sort() urls when sortin on field in associated models
	 */
	// setup order by rating
	$orderBy= '0.rating'; $default = 'desc';
	$rating_markup = $this->Paginator->sort('Rating', $orderBy);	// this will ALWAYS BE direction:asc
	// default = 'desc';
	$isActive = isset($passed['sort']) && $passed['sort'] == $orderBy;
	if ($isActive) {
		$rating_markup = (@ifed($passed['direction'],null) == 'desc') ? $rating_markup : str_replace('asc', 'desc', $rating_markup);  
		$rating_markup = isset($passed['direction']) && $passed['direction'] == '$desc' ? $rating_markup : str_replace('asc', 'desc', $rating_markup);
	} else {
		$rating_markup = ($default=='desc') ? str_replace('asc', 'desc', $rating_markup) : $rating_markup;
	}
	
	// Sort select TAG
	$orderBy_options = array();
	$orderBy_options['dateTaken'] = array('A_markup'=>$this->Paginator->sort('Date Taken', 'dateTaken'));
	$orderBy_options['0.rating'] = array('A_markup'=>$rating_markup);
	$orderBy_options['batchId'] = array('A_markup'=>$this->Paginator->sort('Date Uploaded', 'batchId'));
	$orderBy_options['owner_id'] = array('A_markup'=>$this->Paginator->sort('Owner', 'owner_id'));
	$orderBy_options['provider_account_id'] = array('A_markup'=>$this->Paginator->sort('Provider', 'provider_account_id'));
	$orderBy_options['caption'] = array('A_markup'=>$this->Paginator->sort('caption'));
	$orderBy_options['keyword'] = array('A_markup'=>$this->Paginator->sort('keyword'));
	$orderBy_selected = !empty($passed['sort']) ? $passed['sort'] : 'dateTaken';
	
	$orderBy_options[$orderBy_selected]['selected'] = ' selected ';
	// reformat as select option elements
	// $needle = array('a', 'href'); $replace = array('option', 'value');
	$needle = array('<a', 'href', 'a>'); 
	$replace = array('<option', 'value', 'option>');
	
	$ratingGroup_class = "ratingGroup";
	$ratingRemoved_class = 'remove';
	if (!empty($passed['rating'])) {
		$ratingGroup_class.= " r{$passed['rating']}";	// move to JS
		$ratingRemoved_href = $passed;
		unset($ratingRemoved_href['rating']);
		unset($ratingRemoved_href['page']);
		$ratingRemoved_href = Router::url($ratingRemoved_href);
	} else {
		$ratingRemoved_class .= ' hide'; 				// move to JS
		$ratingRemoved_href = $this->here;
	}
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	
?>

<?php	
	/*
	 * inner block
	 */ 
	$this->Layout->blockStart('inner_DisplayOptions'); ?> 
	    	<ul class="filter inline">
	    		<li class='label'>Filter</li>
				<li class='rating btn'>
					<ul>
						<li class='<?php echo $ratingRemoved_class;  ?>'>
							<a title='click here to REMOVE this filter' href='<?php echo $ratingRemoved_href ?>' >x</a>
						</li>	
						<li>My Rating</li>
						<li id="filter-rating-parent">
							<div class="<?php echo $ratingGroup_class;  ?>">
							</div>
							</li>
					</ul>
				</li>
				<li class="btn">Date Taken <a><img src="/css/images/arrow-down.png" alt=""></a></li>
			</ul>
	        <ul class="sort inline right">
	        	<li class='label'>Show</li>
	            <li class='btn'>
	            	Sort
	             	<select onchange="PAGE.orderBy(this);">
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
	<nav class="settings grid_16 wrapped">
		<?php $this->Layout->output($this->viewVars['inner_DisplayOptions_for_layout']); ?>
	</nav>
</section>
<?php } ?>
