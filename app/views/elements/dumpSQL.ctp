<?php 
// if (Configure::read('Config.os') == 'win') {
	// Configure::write('debug',2);
// } 
// debug(Configure::read('debug'));
if (Configure::read('debug') == 2) {
	echo "<br /><br />\n";
	echo $this->element('sql_dump');
}	
?>

