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
echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail']));
?>
<div class="assets view main-div placeholder">
<div class="assets">
	<div class='img placeholder'>
		<ul class='sizes inline'>
			<li><h3 style='display:inline'>Sizes:</h3></li>
			<?php $sizes = array('sq'=>'Square', 'tn'=>'Thumbnail', 'bs'=>'Small', 'bm'=>'Medium', 'bp'=>'Preview'); 
				foreach($sizes as $size=>$label) {
					echo "<li>{$this->Html->link($label, setNamedParam($this->params['url'], 'size', $size))}</li>";
				}
			?>
		</ul>
		<?php $size = isset($this->params['named']['size'])  ? $this->params['named']['size'] : 'bm';
				$src = getImageSrcBySize(Session::read('stagepath_baseurl').$data['Asset']['src_thumbnail'], $size);
				echo $this->Html->image($src);
			?>
	</div>	
	
	
	<div id='properties' class="placeholder">
	<h3>Properties</h3>
	<blockquote>
		<h4>History</h4>
		<dl><?php $i = 0; $class = ' class="altrow"';?>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Owner'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $this->Html->link($data['Owner']['username'], array('controller' => 'users', 'action' => 'view', $data['Owner']['id'])); ?>
				&nbsp;
			</dd>		
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Photostream'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php $photostream = "{$data['ProviderAccount']['display_name']}@{$data['ProviderAccount']['provider_name']}";
					echo $this->Html->link($photostream, array('controller' => 'provider_accounts', 'action' => 'view', $data['ProviderAccount']['id'])); ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Date Taken'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $this->Time->nice($data['Asset']['dateTaken']); ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Uploaded On'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo  $this->Time->nice($data['Asset']['batchId']); ?>
				&nbsp;
			</dd>
			<!--
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Src Thumbnail'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['src_thumbnail']; ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Json Src'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['json_src']; ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Json Exif'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['json_exif']; ?>
				&nbsp;
			</dd>
			<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Json Iptc'); ?></dt>
			<dd<?php if ($i++ % 2 == 0) echo $class;?>>
				<?php echo $data['Asset']['json_iptc']; ?>
				&nbsp;
			</dd>
			-->
			
			
		</dl>
		<h4>Exif</h4>
	</blockquote>
	</div>	
	
<div id='section-tabs'>
<ul class='inline'>
	<?php 		
		$xhrSrc = array('plugin'=>'', 'action'=>'settings', $this->passedArgs[0]);
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrview'=>'settings-details');
		$detailsSrc = Router::url($xhrSrc);	
		$xhrSrc['?'] = array('xhrview'=>'settings-privacy');
		$privacySrc = Router::url($xhrSrc);	
	?>	
	<li><a class='tab-details' href='<?php echo $detailsSrc ?>' onclick='return gotoTab(this);'>Details</a></li>
	<li><a class='tab-privacy' href='<?php echo $privacySrc ?>' onclick='return gotoTab(this);'>Privacy</a></li>
</ul>
</div>	
<div id='tab-section' class="setting placeholder xhr-get"  xhrSrc='<?php echo $detailsSrc ?>'>
</div>	


	
	
	
</div>
</div>


