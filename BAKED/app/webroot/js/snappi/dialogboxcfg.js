(function(){

	var Y = SNAPPI.Y;
	
	var DialogCfg = function(){};
	
	DialogCfg.prototype = {
		initPrivacyMenuDBox : function(menuNode, delegateSelector){
		
			var cfg = {},
				delegateSelector = delegateSelector || '> li',
				delegateClickListener,
				listenToPrivacyMenuItem,
				onclickHandler,
				action;
			
			// should i call it action or submit
			action = function(){
				var id = SNAPPI.dialogbox.node.one('.FigureBox.selected').get('id');
				SNAPPI.lightbox.applyPrivacyInBatch(id);
			};
			
			onclickHandler = function(e){
				var target = e.target,
					label = target.get('innerHTML'),
					id = target.get('id');	
					args = new Array(),
					nodeCfg = {},
					cfg = {
							title : 'privacy settings',
							buttons : [{
								text : 'apply setting',
								fn   : action
							}]
						};
					
				if(!target.hasClass('more-btn-todialog')){
					nodeCfg = {
						id : id,
						label : label
					};
					cfg.body = Y.Node.create(Y.substitute('<li id="{id}">{label}</li>', nodeCfg));
				}else {
					var list = target.get('parentNode').all('li'),
						node,
						nodeList;
					
					list.each(function(n, i, l){
						var _cfg = {};
		
						if(!n.hasClass('more-btn-todialog')){
							nodeCfg = {
								id : n.get('id'),
								label : n.dom().MenuItem.label
							};
							
							node = Y.Node.create(Y.substitute('<li id="{id}">{label}</li>', nodeCfg));
							nodeList.push(node);
						}
						
					});
					cfg.body = nodeList;
				}
				
				/*
				 * after clicking the menuitem, you need to render dialogbox
				 */
				SNAPPI.dialogbox.render(cfg);
			};
		
			/*
			 * callback for submit action, this is a placeholder, and for future use.
			 */
			SNAPPI.dialogbox.callback = {
				success : function(){},
				failure : function(){}
			};
			
			cfg.onload = {
				fn :  function(handler, container, selector){
						container.delegate('click', handler, selector);
					},
				args : [onclickHandler, menuNode, delegateSelector]
			};
			
			/*
			 * at last, init the dialog box.
			 */
			SNAPPI.dialogbox.init(cfg);
		},
		
		initGroupMenuDBox : function(menuNode, delegateSelector){
			var cfg = {},
				delegateSelector = delegateSelector || ' ul > li',
				delegateClickListener,
				listenToShareMenuItem,
				onclickHandler,
				action_share,
				action_unShare;

			listenToGroupMenuItemClick = function(handler, container, selector){
				container.delegate('click', handler, selector);

			};
			
			action_share = function(){
				var id = SNAPPI.dialogbox.node.one('.FigureBox.selected').get('id');
				SNAPPI.lightbox.applyShareInBatch(id);
			};
			
			action_unShare = function(){
				var id = SNAPPI.dialogbox.node.one('.FigureBox.selected').get('id');
				SNAPPI.lightbox.applyUnShareInBatch(id);
			};
			
			onclickHandler = function(e){
				
				var target = e.target,
					label = target.get('innerHTML'),
					id = target.get('id');	
					args = new Array(),
					nodeCfg = {},
					cfg = {},
					cfg_share = {
							title : 'share settings',
							buttons : [{
								text : 'apply sharing',
								fn   : action_share
							}]
							
						},
					cfg_unShare = {
							title : 'unShare settings',
							buttons : [{
								text : 'apply unSharing',
								fn   : action_unShare
							}]
						};
					
				if(!target.hasClass('more-btn-todialog')){
					if(id !== 'share-list'){
						nodeCfg = {
							id : id,
							label : label
						};
						var check = cfg;
						cfg.body = Y.Node.create(Y.substitute('<li id="{id}">{label}</li>', nodeCfg));
					}
				}else {
					var list = target.get('parentNode').all('li'),
						node,
						nodeList = [];
					
					list.each(function(n, i, l){
						if(!n.hasClass('more-btn-todialog')){
							nodeCfg = {
								id : n.get('id'),
								label : n.dom().MenuItem.label
							};
							node = Y.Node.create(Y.substitute('<li id="{id}">{label}</li>', nodeCfg));
							nodeList.push(node);
						}
						
					});
					cfg.body = nodeList;
				}
				
				var title_parent = target.dom().MenuItem.getMenu().getNode().get('id');
				if(title_parent === "share-list"){
					cfg = Y.merge(cfg, cfg_share);
				}else {
					cfg = Y.merge(cfg, cfg_unShare);
				}

				SNAPPI.dialogbox.render(cfg);
			};
			
			/*
			 * callback for submit action, this is a placeholder, and for future use.
			 */
			SNAPPI.dialogbox.callback = {
				success : function(){},
				failure : function(){}
			};
			
			cfg.onload = {
				fn :  function(handler, container, selector){
						container.delegate('click', handler, selector);
					},
				args : [onclickHandler, menuNode, delegateSelector]
			};
			
			SNAPPI.dialogbox.init(cfg);
			
		},
		
		renderDialogBox : function(cfg){
			SNAPPI.dialogbox.render(cfg);
		}
	};
	
	SNAPPI.cfg.DialogCfg = new DialogCfg();
	
})();