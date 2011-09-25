<?php $uuid = $this->passedArgs[0]; ?>
<script type="text/javascript">
gotoTab = function(dom){
	if (dom.href.search('/cancel')>=0) {
			return false;
	}
	PAGE={section: dom.className};
	SNAPPI.TabNav.selectByName(PAGE);
	var container = SNAPPI.Y.one("#tab-section").setAttribute('ajaxSrc', dom.href);
	SNAPPI.xhrFetch.requestFragment(container);
	return false;
};
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.fetchXhr(); 
};
PAGE.init.push(initOnce);
</script>
<?php
echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>	
<div class="groups view main-div placeholder">

<div id='section-tabs'>
<ul class='inline'>
	<?php 		
		$xhrSrc = array('plugin'=>'', 'action'=>'settings', $this->passedArgs[0]);
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrview'=>'settings-details');
		$settingsSrc = Router::url($xhrSrc);
		$xhrSrc['?'] = array('xhrview'=>'settings-privacy');
		$privacySrc = Router::url($xhrSrc);	
		$xhrSrc['?'] = array('xhrview'=>'settings-policy');
		$policySrc = Router::url($xhrSrc);			
	?>
	<li><a class='tab-details' href='<?php echo $settingsSrc ?>' onclick='return gotoTab(this);'>Details</a></li>
	<li><a class='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return gotoTab(this);'>Privacy</a></li>
	<li><a class='tab-policy' href='<?php echo $policySrc ?>' onclick='return gotoTab(this);'>Policies</a></li>
</ul>
</div>	
<div id='tab-section' class="setting placeholder xhr-get"  xhrSrc='<?php echo $settingsSrc ?>'>
</div>	
		
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Owner'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($data['Owner']['id'], array('controller' => 'users', 'action' => 'view', $data['Owner']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Is System'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php if(1 == $data['Group']['isSystem']){ echo __('Yes'); } else { echo __('No');} ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Title'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['title']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['description']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Membership Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['membership_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Invitation Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['invitation_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Submission Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['submission_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Is NC17'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php if(1 == $data['Group']['isNC17']) { echo __('Yes');} else { echo __('No');} ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Last Visit'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created On'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</dd>
	</dl>
</div>


