<?php
	header('Content-type: text/html; charset=UTF-8');

	/* Note: This thumbnail creation script requires the GD PHP Extension.  
		If GD is not installed correctly PHP does not render this page correctly
		and SWFUpload will get "stuck" never calling uploadSuccess or uploadError
	 */

	// Get the session Id passed from SWFUpload. We have to do this to work-around the Flash Player Cookie Bug
	if (isset($_POST["PHPSESSID"])) {
		session_id($_POST["PHPSESSID"]);
	}

	session_start();

	// Check the upload
	if (!isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0) {
		header("HTTP/1.1 500 Internal Server Error");
		echo "invalid upload";
		exit(0);
	}
    $name = $_FILES["Filedata"]["name"];
    $title = '';
    $type = $_FILES["Filedata"]["type"];
    $file_url = $_FILES["Filedata"]["tmp_name"];
    $size = ceil($_FILES["Filedata"]["size"]/1024) . 'Kb';
    $dir  = realpath(dirname(__FILE__).'/uploads/');
if( move_uploaded_file($file_url, $dir.'/'.$name)){
    $file_url = '/tmp/'.$name;
}else{
    $file_url = 'error when move';
}
    
    echo '{success:true,info:"Uploaded Successfully"}';
?>



