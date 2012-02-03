(function(){
	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	SNAPPI.namespace('PM.onYready');
	// Yready init
	PM.onYready.Dialog = function(Y){
		if (_Y === null) _Y = Y;
		PM.Dialog = Dialog;
		PM.Alert = CFG_Dialog_Alert;
		Plugin = PM.PageMakerPlugin.instance;
	} 	    
    

	var BUTTONS_OK_CANCEL = [{
			text: 'OK',
			handler: null
		},{
			test: 'Cancel',
			handler: function(){this.close();}
		}],
			
		BUTTONS_CLOSE =[{
			text: 'Close',
			handler: function(){this.close();}
		}];
	
	var DEFAULT_CFG_dialog = {
			centered: true,
			constrain2view: true,
			draggable: true,
			resizable: false,
			destroyOnClose: true,
			height: 250,
			width: 500,
			close: true,
			buttons: [],
		};
	var DEFAULT_CFG_modal = {
			modal: true,
			centered: true,
			constrain2view: true,
			draggable: false,
			resizable: false,
			destroyOnClose: true,
			height: 250,
			width: 500,
			close: true,
			buttons: [],
		};		
	var DEFAULT_CFG_io = {
		};
	

	var Dialog = function(){
		if (Dialog.doClassInit) Dialog.classInit();
	};
	
	/*
	 * static properties and methods
	 */
	Dialog.listen = {};
	Dialog.find = {};	// keep track of dialog instances for reuse
	
	Dialog.listen_select = function(d) {
		var content = d.get('contentBox');
		var detach = content.delegate('click', function(e,i,o){
			var target = e.currentTarget;
			e.halt(true);
			target.get('parentNode').all('.selected').removeClass('selected');
			target.addClass('selected');
		}, 'li', d);
		return detach;
	}
	Dialog.listen_close = function(d) {
		var detach = d.on('closeChange', function(e){
			for (var i in this.listen) {
				this.listen[i].detach();
			}
		}, d)
		return detach;		
	}
	
		
	/*
	 * DialogCfgs
	 */
	
	var CFG_Dialog_Select_Stories = function(){}; 
	/*
	 * Lightbox, choose circles dialog
	 */
	CFG_Dialog_Select_Stories.load = function(cfg){
		var CSS_ID = 'dialog-select-stories';
		var _cfg = {
			title: 'My Stories',
			id: CSS_ID,
			width: 740,	// 3 columns, for now
			height: 395,	// 3 rows
			destroyOnClose: true,
			modal: true,
			buttons: [
			{
				text: 'Apply',
				handler: function() {
					var check;
					var content = this.get('contentBox');
					// var selected = content.one('.container .FigureBox.selected');
					// var uuid = selected.get('id');
					var menu = PM.Menu.find['menu-pm-toolbar-edit'];
					menu.STORY_ID = 'temp';
					this.close();
				}
			}
			]			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
				
		dialog.listen = {};
		dialog.listen['select'] = PM.Dialog.listen_select(dialog);
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}
	
	var CFG_Dialog_Select_Privacy = function(){}; 
	/*
	 * Lightbox, choose circles dialog
	 */
	CFG_Dialog_Select_Privacy.load = function(cfg){
		var CSS_ID = 'dialog-select-privacy';
		var _cfg = {
			title: 'Privacy Settings',
			id: CSS_ID,
			// width: 678,	// 2 columns, for now
			height: 300,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			{
				text: 'Apply',
				handler: function() {
					var content = this.get('contentBox');
					var selected = content.one('.selected');
					var value = parseInt(selected.getAttribute('value'));
					var detach = _Y.on('snappi:privacy-complete', function(lightbox, loading){
						loading.loadingmask.hide();
						// update asset count in dialog
						detach.detach();
					});
					SNAPPI.lightbox.applyPrivacyInBatch(value, selected);
				}
			}
			]			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
		dialog.listen = {};
		dialog.listen['select'] = Dialog.listen_select(dialog);
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}

	var CFG_Dialog_Login = function(){}; 
	/*
	 * 
	 * Currently not working. some problems with CSS?
	 * user Login, for AIR Uploader
	 * 
	 */
	CFG_Dialog_Login.load = function(cfg){
		var CSS_ID = 'dialog-login';
		var _cfg = {
			title: 'Sign In',
			id: CSS_ID,
			height: 300,	// 3 rows
			destroyOnClose: false,
			modal: true,
			buttons: [
			// {
				// text: 'Sign in',
				// handler: function() {
					// var content = this.get('contentBox');
				// }
			// }
			],
			resizble: true,			
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_dialog, _cfg, cfg);
		
		var dialog = new _Y.Dialog(_cfg);
		dialog.listen = {};
		
		if (cfg.autoLoad !== false) dialog.render();
		// save reference
		Dialog.find[CSS_ID] = dialog;
		return dialog;		
	}	
	
	/*
	 * example usage
	 * 
				var cfg = {
					selector: [CSS selector, copies outerHTML and substitutes tokens as necessary],
					markup: [html markup],
	    			uri: '/combo/markup/importComplete',
	    			height: 300,
	    			tokens: {
	    				folder: 'folder',
		    			count: 17,
		    			added: 4
	    			},
	    		};
	 * var alert = SNAPPI.Alert.load(cfg) or PM.Dialog.CFG['dialog-alert'].load(cfg);
	 */
	var CFG_Dialog_Alert = function(){}; 
	CFG_Dialog_Alert.load = function(cfg){
		var CSS_ID = 'dialog-alert';
		var _cfg = {
			id: CSS_ID,
			// height: 500,	
			// width: 500,
			// bodyContent: null,
		}
		cfg = cfg || {};
		_cfg = _Y.merge(DEFAULT_CFG_modal, _cfg, cfg);
		var alert = Dialog.find[_cfg.id];
		if (alert) {
			alert.getStdModNode('body').setContent('').destroy();
		} 
		alert = new _Y.Dialog(_cfg).render();
		
		var body = alert.getStdModNode('body');
		if (_cfg.bodyNode) {
			body.setContent(_cfg.bodyNode);
		} else if (_cfg.selector) {
			var markup = _Y.one(_cfg.selector).get('parentNode.innerHTML');
			if (_cfg.tokens) markup = _Y.substitute(markup, _cfg.tokens);
			body.setContent(markup);
		} else if (_cfg.markup) {	
			if (_cfg.tokens) _cfg.markup = _Y.substitute(_cfg.markup, _cfg.tokens);
			body.setContent(_cfg.markup);
		} else if (_cfg.uri) {
			// XHR content for dialog contentBox
    		var args = {
	    		dialog: alert,
	    	}
	    	if (_cfg.tokens) args.tokens = _cfg.tokens;
	    	if (alert.io) alert.unplug(SNAPPI.Y.Plugin.IO);
			var ioCfg = {
				uri: cfg.uri,
				parseContent: true,
				autoLoad: true,
				// modal: false,
				context: alert,
				dataType: 'html',
				arguments: args,    					
				on: {
					success: _cfg.success || function(e, i, o, args) {
						if (args && args.tokens) {
							var markup = _Y.substitute(o.responseText, args.tokens);
						} else markup = o.responseText;
						body.setContent(markup);	// closure
						return false; 
					}					
				}
			};
			ioCfg = SNAPPI.IO.getIORequestCfg(cfg.uri, ioCfg.on, ioCfg);
			alert.plug(SNAPPI.Y.Plugin.IO, ioCfg);
		}		
		Dialog.find[_cfg.id] = alert;		// save reference for lookup
		return alert;		
	}	
	
	
		
	// save CFG in static
	Dialog.CFG = {
		'dialog-select-stories': CFG_Dialog_Select_Stories,
		'dialog-select-privacy': CFG_Dialog_Select_Privacy,
		'dialog-login': CFG_Dialog_Login,
		'dialog-alert': CFG_Dialog_Alert,
	};
	
})();