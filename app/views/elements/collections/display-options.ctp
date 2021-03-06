<?php  
	$passed = Configure::read('passedArgs.complete');
	/*
	 * Generate Paginator->sort() urls when sortin on field in associated models
	 */
	$this->Paginator->options['url']['plugin']='';
	$btn_active=array();
	// Sort select TAG
	$paginate_desc['url']['direction'] = 'desc';
	$orderBy_options = array();
	$orderBy_options['title'] = array('A_markup'=>$this->Paginator->sort('Title', 'title'));
	$orderBy_options['owner_id'] = array('A_markup'=>$this->Paginator->sort('Owner', 'owner_id'));
	$orderBy_options['created'] = array('A_markup'=>$this->Paginator->sort('Most Recent', 'created', $paginate_desc));
	$orderBy_options['assets_collection_count'] = array('A_markup'=>$this->Paginator->sort('Photos', 'assets_collection_count', $paginate_desc ));
	$orderBy_options['collections_group_count'] = array('A_markup'=>$this->Paginator->sort('Circles', 'collections_group_count', $paginate_desc));
	$orderBy_selected = !empty($passed['sort']) ? $passed['sort'] : 'title';
	$btn_active['orderBy'] = isset($passed['sort']);
	$orderBy_options[$orderBy_selected]['selected'] = ' selected ';
	
	$filterBy_options = array();
	$next = Router::url(array_diff_key($passed,array('filter-type'=>1)));
	$filterBy_options['All Stories'] = array('A_markup'=>"<a href='{$next}'>All Stories</a>");
	$next = Router::url(array('filter-type'=>'Group')+ $passed);
	$filterBy_options['Group'] = array('A_markup'=>"<a href='{$next}'>Groups</a>");
	$next = Router::url(array('filter-type'=>'Event')+ $passed);
	$filterBy_options['Event'] = array('A_markup'=>"<a href='{$next}'>Events</a>");
	$next = Router::url(array('filter-type'=>'Wedding')+ $passed);
	$filterBy_options['Wedding'] = array('A_markup'=>"<a href='{$next}'>Weddings</a>");
	$filterBy_selected_item = !empty($passed['filter-type']) ? $passed['filter-type'] : 'All Stories';
	$btn_active['filter-type'] = $filterBy_selected_item !== 'All Stories';
	$filterBy_options[$filterBy_selected_item]['selected'] = ' selected ';	
	
	$btn_active['filter-me'] = !empty($passed['filter-me']); 
	
	// reformat as select option elements
	// $needle = array('a', 'href'); $replace = array('option', 'value');
	$needle = array('<a', 'href', 'a>'); 
	$replace = array('<option', 'value', 'option>');
	
	$isWide = !empty($this->params['named']['wide']);		// fluid layout
	
?>

<?php	
	/*
	 * inner block
	 */ 
	$this->Layout->blockStart('inner_DisplayOptions'); ?> 
	    	<ul class="filter inline">
	    		<li class='label'>Filter</li>
	    		<li class="btn white <?php if ($btn_active['filter-me']) echo "selected" ?>" onclick="SNAPPI.UIHelper.groups.myGroups(this);">My Stories</li>
	    		<li class="btn white <?php if ($btn_active['filter-type']) echo "selected" ?>">Type
	             	<select onchange="SNAPPI.UIHelper.nav.goto(this);">
	             		<?php 
							foreach ($filterBy_options as $id => $option) {
								$html = str_replace($needle, $replace, $option['A_markup']);
								if (!empty($option['selected'])) $html = str_replace('value', " {$option['selected']} value", $html);
								echo $html;
							}                     		
	             		?>
	             	</select>	    			
	    		</li>
				<li class="btn white">Date Taken<span class="menu-open"></span></li>
			</ul>
	        <ul class="sort inline inline-break right">
	        	<li class='label'>Sort</li>
	            <li class='btn white <?php if ($btn_active['orderBy']) echo "selected" ?>'>
	             	<select onchange="SNAPPI.UIHelper.nav.goto(this);">
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
	<nav class="settings cf grid_16 wrapped">
		<?php $this->Layout->output($this->viewVars['inner_DisplayOptions_for_layout']); ?>
	</nav>
</section>
<?php } ?>
