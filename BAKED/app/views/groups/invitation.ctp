<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		echo $this->element('nav/section', 
			array('badge_src'=>$badge_src,
				'classLabel'=>$data['Group']['type'],
				'label'=>$data['Group']['title'],
		));
	$this->Layout->blockEnd();	
		
// debug($data['Group']);	

	$controllerAlias = Configure::read('controller.alias');
	$previewSrc = Stagehand::getSrc($data['Group']['src_thumbnail'], 'bp');
	$options = array('linkTo'=>Router::url(array('plugin'=>'','controller'=>$controllerAlias, 'action'=>'home', $data['Group']['id']))); 
	if (isset($fields['title'])) $options['title'] = $fields['trim_caption'];
	
	// User badges
	if (!empty($this->params['url']['uuid'])) {
		$from =  $badges[$this->params['url']['uuid']];
		$invitation['from_usernameLinkTo'] = $this->Html->link(ucFirst($from['User']['username']) , $from[0]['linkTo'], array('target'=>'_blank'));	
		$invitation['from_src'] =  $from[0]['src'];
		$invitation['from'] = $from[0]['fullname'] ? "<b>{$from[0]['fullname']}<b> ({$invitation['from_usernameLinkTo']})" : "<b>{$invitation['from_usernameLinkTo']}</b>";
	}
	$invitation['circle'] = ucfirst($data['Group']['title']);
	$invitation['group_type'] = $data['Group']['type'];
	// debug($from);	
	// debug($signin_redirect);
?>
<section class='invitation prefix_1 grid_14 suffix_1'>
	<h2 class="alpha">Welcome to Snaphappi</h2>
	<div class='wrap grid_14'>
		<div class="alpha grid_2">
			<div class="right">
				<?php if (isset($from)) echo $this->Html->image($invitation['from_src'], array('url'=>$from[0]['linkTo'])) ?>	
			</div></div>
		<div class="grid_12 omega">
			<p>
<?php 
	if (isset($from) && $this->params['url']['express']) {
		echo String::insert(":from has invited you to upload and share your Snaps with the <b>:circle</b> :group_type at Snaphappi. 
				Your Snaps are needed to tell the whole story of this :group_type, and an express upload option will be provided to help you upload Snaps directly into this :group_type.", $invitation);
	} else if (isset($from)) {
		echo String::insert(":from has invited you to join the <b>:circle</b> :group_type at Snaphappi. As a member, you will be able share Snaps and connect with other members of this :group_type.", $invitation); 
	} else {
		echo String::insert("This is an invitation to join the <b>:circle</b> :group_type at Snaphappi. As a member, you will be able share Snaps and connect with other members of this :group_type.", $invitation);
	}
?>
				</p>
			<div class="response wrap prefix_1">
				<?php  
					if (isset($signin_redirect)) {
						$options = array('value'=>"Accept Invitation", 'name'=>'register', 'class'=>'orange',
							'onclick'=>"window.location.href='{$signin_redirect}';",
							// 'type'=>'button',
						);
						echo $this->Form->button("Accept Invitation", $options);
					} else {
						echo $this->Form->create('Group', array('action'=>'join'));
						echo $this->Form->hidden('id', array('value'=>$id)); 
						echo $this->Form->hidden('title', array('value'=>$data['Group']['title'])); 
						if (!empty($this->params['url']['express'])) {
							echo $this->Form->hidden('express', array('value'=>1)); 
						}
						echo $this->Form->button("Accept Invitation", array('value'=>"Accept Invitation", 'name'=>'data[Group][action]', 'class'=>'orange'));	
						if ($role == 'USER') echo $this->Form->button("Ignore", array('value'=>"Ignore", 'name'=>'data[Group][action]'));	
						echo $this->Form->end();
					} 
				?>			
			</div>
		</div>
	</div>
	<div class='wrap grid_14'>	
		<article class="FigureBox Group bp">
	    	<figure><?php echo $this->Html->image( $previewSrc , $options); ?>
	    		<figcaption>
	    		 <div class="label"><?php $data['Group']['title']  ?></div>
	    		 <ul class="inline extras ">
	    		 	<li class="privacy admin"></li>
	    		 	<li class="members"><a><?php echo $data['Group']['groups_user_count']  ?> Members</a></li>
	    		 	<li class="snaps"><a><?php echo $data['Group']['assets_group_count']  ?> Snaps</a></li>
				</ul></figcaption>
			</figure>
		</article>
	</div>
</section>
<!-- <?php $this->Layout->blockStart('javascript'); ?>
	<script type="text/javascript">	
		/**
		 * NOTE: key issues for xhr login are
		 *  - click for SignIn screen without leaving page
		 *  - openId support. it might be bundled in Users plugin
		 */
		showLoginDialog = function(){
    		/*
    		 * create or reuse Dialog
    		 */
    		var dialog_ID = 'dialog-login';
    		var uri = '/users/register';	// placeholder
    		var dialog = SNAPPI.Dialog.find[dialog_ID];
    		if (!dialog) {
            	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
            	SNAPPI.Dialog.find[dialog_ID] = dialog;
            }
            if (dialog.io) dialog.unplug(SNAPPI.Y.Plugin.IO);
        	var args = {
        		dialog: dialog,
        	}
        	// content for dialog contentBox
			var ioCfg = {
				uri: uri,
				parseContent: true,
				autoLoad: true,
				modal: false,
				context: dialog,
				dataType: 'html',
				arguments: args,    					
				on: {
					success: function(e, i,o,args) {
						var check;
						var container = SNAPPI.Y.Node.create(o.responseText); 
						return container.one('#body-container');
					}					
				}
			};
			ioCfg = SNAPPI.IO.getIORequestCfg(uri, ioCfg.on, ioCfg);
			dialog.plug(SNAPPI.Y.Plugin.IO, ioCfg);
		}
	
		var initOnce = function() {
			try {
				var listeners = {
					// 'WindowOptionClick':1, 
					// 'DisplayOptionClick':1,
					// 'ContextMenuClick':1, 
					// 'LinkToClick': 1,
					// 'MultiSelect':1,
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}			
				
					
						
			} catch (e) {}
		};
		try {
			SNAPPI.xhrFetch.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request	
	</script>	
<?php $this->Layout->blockEnd(); ?> 	 -->