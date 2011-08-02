<sn:Audition   
<?php 
		echo "id='snappi-audition-{$row['a']['id']}' ";
		echo "IsCast='{$local['isCast']}' ";
		if ($local['lastPerformed']) echo "LastPerformed='{$local['lastPerformed']}'";
?> 			>
    <sn:Photo <?php $flash = $row['a']['exif_Flash']==0 ? 0 : 1;
					$ts = $row[0]['ts'];
					
echo "id='snappi-photo-{$row['a']['id']}'
	DateTaken='{$row[0]['dateTaken']}' 
	TS='{$ts}' 
	W='{$row['a']['exif_ExifImageWidth']}' 
	H='{$row['a']['exif_ExifImageLength']}' 
	ExifOrientation = '{$row['a']['exif_Orientation']}'
	ExifFlash='{$flash}'
	ExifColorSpace='{$row['a']['exif_ColorSpace']}'";
if (isset($row[0]['photoset'])) echo " Photoset='{$row[0]['photoset']}'";
?> >
        <sn:Img>
            <sn:Src <?php echo " W='{$row['a']['imageWidth']}' H='{$row['a']['imageHeight']}'  AutoRender='{$local['autoRender']}' "  ?>  >
                <sn:Src><?php echo $row['a']['src']; ?></sn:Src>
<?php if (isset($row['a']['base64Src'])) echo "<sn:base64Src>{$row['a']['base64Src']}</sn:base64Src>";  ?>
<?php if (isset($row['a']['rootSrc'])) {echo "<sn:rootSrc>{$row['a']['rootSrc']}</sn:rootSrc><sn:base64RootSrc>{$row['a']['base64RootSrc']}</sn:base64RootSrc>"; } ?>
            </sn:Src>
        </sn:Img>
        <sn:Fix>
        	<sn:Rotate><?php if (!@empty($row[0]['rotate'])) echo "{$row[0]['rotate']}" ?></sn:Rotate>
			<sn:Rating><?php if (!@empty($row[0]['rating'])) echo "{$row[0]['rating']}" ?></sn:Rating>
			<sn:Scrub></sn:Scrub>
			<sn:Crops><?php
					if (!@empty($row[0]['crops'])) {
					foreach ($row[0]['crops'] as $crop) {
	echo $this->element('xml/crop', array( 'crop'=>$crop 	));
					}} 
			?></sn:Crops>
		</sn:Fix>
    </sn:Photo>
    <sn:LayoutHint>
        <sn:Rating></sn:Rating>
        <sn:Votes>
            <?php echo $local['votes'] ?>
        </sn:Votes>
        <sn:FocusCenter <?php 
			if (!@empty($row[0]['FocusCenter'])) {
				$x=$row[0]['FocusCenter']['X'];
				$y=$row[0]['FocusCenter']['Y'];
				$scale=$row[0]['FocusCenter']['Scale'];
			} else {
	if (!@empty($row['a']['exif_ExifImageWidth'])){
		$x = $row['a']['exif_ExifImageWidth']/2;
		$y = $row['a']['exif_ExifImageLength']/2;
	} else {
		$x = $row['a']['imageWidth']/2;
		$y = $row['a']['imageHeight']/2;
	}
	$scale = 2*max(array($x,$y));
			}
			echo "X='{$x}' Y='{$y}' Scale='{$scale}' ";  ?> />
		<sn:FocusVector Direction="0"  Magnitude="0"/>
    </sn:LayoutHint>
	<?php 
		if (FALSE && $row[0]['sub_groups']) {
			$tids = explode(';',$row[0]['sub_groups']);
echo "<sn:SubstitutionREF>";
			foreach ($tids as $tid) {
echo "<sn:SubstitutionREF idref='snappi-substitution-{$tid}' />";
			}			
echo "</sn:SubstitutionREF>";
		}  else {
echo "<sn:SubstitutionREF />";
		}
	?>
	<sn:Clusters />
<?php if (@empty($row[0]['tags'])) { echo "<sn:Tags />"; } 
else { ?>	
	<sn:Tags <?php if (!@empty($row[0]['tags'])) echo "value='{$row[0]['tags']}'"; ?>  >
		<?php 
			$tids = explode(';',$row[0]['tag_ids']);
			foreach ($tids as $tid) {
echo "<sn:TagREF  idref='snappi-tag-{$tid}' />";
			}
		?>
    </sn:Tags>
<?php   }  ?>	
	<sn:Credits />
</sn:Audition>
