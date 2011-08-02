<div id='groups' class='preview-groups placeholder'>
	<h3>Memberships</h3>
	<?php 
	switch($this->name) {
		case "Groups":
			$options = array('groups'=>(array)$groups['Group'], 'labelField'=>'src_thumbnail');
			break;
		case "Tags":
			$options = array('groups'=>(array)$data['Group']);
			break;
		case "Users":
			$options = array('groups'=>(array)$data['Membership']);
			break;
		case "Assets":
			$options = array('groups'=>(array)$data['Group']);
			break;		
	}
	$options['isPreview']=true;
	echo $this->element('/group/roll', $options);
	?>
</div>