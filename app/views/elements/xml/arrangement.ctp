<sn:Arrangements>
<sn:Arrangement <?php echo "W='{$row['W']}' H='{$row['H']}'"  ?> >
  <sn:Title><?php echo "{$row['Title']}" ?></sn:Title>
  <!-- <?php if (TRUE || count($row['Background'])==0) echo "<sn:Background/>";
  		else foreach($row['Background'] as $bkg ) { ?>
  <sn:Background><?php echo $bkg ?></sn:Background>
  <?php } ?>
  -->
  <sn:Spacing><?php echo "{$row['Spacing']}" ?></sn:Spacing>
  <sn:Orientation <?php echo "LandscapeCount='{$row['LandscapeCount']}' PortraitCount='{$row['PortraitCount']}'"?> />
  <?php if (count($row['Roles'])==0) echo "<sn:Roles/>";
  		else foreach($row['Roles'] as $role ) { ?>
	  <sn:Role <?php echo "Index='{$role['Index']}' 
	  					X='{$role['X']}' Y='{$role['Y']}' W='{$role['W']}' H='{$role['H']}'
	  					Theta='{$role['Theta']}' Scale='{$role['Scale']}'
						ZIndex='{$role['ZIndex']}'
						IsCast='{$role['IsCast']}'
						"   
	  			?> >
	    <sn:Prominence><?php echo $role['Prominence'] ?></sn:Prominence>
	  </sn:Role>
	<?php } ?>
</sn:Arrangement>
</sn:Arrangements>