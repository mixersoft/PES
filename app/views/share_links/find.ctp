<?php if (empty($shareLinks)): ?>
<h2>No links for that query</h2>
<?php else: ?>
<h2>Links for the query</h2>
<?php 

foreach ($shareLinks as &$row) {
	$row['link'] = $this->Html->link(array('action'=>'view', $row['ShareLink']['secret_key']), null, array('target'=>"_blank"));
}
pr($shareLinks); 

?>
<?php endif; ?>