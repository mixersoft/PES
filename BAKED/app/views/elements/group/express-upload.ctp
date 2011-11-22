<aside id="express-upload-options"  class="related-content blue rounded-5">
	<h3>Express Upload</h3>
	<div>Share uploaded Snaps immediately with the Circles marked below.</div>
	<div class="wrap-v">
	<?php foreach ($expressUploadGroups as & $group) {
		$group['badge_src'] = Stagehand::getSrc($group['src_thumbnail'], 'sq');
		$group['title'] = ucFirst($group['title']);
		$group['linkTo'] = $this->Html->link($group['title'], 
			Router::url(array('controller'=>'groups', 'action'=>'home', $group['id'])),
			array('target'=>'_blank')
			);
		echo String::insert("<ul class='inline'>
			<li><input type='checkbox' uuid=':id' checked></li>
			<li><img src=':badge_src' class='tiny'></li>
			<li class='label'><b>:linkTo</b> (:type)</li>
			<li class='label'>:assets_group_count Snaps</li>
			<li class='label'>:groups_user_count Members</li>
			<li class='btn disabled' title='Remove from list.' onclick='PAGE.getExpressUploads()'>x</li>
		</ul>", $group);
	}?>
	</div>
</aside>