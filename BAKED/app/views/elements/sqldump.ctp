<?php 
if (Configure::read('Config.os') == 'win') {
	Configure::write('debug',2);
	echo $this->element('sql_dump');	
} 
?>
