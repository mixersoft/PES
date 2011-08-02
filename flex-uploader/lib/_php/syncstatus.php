<?php 
	//$lastsync = $_POST['lastupdate']; //lastupdate timestamp when this desktop last sync with server
	//$desktop_id = $_POST['desktop_id'];
	

	$arr = array();
	$arr[0] = array();
	$arr[1] = array();
	$arr[2] = array();
	$arr[0]['id'] = 'ACC9D55A-5AF6-4FB3-822B-F271D747E835';
	$arr[0]['rating'] = 5;
	
	$arr[1]['id'] = '558C030B-C4A1-4393-AB2B-CB1C325D75AB';
	$arr[1]['rating'] = 4;
	
	$arr[2]['id'] = '4CE6039B-BFDC-4F05-BCCD-A4901BEEFF17';
	$arr[2]['rating'] = 3;

	/*
		$arr is the dummy data  - this photo_id is exists in our local db and we are sending them back as some one change it at server side
		
		you fetch your changed record here from db and
		send it to as serialize array json object 
	
	*/
	
	echo json_encode($arr);
?>