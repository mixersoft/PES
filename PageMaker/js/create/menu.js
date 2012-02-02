(function(){

	var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	SNAPPI.namespace('PM.onYready');
	// Yready init
	PM.onYready.Menu = function(Y){
		if (_Y === null) _Y = Y;
		PM.Menu = Menu;
		Plugin = PM.PageMakerPlugin.instance;
		// global lookup by CSS ID, or cfg.lookup_key
		Menu.CFG = {
			'menu-pm-toolbar-edit': CFG_Menu_PageMaker_Create,
			'menu-photoPreview-actions': CFG_Menu_PreviewPhoto_Actions,

			// context menus
			'contextmenu-photoroll-markup': CFG_Context_Photoroll,
			'contextmenu-group-markup': CFG_Context_FigureBox,		
			'contextmenu-person-markup': CFG_Context_FigureBox,
		};	
		
		
	} 	

	var DEFAULT_CFG_contextmenu = 	{
		showOn: 'mouseover',
		hideOn: 'mouseleave',
		align: { points:['tl', 'tr'] },
		hideDelay: 500,
		cancellableHide: true,
		showDelay: 0,
		on: {
			show: function(e) {
				 // console.warn('contextmenu: on show', e);
				 var menuTarget = e.target;
				 Menu.menuItem_beforeShow(menuTarget);
			},
			hide: function(e) {
//				 console.warn('contextmenu: on hide', e);
			}
		},
		after: {
			show: function(e) {
				 // console.warn('contextmenu: after show', e);
				 var menuTarget = e.target;
				 var contextTarget = menuTarget.get('currentNode');
				 var check;				 
			},
			hide: function(e) {
//				 console.warn('contextmenu: after hide', e);
			}
		}
	};

	
	var Menu = function(){};
	
	/*
	 * static properties and methods
	 */
	Menu.listen = {};		// ??? track Class listeners? or instance listeners?
	Menu.find = {};	// keep track of dialog instances for reuse
	Menu.overlayManager = null;
	
	/**
	 * fetch html markup using Plugin.IO
	 * @param cfg {uri:, selector:, container:, plug _Y.Plugin.io cfg attrs}
	 * @param callback, callback method to init Menu
	 * @return menu or false on XHR request
	 */
	Menu.getMarkup = function(cfg, callback){
		var container = cfg.container;
		var selector = cfg.selector;
		
		var ioCfg = {
				uri: null,
				autoLoad: true,
				showLoading:false, 
				end: null
		};		
		var ioCfg = _Y.merge(ioCfg, cfg);
		delete ioCfg.selector;
		delete ioCfg.selector;
		
		if (!_Y.one(selector) && ioCfg.uri) {
			// BUG: container.one('#menu-header') does NOT work in chrome
			var markupNode = _Y.Node.create("<div />");
			container.append(markupNode);
			SNAPPI.setPageLoading(true);
			markupNode.plug(_Y.Plugin.IO, ioCfg);	
			markupNode.io.afterHostMethod('insert', function(){
				SNAPPI.setPageLoading(false);
				callback.apply(this, arguments);
			});
			return false;
		} else {
			return callback();
		}		
	};
	/**
	 * 
	 * @param MARKUP
	 * @param TRIGGER
	 * @param cfg {}, additional config for _Y.OverlayContext
	 * @return
	 */
	Menu.initMenus = function(menus){
		var auth, defaultMenus = {};
		try {
			auth = SNAPPI.STATE.controller.userid; // authenticated
		} catch (e) {
			auth = null;
		}			
		if (auth) {
			defaultMenus = {
				'menu-pm-toolbar-edit': 1,	
			};
		}			
		
		menus = _Y.merge(defaultMenus, menus);
		for (var i in menus) {
			var key = menus[i]!==false ? i : null; 
			var cfg = _Y.Lang.isObject(menus[i]) ? menus[i] : null;
	    	if (key && !Menu.find[key]) {
	    		Menu.CFG[key].load(cfg);
	    	}			
		}
		_Y.one('#markup').setStyle('display', 'block');
	};	
	/**
	 * 
	 * @param MARKUP
	 * @param TRIGGER
	 * @param cfg {}, additional config for _Y.OverlayContext
	 * 		cfg.host: adds cfg.host.ContextMenu backreference
	 * 		cfg.triggerType	.gallery.[triggerType]
	 * 		cfg.triggerRoot '#'+cfg.triggerRoot.get('id')
	 * 		cfg.currentTarget, open at currentTarget, set menu._stashTrigger
	 * @return
	 */
	Menu.initContextMenu = function(MARKUP, TRIGGER, cfg){
		cfg = cfg || {};	// closure
		
		// set the correct TRIGGER
		if (cfg.force_TRIGGER) TRIGGER = cfg.force_TRIGGER;	// hack: for UIHelper.toggle_ItemMenu to use contextmenu as normal menu
		else {
			if (cfg.triggerRoot && cfg.triggerRoot instanceof _Y.Node) TRIGGER = '#'+cfg.triggerRoot.get('id')+' '+ TRIGGER;
			else if (cfg.triggerRoot) TRIGGER = cfg.triggerRoot +' '+ TRIGGER;
			else if (cfg.triggerType) TRIGGER = '.gallery.'+cfg.triggerType + TRIGGER;
		}
		
		var _cfg = {
				trigger: TRIGGER,
				contentBox: MARKUP.selector,
				boundingBox: MARKUP.selector
		};
		_cfg = _Y.merge(DEFAULT_CFG_contextmenu, _cfg, cfg);
		if (cfg.currentTarget) _cfg.trigger = cfg.currentTarget;	// 'startup/disabled' trigger

		var menu = new _Y.OverlayContext(_cfg);
		menu.render();
		menu.get('contentBox').removeClass('hide');
		if (cfg.init_hidden === false) menu.show();
		if (cfg.currentTarget) {
			menu.set('trigger', TRIGGER);	// 'enabled' trigger
			menu._stashTrigger = TRIGGER;
		}
		Menu.startListener(menu, cfg.handle_click );
		
		// lookup reference
		var key = cfg.lookup_key || MARKUP.id;
		Menu.find[key] = menu;
		if (cfg.host) cfg.host.ContextMenu = menu; 		// add back reference
		return menu;
	};
	/*
	 * add offset to menu alignment positioning
	 * 	NOTES: must use cfg.constrain = false for A.OverlayContext
	 * @params overlay, A.OverlayContext
	 * @params newXY, array [x,y] (optional), target XY location BEFORE constrain
	 * @params offset, {x:, y:}
	 */
	Menu.moveIfUnconstrained = function(overlay, newXY, offset) {
		if (!offset) return;
		newXY = newXY || overlay.get('xy');
		var constrainedXY = overlay.getConstrainedXY(newXY);
		newXY[0] = (newXY[0] == constrainedXY[0]) ? newXY[0]+offset.x : constrainedXY[0];
		newXY[1] = (newXY[1] == constrainedXY[1]) ? newXY[1]+offset.y : constrainedXY[1];
		return newXY;		
	}
	
	/*
	 * toggle .FigureBox context menu enable/disable by changing trigger
	 */
	Menu.toggleEnabled = function(menu_ID, e) {
		var menu = Menu.find[menu_ID];
		if (e && menu.get('disabled')) {
			menu.enable();
			var trigger = e.currentTarget.hasClass('FigureBox') ? e.currentTarget : e.currentTarget.ancestor('.FigureBox');
			menu.set('trigger', trigger);			// 'startup/disabled' trigger
			menu.show();
			menu.set('trigger', menu._stashTrigger); 		// 'enabled' trigger
		} else {
			menu.disable();
			menu.hide();
			menu.set('trigger', '#blackhole');
		}
		return menu;
	};
	
	Menu.startListener = function(menu, handle_click){
		var parent = menu.get('contentBox');
		handle_click = handle_click || function(e){
			var menuItem = e.currentTarget;
			if (menuItem.hasClass('disabled')) {
				// check for disabled
				e.preventDefault();
				return;
			} 
			var methodName = menuItem.getAttribute('action')+'_click';
			if (MenuItems[methodName]) {
				e.preventDefault();
				MenuItems[methodName](menuItem, this);
			} else {
				// default
				try {
					// no special clickhandler, so just find a.href
					var next = menuItem.one('a').getAttribute('href');
					menuItem.addClass('clicked');
					SNAPPI.setPageLoading(true);
					var delayed = new _Y.DelayedTask( function() {
						Menu.hide();
						menuItem.removeClass('clicked');
						window.location.href = next;
					});
					delayed.delay(100);
				} catch (e) {}
			}
		};
			
		menu.listen = menu.listen || {};
		if (!menu.listen['delegate_click']) {
			menu.listen['delegate_click'] = parent.delegate('click', handle_click, 'ul  li',  menu);
		}
	};
	
	Menu.menuItem_beforeShow = function(menu, o){
		var content = menu.get('contentBox');
		if (content) content.all('ul  li.before-show').each(function(n,i,l){
			// call beforeShow for each menuItem
			if (n.hasClass('before-show')) {
				var methodName = n.getAttribute('action')+'_beforeShow';
				if (MenuItems[methodName]) {
					try {
						MenuItems[methodName](n, menu, o);	
					} catch (e) {}
				}
			}
		}, menu);
	};
	
	
	
	
	Menu.log_methods = function(menu){
		
		return;		// disable
		
		
		var peek = ['refreshAlign', 'toggle', 'show', 'hide', 'updateCurrentNode', 'focus', 'render', 'enable' , 'disable' ];
		_Y.before(function(){
			console.log("before A.OverlayContext.refreshAlign()");
		}, menu, 'refreshAlign', menu);  
		_Y.before(function(){
			console.log("before A.OverlayContext.toggle()");
		}, menu, 'toggle', menu); 
		_Y.before(function(){
			console.log("before A.OverlayContext.show()");
		}, menu, 'show', menu); 
		_Y.before(function(){
			console.log("before A.OverlayContext.hide()");
		}, menu, 'hide', menu); 
		_Y.before(function(){
			console.log("before A.OverlayContext.updateCurrentNode()");
		}, menu, 'updateCurrentNode', menu); 			
		_Y.before(function(){
			console.log("before A.OverlayContext.focus()");
		}, menu, 'focus', menu); 			
		_Y.before(function(){
			console.log("before A.OverlayContext.render()");
		}, menu, 'render', menu); 		
	};
	
	
	
	
	var MenuItems = function(){}; 

	MenuItems.shuffle_beforeShow = function(menuItem, menu){
		if (1) {
			menuItem.removeClass('disabled');
			return;
		}
		menuItem.addClass('disabled');
	};	
	
	MenuItems.shuffle_click = function(menuItem, menu){
		var target = menu.get('currentNode');	
		// var delayed = new _Y.DelayedTask( function() {
			// menu.hide();
		// });
		// delayed.delay(1000);
		PM.main.makePageGallery();
	};
	MenuItems.save_beforeShow= function(menuItem, menu){
		// start listener for story_id
		menuItem.delegate('click', function(e){
			e.stopImmediatePropagation();
		}, '#story_id');
		menuItem.delegate('change', function(e){
			try {
				var parent = menu.get('contentBox');
				var STORY_ID = menuItem.one('#story_id').get('value');
				parent.all('li.btn').some(
					function(n,i,l){
						if (n.getAttribute('action')=='play') {
							if (STORY_ID) n.removeClass('disabled');
							else n.addClass('disabled');
							return true;
						}
						return false;
					})
			} catch (e) {}
		}, '#story_id');		
	}
	MenuItems.save_click = function(menuItem, menu){
		var target = menu.get('currentNode');
		var parent = menuItem.get('parentNode');
		var STORY_ID = parent.one('#story_id').get('value');
		if (STORY_ID) {
            var userid, filename, saved_src;
            try { 
            	userid = PAGE.jsonData.controller.xhrFrom.uuid;
            	filename = STORY_ID || userid;
            } catch (e){
            	filename = STORY_ID || 'saved';
            }
            saved_src = '/gallery/story/'+filename+'?page=last';
            var content = Plugin.stage.body.one('div.pageGallery').unscaled_pageGallery;
            var cfg = {
            	content: content, 	// save pageGallery HTML of parent node
//                  tmpfile: 'tmp',		// save from tmp file
                filename: filename,
                success: function(){
                    /*
                     * mark scene as saved
                     */
                    var Pr = Plugin.production;
                    Pr.saveScene();
                    window.open(saved_src, 'page gallery');
                }
            };
            PM.util.saveToPageGallery(cfg);
            return false;
        }			
	}
	
	
	// called on menu.show()
	MenuItems.play_beforeShow = function(menuItem, menu){
		var STORY_ID = menuItem.one('#story_id').get('value');
		if (STORY_ID) {
			menuItem.removeClass('disabled');
			return;
		}
		menuItem.addClass('disabled');
	};			
		
	
	/*
	 * incomplete. use PM.Dialog to choose from existing Stories
	 * adapted from select-circles
	 */
	MenuItems.save_dialog_click = function(menuItem, menu){
		var target = menu.get('currentNode');	
		// launch Save Dialog
		var STORY_ID = menu.STORY_ID;
		
		/*
		 * create or reuse Dialog
		 */
		var dialog_ID = 'dialog-select-stories';
		var dialog = PM.Dialog.find[dialog_ID];
		if (!dialog) {
        	dialog = PM.Dialog.CFG[dialog_ID].load();
        	var args = {
        		dialog: dialog,
        		menu: menu
        	}
        	// content for dialog contentBox
			var ioCfg = {
				uri: subUri,
				parseContent: false,
				autoLoad: false,
				context: dialog,
				dataType: 'html',
				arguments: args,    					
				on: {
					success: function(e, i,o,args) {
						this.set('zIndex', 2001);	// pageGallery zIndex=1001
						SNAPPI.setPageLoading(false);
						// add content
						var parent = args.dialog.getStdModNode('body');
						parent.setContent(o.responseText);
						// start multi-select listener
						var container = parent.one('.container');
						SNAPPI.multiSelect.listen(container, true, SNAPPI.MultiSelect.singleSelectHandler);
						return false;
					}					
				}
			};
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
			dialog.plug(_Y.Plugin.IO, ioCfg);
			PM.Dialog.find[dialog_ID] = dialog;
			
			/*
			 *  enable/disable Play button
			 */
			var detach = dialog.on('closeChange', function(e){
				try {
					var parent = menu.get('contentBox').all('li.btn').some(
						function(n,i,l){
							if (n.getAttribute('action')=='play') {
								if (STORY_ID) n.removeClass('disabled');
								else n.addClass('disabled');
								return true;
							}
							return false;
						})
				} catch (e) {}	
			}, dialog);			
			
		} else {
			if (!dialog.get('visible')) {
				dialog.setStdModContent('body','<ul />');
				dialog.show();
			}
			dialog.set('title', 'My Stories');
		}
		
		// shots are NOT included. get shots via XHR and render
		var subUri = '/my/groups';
		dialog.io.set('uri', subUri );
		// dialog.io.set('arguments', args ); 
		SNAPPI.setPageLoading(true);   			
		dialog.io.start();	
	};



	/*
	 * MenuCfgs
	 */
	/**
	 * load method for pop-up menu with static, onclick trigger
	 * @param id string, CSS_ID for menu markup
	 * @param trigger string, CSS3 selector
	 * @param cfg, cfg.uri for markup by XHR load
	 * @return
	 */
	var _load_Single_Trigger_Menu = function(id, trigger, uri, cfg){
		// cfg.uri for XHR load
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tr', 'br'] },
			init_hidden: true
		};
		cfg = _Y.merge(defaultCfg, cfg);
		uri = uri || cfg.uri;
		var CSS_ID = id;
		var TRIGGER = trigger;
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: uri,			// cfg.uri for XHR load
				end: null
		};
		
		// reuse, if found
		var key = cfg.lookup_key || CSS_ID;
		if (Menu.find[key]) 
			return Menu.find[key];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	} 
	 
	/**
	 * load user shortcuts menu
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_PageMaker_Create = function(){};
	CFG_Menu_PageMaker_Create.load = function(cfg){
		var CSS_ID = 'menu-pm-toolbar-edit';
		var TRIGGER = '#stage-2';
		var XHR_URI = '/combo/markup/pm_ToolbarEdit'; 
		var _cfg = {
			showOn: 'mouseenter',
			hideOn: '',
			// hideOnDocumentClick: false,	// must set zIndex manually, and hide on dialog close
			// zIndex: 5000,					
			align: { points:['bl', 'tl'] },
			init_hidden: false,
		}
		_cfg = _Y.merge(_cfg, cfg);
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, _cfg);
	};	

	
	var CFG_Menu_PreviewPhoto_Actions = function(){}
	CFG_Menu_PreviewPhoto_Actions.load = function(cfg){
		var CSS_ID = 'menu-photoPreview-actions';		
		var TRIGGER = '.FigureBox.PhotoPreview li.icon.context-menu';
		var XHR_URI = '/combo/markup/photoPreviewActionMenu'; 
		var _cfg = {
			align: { points:['tr', 'br'] },
			init_hidden: false,
			offset: {x:10, y:0},
			on: {
				show: function(e) {
					 var menu = e.target;
					 var target = menu.get('currentNode');
					 var node = target.ancestor('.FigureBox.PhotoPreview');
					 var audition = SNAPPI.Auditions.find(node.uuid); 
					 Menu.menuItem_beforeShow(menu, audition);
				},
			},
			handle_click : function(e){
				var menuItem = e.currentTarget;
				var target = this.get('currentNode');
				if (menuItem.hasClass('disabled')) {
					// check for disabled
					e.preventDefault();
					return;
				} 
				var node = target.ancestor('.FigureBox.PhotoPreview');
				var audition = SNAPPI.Auditions.find(node.uuid); 
				var methodName = menuItem.getAttribute('action')+'_click';
				if (MenuItems[methodName]) {
					e.preventDefault();
					MenuItems[methodName](menuItem, this, audition);
				}
			}
	    }
		cfg = _Y.merge(_cfg, cfg);
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, cfg);
	};
	
	var CFG_Context_Photoroll = function(){}; 
	/**
	 * load .gallery.photo > .FigureBox contextmenu 
	 * @param cfg
	 * @return
	 */
	CFG_Context_Photoroll.load = function(cfg){
		var CSS_ID = 'contextmenu-photoroll-markup';
		var TRIGGER = ' .FigureBox';
		var defaultCfg = {
				// constrain: true,
		}
		cfg = _Y.merge(defaultCfg, cfg);

		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: '/combo/markup/photoRollContextMenu',
		};
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];
		
		
		var callback = function(){
			var menu = Menu.initContextMenu(MARKUP, TRIGGER, cfg);
			var offset = cfg.offset || {x:-20, y:20};
			menu.set('xy', Menu.moveIfUnconstrained(menu, null, offset));
			menu.on('xyChange', function(e){
					e.newVal = Menu.moveIfUnconstrained(this, e.newVal, offset);
				}
			)
		};
		return Menu.getMarkup(MARKUP , callback);
	};
	
	
	

	
	
	/**
	 * load .FigureBox contextmenu .gallery.[type] > .FigureBox contextmenu 
	 * @param cfg. cfg.triggerType = [group, person, etc.]
	 * @return
	 */
	var CFG_Context_FigureBox = function(){}; 
	CFG_Context_FigureBox.load = function(cfg){
		var TRIGGER = cfg.force_TRIGGER || cfg.TRIGGER || ' .FigureBox';
		// if (cfg.triggerType) TRIGGER = '.gallery.'+cfg.triggerType + TRIGGER;
		var TYPE_LOOKUP = {
			'photo': {
				CSS_ID: 'contextmenu-photo-markup',	
				uri: '/combo/markup/photoContextMenu',				
			},
			'group': {
				CSS_ID: 'contextmenu-group-markup',	
				uri: '/combo/markup/groupContextMenu',
			},
			'person': {
				CSS_ID: 'contextmenu-person-markup',	
				uri: '/combo/markup/personContextMenu',
			}, 
		}		
		var typeDefaults = TYPE_LOOKUP[ cfg.triggerType ];
if (!typeDefaults && console) console.error("ERROR: missing contextmenu type for CFG_Context_FigureBox.load()"); 

		var CSS_ID = cfg.CSS_ID || typeDefaults.CSS_ID;
		var defaultCfg = {
				// constrain: true,
			on: {
				show: function(e) {
					 // console.warn('contextmenu: on show', e);
					 var menu = e.target;
					 var target = menu.get('currentNode');
					 var jsonProperties = SNAPPI.UIHelper.groups.getProperties(cfg.triggerType ,target); 
					 Menu.menuItem_beforeShow(menu, jsonProperties);
				},
			},
			handle_click : function(e){
				var menuItem = e.currentTarget;
				var target = this.get('currentNode');
				if (menuItem.hasClass('disabled')) {
					// check for disabled
					e.preventDefault();
					return;
				} 
				var jsonProperties = SNAPPI.UIHelper.groups.getProperties(cfg.triggerType ,target);
				var methodName = menuItem.getAttribute('action')+'_click';
				if (MenuItems[methodName]) {
					e.preventDefault();
					MenuItems[methodName](menuItem, this, jsonProperties);
				}
			},	
		}
		cfg = _Y.merge(defaultCfg, cfg);

		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: typeDefaults.uri,
		};
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];
		
		
		var callback = function(){
			var menu = Menu.initContextMenu(MARKUP, TRIGGER, cfg);
			var offset = cfg.offset || {x:-20, y:20};
			menu.set('xy', Menu.moveIfUnconstrained(menu, null, offset));
			menu.on('xyChange', function(e){
					e.newVal = Menu.moveIfUnconstrained(this, e.newVal, offset);
				}
			)
		};
		return Menu.getMarkup(MARKUP , callback);
	};		
	

})();
