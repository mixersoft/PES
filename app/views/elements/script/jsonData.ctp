<script type="text/javascript">
	if (typeof PAGE == 'undefined') PAGE = {};
	PAGE.jsonData = PAGE.jsonData || {}; 
	<?php 
		Configure::write('debug', 0);
		foreach ($this->viewVars['jsonData'] as $key=>$value){
			$json = json_encode($this->viewVars['jsonData'][$key]);
			echo "PAGE.jsonData['{$key}']={$json};\n"; 
			unset($this->viewVars['jsonData'][$key]);
		}
	?>
	var check;
</script>