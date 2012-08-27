<sn:Crop <?php if (!@empty($crop['Label'])) echo "Label='{$crop['Label']}' "; 	if (!@empty($crop['Format'])) echo "Format='{$crop['Format']}'"; ?> >
	<sn:Rect <?php if (count($crop)) { echo "X='{$crop['X']}' Y='{$crop['Y']}' W='{$crop['W']}' H='{$crop['H']}' Scale='{$crop['Scale']}'";} ?> ></sn:Rect>
</sn:Crop>
