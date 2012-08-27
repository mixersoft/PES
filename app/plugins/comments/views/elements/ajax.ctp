<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<?php if (isset($messageTxt)): ?>
<div class="message">
	<?php echo $messageTxt; ?>
</div>
<?php endif ;?>
<?php 
	if (isset($ajaxMode)): 
		if (!empty($redirect)):
			if (isset($redirect['#'])) {
				unset($redirect['#']);
			}
		
			$url = Router::parse($this->here);
			$url = array_merge($url, $url['named'], $url['pass']);
			unset($url['named']);
			unset($url['pass']);
			if (isset($url['comment'])) {
				unset($url['comment']);
			}
			
			echo $this->Html->scriptBlock('setTimeout(function () {' . $js->request(Router::url($url), array('method' => 'get', 'update' => $commentWidget->globalParams['target'])) . '}, 1500);');
		else:
			echo $commentWidget->display();
		endif;
	else:
		echo $commentWidget->display();
	endif;
?>
