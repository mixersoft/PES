<?php
echo header('Pragma: no-cache');
echo header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
//echo header('Content-Type: application/json');
//echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

echo $content_for_layout;
?>