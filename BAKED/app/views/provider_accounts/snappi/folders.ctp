<h2>Import Photos to Snaphappi</h2>
<p>prepare photos for Snappi processing.</p>
<br /> 
<table ><th>Folder</th><th>Actions</th>
<?php 
$output = '';

foreach ($tree as $folderpath) {
	$parts = explode(DS,$folderpath);
	$link = array_pop($parts);
	$plain = implode(DS,$parts);
	$space = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	
	$output .= "<tr><td style='text-align:left;width:300px;'>";
	if ($plain) $output .= $plain.DS;
	if ($folderpath == cleanPath( Configure::read('path.local.original.fileroot'))) $folderpath='.';
	$output .= $link;
	$output .= "</td><td width=\"1000px\">";
	$actions = array();
	$actions[] = $html->link("import", "import?relpath=$folderpath"); 
	$actions[] = $html->link("create Previews", "resize_rotate?relpath=$folderpath");
	$actions[] = $html->link("autoRotate", "autoRotate?relpath=$folderpath");
	$actions[] = $html->link("removeKeywords", "removeKeywords?relpath=$folderpath"); 
	$output .= implode($space, $actions);
	$output .= "</td></tr>"; 
}

echo $output;
?>
</table>