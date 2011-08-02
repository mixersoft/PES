<span class='context '>
<?php 
	$controllerAttr = Configure::read('controller');
	$fromKeyName =  $controllerAttr['xhrFrom']['keyName'];
	$context = Session::read('lookup.context');
//debug($context);	
	if ( $context['keyName']==null 
			|| $controllerAttr['keyName'] == $context['keyName'] 
			|| in_array($controllerAttr['keyName'], array('Photo'))
			|| $fromKeyName == $context['keyName'] 
	) {
		// show nothing
	} else { 
//		debug($context);
		$SHORT = 12;
		$context_uuid = $context['uuid'];
		if ($context_uuid && strpos('all', $controllerAttr['here'])===false) {
			$context_label = @$this->Text->truncate($context['label'], $SHORT); 

			$next = $this->passedArgs;
			if ($this->action=='fragment' && !@empty($next['a'])) {
				$next['action'] = $next['a'];
				unset($next['a']);
				unset($next['e']);
			}
			$next['context'] = 'remove';
			$next['plugin'] = '';
			$label = Session::read("lookup.trail.{$controllerAttr['keyName']}.label");
			switch ($context['keyName']) {	// see Configure::read('lookup.keyName')
				case "person":
					$caption = "Showing only items shared by %s";
					$linkTo = array('controller'=>'users', 'action'=>'home', $context_uuid);
					$changeTo = array('caption'=> "remove", 'label'=> $label);
	 				break;
				case "group":
				case "event":		
						$caption = "Showing only items in {$context['keyName']} %s";
						$linkTo = array('controller'=>'groups', 'action'=>'home', $context_uuid);
						$changeTo = array('caption'=> "remove", 'label'=> $label);
					break;
				case "tag":
						$caption = "Showing only items tagged %s";
						$linkTo = array( 'controller'=>'tags', 'action'=>'home', $context_uuid);
						$changeTo = array('caption'=> "remove", 'label'=> $label);
						break;
				default:
					break;	
			}
			$caption = sprintf($caption, $this->Html->link($context_label, $linkTo));
	?>
	<span class='filter'><span class='remove'> <?php  echo $this->Html->link('x', $next, array('title'=>'click here to REMOVE this filter.')); ?></span><?php echo $caption; ?></span>
	<?php } // end else    if ($context_uuid) { ?>
<?php }; ?>	
</span>