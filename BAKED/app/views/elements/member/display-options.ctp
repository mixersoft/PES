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
	$orderBy_options['username'] = array('A_markup'=>$this->Paginator->sort('Name', 'username'));
	$orderBy_options['last_login'] = array('A_markup'=>$this->Paginator->sort('Last Visit', 'last_login'));
	$orderBy_options['created'] = array('A_markup'=>$this->Paginator->sort('Member Since', 'created', $paginate_desc));
	$orderBy_options['asset_count'] = array('A_markup'=>$this->Paginator->sort('Photos', 'asset_count', $paginate_desc ));
	$orderBy_options['groups_user_count'] = array('A_markup'=>$this->Paginator->sort('Circles', 'groups_user_count', $paginate_desc));
	$orderBy_selected = !empty($passed['sort']) ? $passed['sort'] : 'username';
	$btn_active['orderBy'] = isset($passed['sort']);
	$orderBy_options[$orderBy_selected]['selected'] = ' selected ';
	
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
				<li class="btn">Date Taken<span class="menu-open"></span></li>
			</ul>
	        <ul class="sort inline right">
	        	<li class='label'>Sort</li>
	            <li class='btn <?php if ($btn_active['orderBy']) echo "selected" ?>'>
	             	<select onchange="PAGE.goto(this);">
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
