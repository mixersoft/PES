<?php
class  StoryController extends AppController {
	public $layout = '';
	public $uses = null;

	// sample arrangements as json string, us, /appcache/story/show?perpage=16&pg=1&rating=3
	public $STATIC_JSON_cc = '{"profile":null,"castingCall":{"CastingCall":{"ID":1333617624,"Timestamp":1333617624,"ProviderName":"snappi","Auditions":{"Audition":[{"id":"67B9ED53-4E6B-4165-A3DA-C595626960CC","Photo":{"id":"67B9ED53-4E6B-4165-A3DA-C595626960CC","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"5.00","Rotate":"1","Scrub":"","Score":"5.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage7\/67B9ED53-4E6B-4165-A3DA-C595626960CC.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-22 14:35:13","TS":1327242913,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"LesArc 2012 005","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 005.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:46:19"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-eeac-4771-a7f4-11a0f67883f5","Photo":{"id":"4bbb3976-eeac-4771-a7f4-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.75","Rotate":"1","Scrub":"","Score":"4.8","Votes":"2"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage3\/4bbb3976-eeac-4771-a7f4-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-09 17:56:30","TS":1252518990,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010595","origSrc":"\/venice\/P1010595.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":"4e591d29-2f44-4fc7-b0f6-54ae0a803b63","Shot":{"id":"4e591d29-2f44-4fc7-b0f6-54ae0a803b63","count":"3"},"Tags":[],"Clusters":"","Credits":""},{"id":"E432F73D-19A3-4867-A1D3-3425CA2F09CE","Photo":{"id":"E432F73D-19A3-4867-A1D3-3425CA2F09CE","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage7\/E432F73D-19A3-4867-A1D3-3425CA2F09CE.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-23 11:58:56","TS":1327319936,"ExifColorSpace":1,"ExifFlash":1,"ExifOrientation":1,"Caption":"LesArc 2012 030","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 030.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:47:11"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-528c-4912-ad0d-11a0f67883f5","Photo":{"id":"4bbb3976-528c-4912-ad0d-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage0\/4bbb3976-528c-4912-ad0d-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-10 12:47:01","TS":1252586821,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010667","origSrc":"\/venice\/P1010667.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-8d94-4987-8c21-11a0f67883f5","Photo":{"id":"4bbb3976-8d94-4987-8c21-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage2\/4bbb3976-8d94-4987-8c21-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-10 19:21:27","TS":1252610487,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010726","origSrc":"\/venice\/P1010726.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:03"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-6f00-487b-a87f-11a0f67883f5","Photo":{"id":"4bbb3976-6f00-487b-a87f-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage2\/4bbb3976-6f00-487b-a87f-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-11 10:16:16","TS":1252664176,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010774","origSrc":"\/venice\/P1010774.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:03"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-754c-4d59-91b5-11a0f67883f5","Photo":{"id":"4bbb3976-754c-4d59-91b5-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage3\/4bbb3976-754c-4d59-91b5-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-11 12:34:27","TS":1252672467,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010811","origSrc":"\/venice\/P1010811.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:03"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-89e0-4580-9ab3-11a0f67883f5","Photo":{"id":"4bbb3976-89e0-4580-9ab3-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"4.00","Rotate":"1","Scrub":"","Score":"4.0","Votes":"1"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage2\/4bbb3976-89e0-4580-9ab3-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-11 18:35:41","TS":1252694141,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010835","origSrc":"\/venice\/P1010835.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:04"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-32d0-4727-87d9-11a0f67883f5","Photo":{"id":"4bbb3976-32d0-4727-87d9-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"3.50","Rotate":"1","Scrub":"","Score":"3.5","Votes":"2"},"Img":{"Src":{"W":640,"H":428,"rootSrc":"stage6\/4bbb3976-32d0-4727-87d9-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-09 19:02:14","TS":1252522934,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010614","origSrc":"\/venice\/P1010614.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":"4de449d2-ad14-4847-a435-0494f67883f5","Shot":{"id":"4de449d2-ad14-4847-a435-0494f67883f5","count":"2"},"Tags":[],"Clusters":"","Credits":""},{"id":"4bbb3976-b76c-4907-a195-11a0f67883f5","Photo":{"id":"4bbb3976-b76c-4907-a195-11a0f67883f5","W":4000,"H":2672,"Fix":{"Crops":"","Rating":"3.20","Rotate":"1","Scrub":"","Score":"3.2","Votes":"5"},"Img":{"Src":{"W":428,"H":640,"rootSrc":"stage6\/4bbb3976-b76c-4907-a195-11a0f67883f5.jpg","Orientation":1}},"isOwner":false,"DateTaken":"2009-09-09 18:01:42","TS":1252519302,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"P1010598","origSrc":"\/venice\/P1010598.JPG","CameraId":"","BatchId":"1270599803","Keyword":"","Created":"2010-04-06 14:39:02"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1336},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"5B57B6BC-591F-482F-831F-5FD360239592","Photo":{"id":"5B57B6BC-591F-482F-831F-5FD360239592","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage2\/5B57B6BC-591F-482F-831F-5FD360239592.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-25 10:56:47","TS":1327489007,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"LesArc 2012 071","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 071.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:48:40"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"1411E126-ED58-4284-B223-DD9A64D0FF83","Photo":{"id":"1411E126-ED58-4284-B223-DD9A64D0FF83","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage4\/1411E126-ED58-4284-B223-DD9A64D0FF83.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-25 11:22:41","TS":1327490561,"ExifColorSpace":1,"ExifFlash":1,"ExifOrientation":1,"Caption":"LesArc 2012 075","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 075.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:48:48"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"2FD1EC63-FF30-4785-9672-9A010481A583","Photo":{"id":"2FD1EC63-FF30-4785-9672-9A010481A583","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage4\/2FD1EC63-FF30-4785-9672-9A010481A583.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-23 14:54:53","TS":1327330493,"ExifColorSpace":1,"ExifFlash":1,"ExifOrientation":1,"Caption":"LesArc 2012 037","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 037.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:47:25"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"38C17C1B-1477-4479-971E-956331B45515","Photo":{"id":"38C17C1B-1477-4479-971E-956331B45515","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage2\/38C17C1B-1477-4479-971E-956331B45515.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-23 11:37:01","TS":1327318621,"ExifColorSpace":1,"ExifFlash":0,"ExifOrientation":1,"Caption":"LesArc 2012 027","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 027.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:47:04"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"156097A1-443C-4FED-9307-F8B48038DA2A","Photo":{"id":"156097A1-443C-4FED-9307-F8B48038DA2A","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage4\/156097A1-443C-4FED-9307-F8B48038DA2A.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-23 11:37:27","TS":1327318647,"ExifColorSpace":1,"ExifFlash":1,"ExifOrientation":1,"Caption":"LesArc 2012 028","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 028.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:47:06"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""},{"id":"681F4E8E-E49C-4E46-AD89-19EF1CE3C7C2","Photo":{"id":"681F4E8E-E49C-4E46-AD89-19EF1CE3C7C2","W":4000,"H":3000,"Fix":{"Crops":"","Rating":"3.00","Rotate":"1","Scrub":"","Score":"3.0","Votes":"1"},"Img":{"Src":{"W":640,"H":480,"rootSrc":"stage1\/681F4E8E-E49C-4E46-AD89-19EF1CE3C7C2.jpg","Orientation":1,"isRGB":true}},"isOwner":false,"DateTaken":"2012-01-23 12:52:34","TS":1327323154,"ExifColorSpace":1,"ExifFlash":1,"ExifOrientation":1,"Caption":"LesArc 2012 031","origSrc":"C:\/Users\/Public\/Pictures\/2012\/Milan 2012\/LesArc 2012 031.JPG","CameraId":"","BatchId":"1328211968","Keyword":"","Created":"2012-02-02 03:47:13"},"LayoutHint":{"FocusCenter":{"Scale":4000,"X":2000,"Y":1500},"FocusVector":{"Direction":0,"Magnitude":0}},"IsCast":false,"lastPerformed":null,"SubstitutionREF":null,"Shot":{"id":null,"count":null},"Tags":[],"Clusters":"","Credits":""}],"Bestshot":[],"Total":44,"Perpage":16,"Pages":3,"Page":1,"Baseurl":"\/svc\/STAGING\/","ShotType":"Usershot"},"Request":"\/photos\/all\/rating:3\/page:1\/perpage:16\/.json","GroupAsShotPerm":null,"ShowHidden":false},"lookups":null},"filter":[{"class":"Rating","label":"at least 3","value":"3","removeHref":"\/photos\/all\/page:1\/perpage:16"}]}';
	public $STATIC_JSON_arrangements = array(
		'7wide' => '{"H":7.03125,"W":12.5,"Roles":[{"H":0.69164265129683,"W":0.51058623646998,"X":0.48941376353002,"Y":0},{"H":0.59114139693356,"W":0.48941376353002,"X":0,"Y":0.40885860306644},{"H":0.40885860306644,"W":0.33849943298906,"X":0.15091433054096,"Y":0},{"H":0.30835734870317,"W":0.25529311823499,"X":0.74470688176501,"Y":0.69164265129683},{"H":0.30835734870317,"W":0.25529311823499,"X":0.48941376353002,"Y":0.69164265129683},{"H":0.20442930153322,"W":0.15091433054096,"X":0,"Y":0.20442930153322},{"H":0.20442930153322,"W":0.15091433054096,"X":0,"Y":0}],"way":"(((h-h)|h)-h)|(h-(h|h)), init: v|v","quality":6.6,"Scale":72}', 
		'4wide' => '{"H":12.5,"W":10.158150851582,"Roles":[{"H":0.60948905109489,"W":1,"X":0,"Y":0},{"H":0.39051094890511,"W":0.64071856287425,"X":0.35928143712575,"Y":0.60948905109489},{"H":0.19525547445255,"W":0.35928143712575,"X":0,"Y":0.60948905109489},{"H":0.19525547445255,"W":0.35928143712575,"X":0,"Y":0.80474452554745}],"way":"h-((h-h)|h), init: h-h","quality":6.6,"Scale":72}', 
		'3wide' => '{"H":12.5,"W":12.227638772927,"Roles":[{"H":0.65417867435159,"W":1,"X":0,"Y":0.34582132564841},{"H":0.34582132564841,"W":0.52863436123348,"X":0.47136563876652,"Y":0},{"H":0.34582132564841,"W":0.47136563876652,"X":0,"Y":0}],"way":"(h|h)-h, init: h-h","quality":8.1,"Scale":72}', 
	);

	/*
	 * extract key properties from photos
	 */
	function __getPhotos($photos, $baseurl, $format = null) {
		$output = array();
		foreach($photos as $photo) {
			$p = array();
			$p['id'] = $photo['id'];
			$p['caption'] = $photo['Photo']['Caption'];
			$p['unixtime'] = $photo['Photo']['TS'];
			$p['dateTaken'] = $photo['Photo']['DateTaken'];
			$p['rating'] = $photo['Photo']['Fix']['Rating'];
			$p['width'] = $photo['Photo']['Img']['Src']['W'];
			$p['height'] = $photo['Photo']['Img']['Src']['H'];
			$p['src'] = $baseurl . $photo['Photo']['Img']['Src']['rootSrc'];

			if($format == 'landscape') {	// only use wide photos, for static arrangement
				if($p['width'] < $p['height'])
					continue ;
			}

			$output[] = $p;
		}
		//	sort($output, );
		return $output;
	}

	function __getPhotoById($photos, $id) {
		foreach($photos as $photo) {
			if($photo['id'] == $id)
				return $photo;
		}
		return false;
	}

	/*
	 * sort by Rating DESC, then unixtime ASC
	 */
	function __sortPhotos($photos, $count = 24) {
		// Obtain a list of columns
		foreach($photos as $key => $row) {
			$rating[$key] = $row['rating'];
			$time[$key] = $row['unixtime'];
		}
		array_multisort($rating, SORT_DESC, $time, SORT_ASC, $photos);
		if($count)
			$photos = array_slice($photos, 0, $count);
		return $photos;
	}

	function __getArrangementFromPOST($photos) {
		$postData = array();
		foreach($photos as $row) {
			$post['id'] = $row['id'];
			// $post['Caption'] = $row['caption'];
			$post['TS'] = $row['unixtime'];
			// $post['DateTaken'] = $row['dateTaken'];
			$post['Rating'] = $row['rating'];
			$post['W'] = $row['width'];
			$post['H'] = $row['height'];
			$postData[] = $post;
		};
		$auditionsAsJson = json_encode(array('Audition' => $postData));
		$count = count($postData);
		$url = "http://dev.snaphappi.com/pagemaker/arrangement/.json?data[role_count]={$count}&forcexhr=1&debug=0&data[CastingCall][Auditions]={$auditionsAsJson}";
		$rawJson = file_get_contents($url);
		$json = json_decode($rawJson, true);
		$json = $json['response'];
		$arrangement = $json['arrangement'];
		return $arrangement;
	}

	function __scaleArrangement(&$arrangement) {
		$scale = $arrangement['Scale'];
		$arrangement['H'] *= $scale;
		$arrangement['W'] *= $scale;
		foreach($arrangement['Roles'] as & $r) {
			$r['X'] *= $arrangement['W'];
			$r['Y'] *= $arrangement['H'];
			$r['W'] *= $arrangement['W'];
			$r['H'] *= $arrangement['H'];
		}
	}

	function __sortRoles($arrangement) {
		$roles = $arrangement['Roles'];
		// Obtain a list of columns
		foreach($roles as $key => $row) {
			$area[$key] = $row['H'] * $row['W'];
			$top[$key] = $row['Y'];
			$left[$key] = $row['Y'];
		}
		array_multisort($area, SORT_DESC, $top, SORT_ASC, $left, SORT_ASC, $roles);
		$arrangement['Roles'] = $roles;
		return $arrangement;
	}

	function __exportMontage($arrangement, $sortedPhotos) {
		$arrangementTemplate = "<div style='background-color: lightgray; margin: 2px auto; height: %fpx; width: %fpx;' class='pageGallery'>";
		$photoTemplate = "<img title='%s/5 : %s : %s' src='%s' style='height: %fpx; width: %fpx; left: %fpx; top: %fpx; position: absolute; border: 3px solid lightgray; cursor: pointer;'>";
		$role_count = count($arrangement['Roles']);

		$outputHTML = sprintf($arrangementTemplate, $arrangement['H'], $arrangement['W']);
		for($i = 0; $i < $role_count; $i++) {
			$r = $arrangement['Roles'][$i];
			if(isset($r['photo_id'])) {
				$p = $this->__getPhotoById($sortedPhotos, $r['photo_id']);
				if($p == false)
					break;
			} else {
				if(!isset($sortedPhotos[$i]))
					break;
				$p = $sortedPhotos[$i];
			}
			$outputHTML .= sprintf($photoTemplate, $p['rating'], $p['dateTaken'], $p['caption'], $p['src'], $r['H'], $r['W'], $r['X'], $r['Y']);
		}
		$outputHTML .= '</div>';
		return $outputHTML;
	}
	
	function show($perpage=16, $page=1, $rating=1, $layout='static' ) {
		$this->layout = false;
		/*****************************************************************
		 * Get input photos from JSON request to LIVE server
		 * 
		 * 
		 * request params
		 */
		if (isset($_REQUEST['pg'])) $page = $_REQUEST['pg'];
		if (isset($_REQUEST['perpage'])) $perpage = $_REQUEST['perpage'];
		if (isset($_REQUEST['rating'])) $rating = $_REQUEST['rating'];
		
		/*
		 * get JSON from LIVE server
		 */
		$host = "dev.snaphappi.com";
		// $host = "git3:88";
		
		/****************************************************
	 	 * output
	 	 */
	 	switch($layout) {	// use sample/static arrangements	
	 		case 'dynamic':
				/*****************************************************************
				 * Get input photos from JSON request to LIVE server
				 * 
				 * 
				 * request params
				 */
				if (isset($_REQUEST['pg'])) $page = $_REQUEST['pg'];
				if (isset($_REQUEST['perpage'])) $perpage = $_REQUEST['perpage'];
				if (isset($_REQUEST['rating'])) $rating = $_REQUEST['rating'];
				$COUNT 		= 5;		// count of photos to use in the arrangement, taken from the final, sorted array
				
				/*
				 * get JSON from LIVE server
				 */
				$url = "http://dev.snaphappi.com/photos/all/rating:{$rating}/page:{$page}/perpage:{$perpage}/.json?debug=0";
				$rawJson = file_get_contents($url);
				$json = json_decode($rawJson, true);
				$json = $json['response'];
				$photos = $json['castingCall']['CastingCall']['Auditions']['Audition'];
				$baseurl = 'http://dev.snaphappi.com'.$json['castingCall']['CastingCall']['Auditions']['Baseurl'];
				/**
				 * prepare output
				 */
		 		$layoutPhotos = $this->__sortPhotos($this->__getPhotos($photos, $baseurl),3);
				if ($single_page = 1) {
					$arrangement = $this->__getArrangementFromPOST($layoutPhotos);
			 		$this->__scaleArrangement($arrangement);
					$montage[] = $this->__exportMontage($arrangement, $layoutPhotos);
				} else {
					do {
						$slice = array_splice($layoutPhotos,0,$COUNT);
						$arrangement = $this->__getArrangementFromPOST($slice);
			 			$this->__scaleArrangement($arrangement);
				 		$montage[] = $this->__exportMontage($arrangement, $slice);
				 	} while (!empty($layoutPhotos));
				}
				break;
			case 'static': 
			default:
				// use static list of photos
				$url = "http://{$host}/photos/all/rating:1/page:1/perpage:16/.json?debug=0";
				$json = json_decode($this->STATIC_JSON_cc, true); 
				$photos = $json['castingCall']['CastingCall']['Auditions']['Audition'];
				$baseurl = 'http://dev.snaphappi.com'.$json['castingCall']['CastingCall']['Auditions']['Baseurl'];
		 		// static arrangements use only landscape photos
		 		$layoutPhotos = $this->__sortPhotos($this->__getPhotos($photos, $baseurl, 'landscape'),50);
// debug($layoutPhotos); 				
				do {
					$copy = $this->STATIC_JSON_arrangements;
					shuffle($copy);
					$rawJson = $copy[0];
					$arrangement = json_decode($rawJson, true);
					$this->__scaleArrangement($arrangement);
// debug($arrangement);					
					$slice = array_splice($layoutPhotos,0,count($arrangement['Roles']));
			 		$montage[] = $this->__exportMontage($arrangement, $slice);
			 	} while (!empty($layoutPhotos));
			 	break;
	 	}
		$this->set(compact('url', 'json', 'photos', 'arrangement','montage'));
	}

}
?>
