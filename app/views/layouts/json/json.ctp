<?php
echo header('Pragma: no-cache');
echo header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
//echo header('Content-Type: application/x-json');
//echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
echo "WARNING: THIS LAYOUT HAS BEEN DEPRECATED. USE /layouts/json/default.ctp  <br>\n";
echo $content_for_layout;
?>