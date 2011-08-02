<?php
class LabelHelper extends AppHelper {
	function username($class, $format, $data, $limit){
		switch($class) {
			case 'Thumbnail':
				$label = $this->Html->link($username, 
						array(
							'controller'=>'users', 
							'action'=>'home', 
							$data[$labelField]), 
						$options);
				return $label;
		}
		
	}
	function get($class, $data){
		
	}
}
?>