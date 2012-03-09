<script type="text/javascript">
	if (typeof PAGE == 'undefined') PAGE = {};
	PAGE.Cookie = PAGE.Cookie || {}; 
	<?php 
		Configure::write('debug', 0);
		foreach ($this->viewVars['cData'] as $key=>$value){
			if (is_array($value)) $value = json_encode($value);
			echo "PAGE.Cookie['{$key}']={$value};\n"; 
			unset($this->viewVars['cData'][$key]);
		}
	?>
	var check;
</script>