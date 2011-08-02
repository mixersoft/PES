<sn:Catalog xmlns:sn='snaphappi.com' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='<?php echo $local['schemaLocation'];  ?>'>
	<sn:Id><?php echo time() ?></sn:Id>
	<sn:Owner><?php echo $catalog['format'] ?></sn:Owner>
	<?php foreach ($catalog['arrangements'] as $arrangement) { 
				// debug($arrangement);
				echo $this->element('xml/arrangement'
					, array(
						'row'=>$arrangement['xmlAsArray']
					));
				}
			?>
</sn:Catalog>
