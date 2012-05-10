<?php
	echo $this->Form->input('RenewShareLink.message',array(
		'label'=>'Request this link to be renewed. Type your message to the link owner.',
		'type'=>'textarea'
	));
	echo $this->Form->submit();

?>