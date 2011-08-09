<?php 
	if (isset($data['User'])) echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
	
?>
<h2>Snaphappi Downloads</h2>
<?php  echo $this->element('downloads');  ?>



