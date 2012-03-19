<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();	
}			
?>
<p />
<?php
$lightbox = $this->Session->read('lightbox');
if ($lightbox) $this->viewVars['jsonData']['lightbox'] = $lightbox;

/*
 * DEBUG ONLY for pg_designer
 * if lightbox is empty, just get all photos for current user
 */
if (empty($lightbox['auditions'])) {
	// DEBUG: if empty, get all assetIds from /my/photos
	// same as http://git:88/my/photos/perpage:999/.json
	App::import('Controller', 'Assets');
	$AssetsController = new AssetsController();
	$AssetsController->constructClasses();
//	$AssetsController->Asset->contain(null);
	// get all Assets for logged in user
	$options = array(
		'recursive'=> -1,
		'fields'=>'id',
		'conditions'=>array('Asset.owner_id'=>AppController::$ownerid),
		'order'=>array('Asset.dateTaken'=> 'ASC'),
//		'showEdits'=>true,
		'permissionable' => false,
//		'showSubstitutes'=>false,
		'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'UserShot', 		// TODO: could be Groupshot!!!, check from:
				'show_hidden_shots'=>false
			),
		'limit'=>500
	);
	$data = $AssetsController->Asset->find('all', $options);
	$aids = Set::extract($data, '/Asset/id');
	$lightbox['auditions'] = implode(',',$aids);
}
/*
 * END DEBUG
 */


if (!empty($lightbox['auditions'])) {
	// ignore innerHTML
	App::import('Controller', 'Assets');
	$AssetsController = new AssetsController();
	$AssetsController->constructClasses();
	$request = $this->here;
	$castingCall = $AssetsController->getCC($lightbox['auditions'], $request);
	if ($castingCall) {
		$this->viewVars['jsonData']['lightbox']['castingCall'] = $castingCall;
		unset( $this->viewVars['jsonData']['lightbox']['auditions']);		// TODO: deprecate $lightbox['auditions']
	}
}
	// full page lightbox
	$this->viewVars['jsonData']['lightbox']['full_page']=true;
	$this->viewVars['jsonData']['lightbox']['thumbsize']='tn';
?>
<div class="drop placeholder hide" id="lightbox">
	<ul class="toolbar inline"></ul>
	<ul class="photo-roll"></ul>
</div>
<div id='pagemaker' class='placeholder'></div>
<?php $this->Layout->blockStart('javascript');?>
<script type="text/javascript">
var initOnce = function() {
	// load PG designer
	// after lightbox Load
	var detach;
	detach = SNAPPI.Y.on('snappi:afterLightboxInit',
			function(){
				detach.detach();
				var count = this.Gallery.auditionSH.size();
				this.Gallery.container.setStyles(
						{
							'overflow-x':'scroll',
							'height':'132px',
							'width': count * 132 + 'px'
						});
				SNAPPI.Y.one('#lightbox .FigureBox figure > img').addClass('drag');
	            SNAPPI.Y.all('img.drag').each(function(n, i, l){
					SNAPPI.DragDrop.pluginDrag(n);
				}, this);
				SNAPPI.DragDrop.startListeners();
				this.load_then_launch_PageMaker();

				// additional lightbox attrs
				SNAPPI.MenuAUI.initMenus({'menu-pagemaker-selected-create-markup':1});
				this.showThumbnailRatings();
			}, SNAPPI.lightbox
	);
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<?php $this->Layout->blockEnd();?>	