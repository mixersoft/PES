<?php
$lightbox = $this->Session->read('lightbox');
if ($lightbox) $this->viewVars['jsonData']['lightbox'] = $lightbox;
if (!empty($lightbox['auditions'])) {
	// ignore innerHTML
	App::import('Controller', 'Assets');
	$AssetsController = new AssetsController();
	$AssetsController->constructClasses();
	$castingCall = $AssetsController->getCC($lightbox['auditions']);
	if ($castingCall) {
		$this->viewVars['jsonData']['lightbox']['castingCall'] = $castingCall;
		unset( $this->viewVars['jsonData']['lightbox']['auditions']);		// TODO: deprecate $lightbox['auditions']
		unset($this->viewVars['jsonData']['lightbox']['innerHTML']);		// TODO: deprecate
	}
}
if (Configure::read('controller.action')=='lightbox') {
	// full page lightbox
	$this->viewVars['jsonData']['lightbox']['full_page']=true;
	$this->viewVars['jsonData']['lightbox']['thumbsize']='tn';
}
?>
<div class="drop placeholder hide" id="lightbox">
	<ul class="toolbar inline"></ul>
	<ul class="photo-roll"></ul>
</div>