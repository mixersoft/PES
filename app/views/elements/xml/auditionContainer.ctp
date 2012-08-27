 <?php foreach ($containers as $id=>$container) {
 		$count=count($container['Auditions']);
	?>
 <?php $lowercase = strtolower($elementName); 
 	$typeAttr = (!@empty($container['Type'])) ? "Type='{$container['Type']}'" : "";
	$optional = (!@empty($container['total'])) ? " Total='{$container['total']}'" : "";  // for photoset groupings
	$optional .= (!@empty($container['perpage'])) ? " Perpage='{$container['perpage']}'" : "";
	$optional .= (!@empty($container['page'])) ? " Page='{$container['page']}'" : "";
	$optional .= (!@empty($container['pages'])) ? " Pages='{$container['pages']}'" : "";
 	echo "<sn:{$elementName} id='snappi-{$lowercase}-{$id}'  Label='{$container['Label']}' {$typeAttr} {$optional} >" ?>
	 <?php foreach ($container['Auditions'] as $row) { ?>
	  <sn:AuditionREF <?php echo "idref='snappi-audition-{$row}' "; ?> />
	<?php  }  ?>
 <?php echo "</sn:{$elementName}>" ?> 
<?php  }  ?>