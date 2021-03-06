(function(){

	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Menu = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.MenuAUI = Menu;
		
		// SNAPPI.MenuAUI
		// global lookup by CSS ID, or cfg.lookup_key
		Menu.CFG = {
			'menu-header-markup': CFG_Menu_Header,
			'menu-header-create-markup': CFG_Menu_Header_Create,
			'menu-header-help-markup': CFG_Menu_Header_Help,
			'menu-item-header-markup': CFG_Menu_Item_Header,
			'menu-photoPreview-actions': CFG_Menu_PreviewPhoto_Actions,
			'menu-pagemaker-selected-create-markup': CFG_Menu_Pagemaker_Create, 
			'menu-select-all-markup': CFG_Menu_SelectAll,
			'menu-lightbox-organize-markup': CFG_Menu_Lightbox_Organize,
			'menu-lightbox-share-markup': CFG_Menu_Lightbox_Share,
			'menu-sign-in-markup': CFG_Menu_SignIn,
			'menu-uploader-batch-markup': CFG_Menu_Uploader_Batch,		
			'menu-uploader-folder-markup': CFG_Menu_Uploader_Folder,
			// context menus
			'contextmenu-photoroll-markup': CFG_Context_Photoroll,
			'contextmenu-photoroll-markup-workorder': CFG_Context_Photoroll,
			'contextmenu-hiddenshot-markup': CFG_Context_HiddenShot,
			'contextmenu-hiddenshot-markup-workorder': CFG_Context_HiddenShot,
			'contextmenu-group-markup': CFG_Context_FigureBox,		
			'contextmenu-person-markup': CFG_Context_FigureBox,
			'contextmenu-collection-markup': CFG_Context_FigureBox,
			// Workorder Managment System
			'menu-header-wms-markup': CFG_Menu_Header_WMS,
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
				 Menu.menuItem_beforeShow(menuTarget, e);
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
		var container = cfg.container || _Y.one('#markup');	
		var selector = cfg.selector || '#'+cfg.css_id;	// selector for markup
		var markup = container.one(selector);
		if (markup) {
			callback.call(this, cfg, markup);
			return markup;
		}
		
		var markupNode = _Y.Node.create("<div></div>");
		container.append(markupNode);
		var ioCfg = {
				uri: cfg.uri,
				autoLoad: true,
				showLoading:false, 
				context: this,
				arguments: {
					cfg: cfg,
					parent: markupNode,
					callback: callback,
				},
				on: {
					success: function(e, id, o, args) {
						SNAPPI.setPageLoading(false);
						var markup = _Y.Node.create(o.responseText);
						args.parent.setContent(markup);
						args.callback.call(this, args.cfg, markup);
						return false;	
					}
				},
		};		
		SNAPPI.setPageLoading(true);
		markupNode.plug(_Y.Plugin.IO, ioCfg);
		return false;	
	};
	/**
	 * load Menus on page 
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
		
		if (!SNAPPI.isAIR) {
			defaultMenus = {'menu-header-help-markup': 1};
			try {
				if (/^(MANAGER|EDITOR)$/.test(SNAPPI.STATE.controller.ROLE)) {
					defaultMenus['menu-header-wms-markup'] = 1;	
				} else {
					defaultMenus['menu-header-create-markup'] = 1;	
				}
			} catch(e) {
				throw "Error: SNAPPI.STATE.controller.ROLE not set";
			}
		}	
		if (auth) {
			defaultMenus['menu-header-markup'] = 1;
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
		cfg = cfg || {};
		var key = cfg.lookup_key || MARKUP.id;
		if (Menu.find[key]) {	// doublecheck to catch race condition
			return Menu.find[key];	
		}
		
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
		_cfg = _Y.merge(DEFAULT_CFG_contextmenu, cfg, _cfg);
		if (cfg.currentTarget)	_cfg.trigger = cfg.currentTarget;	// 'startup/disabled' trigger

		var sticky, menu = new _Y.OverlayContext(_cfg);
		menu.render();
		menu.get('contentBox').removeClass('hide');
		if (cfg.init_hidden === false) menu.show();
		if (cfg.currentTarget) {
			if (sticky) menu.set('trigger', TRIGGER);	// 'sticky' trigger
			menu._stashTrigger = TRIGGER;
		}
		menu.on('visibleChange',
			function(e){
				if (e.newVal == true && e.prevVal== false) {
					_Y.fire('snappi:menu-visible', menu, true, cfg);
				} else if (e.newVal == false && e.prevVal== true) {
					_Y.fire('snappi:menu-visible', menu, false, cfg);
					if (_cfg.currentTarget) {  // closure
						// disable contextmenu for menu.onHide
						if (sticky) return;		// TODO: set up sticky in contextmenu
						e.target.disable();
						e.target.set('trigger', '#blackhole');
					}
				}					
			}
		, menu);
		if (!menu.get('disabled')) {
			Menu.startListener(menu, cfg.handle_click ); 
		}
		// add lookup reference
		
		Menu.find[key] = menu;
		if (cfg.host) cfg.host.ContextMenu = menu; 		// add back reference
		return menu;
	};
	/*
	 * add offset to menu alignment positioning
	 * 	NOTES: must use cfg.constrain = false for A.OverlayContext
	 * 		for PhotoRoll, change alignment to .FigureBox > Figure > Img BEFORE calling
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
		var menu = (menu_ID instanceof _Y.OverlayContext) ? menu_ID : Menu.find[menu_ID];
		if (e && menu.get('disabled')) {
			menu.enable();
			var trigger = e.currentTarget.hasClass('FigureBox') ? e.currentTarget : e.currentTarget.ancestor('.FigureBox');
			// align to FigureBox > Figure > Img
			menu.set('trigger', trigger.one('> Figure > Img'));			// 'startup/disabled' trigger
			menu.show();
			// TODO: add checkbox for sticky
			if (typeof sticky !== 'undefined') {
				menu.set('trigger', menu._stashTrigger); 		// 'enabled' trigger
			}
		} else {
			menu.disable();
			menu.hide();
			menu.set('trigger', '#blackhole');
		}
		return menu;
	};
	
	Menu.startListener = function(menu, handle_click, proxy){
		var delegateHost = proxy ? proxy : menu.get('contentBox');
		handle_click = handle_click || function(e){
			var menuItem = e.currentTarget;
			if (menuItem.hasClass('disabled')) {
				// check for disabled
				e.preventDefault();
				return;
			} 
			var methodName = menuItem.getAttribute('action')+'_click';
			if (MenuItems[methodName]) {
				e.stopImmediatePropagation();	
				// e.preventDefault();
				MenuItems[methodName](menuItem, this, e);
			} else {
				// default
				try {
					// no special clickhandler, so just find a.href
					var next = menuItem.one('a').getAttribute('href');
					menuItem.addClass('clicked');
					var target = menuItem.one('a').getAttribute('target');
					if (target) {	// open in a popup window
						var delayed = new _Y.DelayedTask( function() {
							menu.hide();
							menuItem.removeClass('clicked');
							SNAPPI.setPageLoading(false);
						});
						delayed.delay(100);	
					} else SNAPPI.setPageLoading(true);
				} catch (e) {}
			}
		};
			
		menu.listen = menu.listen || {};
		if (proxy) {
			// detach, if delegateHost is not contained 
			var j, detachHost;
			for (j in menu.listen ) {
				detachHost = menu.listen[j].evt.el;
				if (!detachHost.ynode().contains(proxy)) {
					console.log("menu.id="+menu._yuid+", header="+delegateHost._yuid+", container="+delegateHost.get('parentNode')._yuid);		
					menu.listen[j].detach();
					delete (menu.listen[j]);
				}
			}
		}
		if (!menu.listen['delegate_click']) {	
			menu.listen['delegate_click'] = delegateHost.delegate('click', handle_click, 'ul  li',  menu);
		} else 
console.log("delegateHost="+delegateHost._yuid);		
		if (proxy && !menu.listen['mouseenter_beforeShow']) {
			menu.listen['mouseenter_beforeShow'] = delegateHost.on('mouseenter', 
			function(e){
				Menu.menuItem_beforeShow(proxy, null);
			}, 'ul  li',  menu);
		}
	};
	
	Menu.menuItem_beforeShow = function(menu, o){
		var content = menu.get('contentBox') || menu;
		if (content) content.all('ul  li.before-show').each(function(n,i,l){
			// call beforeShow for each menuItem
				var methodName = n.getAttribute('action')+'_beforeShow';
				if (MenuItems[methodName]) {
					try {
						MenuItems[methodName](n, menu, o);	
					} catch (e) {}
				}
		}, menu);
	};
	Menu.copyMenuToDialogHeader = function(CSS_ID, menu){
		var dialog = SNAPPI.Dialog.find['dialog-alert'],
			header = dialog.getStdModNode('header');
			menu = menu || Menu.find[CSS_ID];
		if (menu && !header.one('.'+CSS_ID)){
			var menuContent = menu.get('contentBox');	// get menuContent
			var after = header.one('span.aui-toolbar');
			var copied = header.create(menuContent.get('innerHTML'));
			copied.addClass(CSS_ID).addClass('toolbar');
			header.insertBefore(copied, after);
			Menu.startListener(menu, null, header.get('parentNode') );	
			menu.disable().hide();
			menu.set('trigger', '#blackhole');
			var check;							
		}
	}
	
	
	
	
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
	MenuItems.confirmAuth = function(menuItem){
		try { // authenticated
			var auth = false,
				role = SNAPPI.STATE.controller.ROLE;
			if (SNAPPI.STATE.controller.userid) auth = true;
			if (/(EDITOR|MANAGER)/.test(role)) auth = true;
			if (!auth) {
				menuItem.addClass('disabled').set('title', 'Sign in to perform this action'); 
				return false;	
			}
			return true;
		} catch(e){
			return false;
		}
	};
	MenuItems.authenticated_beforeShow = function(menuItem, menu){
		try {
			if (SNAPPI.STATE.controller.userid) {
				menuItem.removeClass('disabled').show();
			} else {
				menuItem.addClass('disabled').setAttribute('title','Please sign in to access this feature.');
			}
		} catch(e) {
			menuItem.addClass('disabled').setAttribute('title','Please sign in to access this feature.');
		}
	};
	MenuItems.select_all_pages_beforeShow = function(menuItem, menu){
		var target = menu.get('currentNode');	// target
		// gallery only, not lighbox
		try {
			var isPaged = target.ancestor('.gallery-container').one('.aui-paginator-container');
			if (!isPaged) {
				if (SNAPPI.STATE.selectAllPages == true) {
					menuItem.addClass('selected');
				} else menuItem.removeClass('selected');
			}
			menuItem.removeClass('hide');
		} catch(e) {
			menuItem.addClass('hide');
		}
	};	
	MenuItems.select_all_pages_click = function(menuItem, menu){
		MenuItems.select_all_click(menuItem, menu);
		SNAPPI.STATE.selectAllPages = true;
		menuItem.addClass('selected');
		menu.hide();
	};		
	MenuItems.select_all_click = function(menuItem, menu){
		var target = menu.get('currentNode');	// target
		var cb = target.one('input[type="checkbox"]');
		if (cb) {
			cb.set('checked', true);
			var container, gallery = cb.ancestor('section').next('section.gallery');
			try {
				container  = gallery.Gallery.container;	
			} catch (e) {
				container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet
			}
			container.all('.FigureBox').addClass('selected');
		}
		try {
			target.ancestor('section#lightbox').Lightbox.save();
		} catch (e) {}
		menu.hide();
	};
	MenuItems.clear_all_click = function(menuItem, menu){
		var target = menu.get('currentNode');	// target
		var cb = target.one('input[type="checkbox"]');
		if (cb) {
			cb.set('checked', false);
			var container, gallery = cb.ancestor('section').next('section.gallery');
			try {
				container  = gallery.Gallery.container;	
			} catch (e) {
				container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet
			}
			container.all('.FigureBox').removeClass('selected');
		}		
		SNAPPI.STATE.selectAllPages = false;
		try {
			menuItem.previous('.selected').removeClass('selected');
			target.ancestor('section#lightbox').Lightbox.save();
		} catch (e) {}
		menu.hide();
	};
	/*
	 * remove from Lightbox
	 * NOTE: see also MenuItems.remove_from_uploader_selected_click()
	 * 		MenuItems.delete
	 */
	MenuItems.remove_selected_beforeShow = function(menuItem, menu){
		try {
			var target = menu.get('currentNode');	// target
			var lightbox = target.ancestor('section#lightbox').Lightbox;
			var isSelected = lightbox.Gallery.container.all('.selected');
			var label = isSelected.size() ? 'Remove Selected Snaps': 'Remove All Snaps';
			menuItem.set('innerHTML', label);
			menuItem.removeClass('hide');
		} catch (e) {
			menuItem.addClass('hide');
		}
	};	
	MenuItems.remove_selected_click = function(menuItem, menu){
		try {
			// lightbox only
			var target = menu.get('currentNode');	// target
			var lightbox = target.ancestor('section#lightbox').Lightbox;
			lightbox.clear();
			lightbox.save();
			lightbox.updateCount();
		} catch (e) {}
		menu.hide();
	};
	/**
	 * for SNAPPI AIR Uploader
	 */	
	MenuItems.retry_selected_beforeShow = function(menuItem, menu){
		try {
			var target = menu.get('currentNode');	// target
			// used by SNAPPI.uploader to retry in uploadQueue
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) { 
				if (target.hasClass('status-cancelled') || target.hasClass('status-error')){
					menuItem.removeClass('disabled');
				} else menuItem.addClass('disabled');
				menuItem.removeClass('hide');
				return;
			}			
			var isSelected, container, 
				gallery = target.ancestor('section').next('section.gallery');
			container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet
			isSelected = container.all('.FigureBox.selected');
			var label = isSelected.size() ? 'Retry Selected Snaps': 'Retry All Snaps';
			menuItem.set('innerHTML', label);
			menuItem.removeClass('hide');
			return;
		} catch (e) {	}
		menuItem.addClass('hide');
	};	
	MenuItems.retry_selected_click = function(menuItem, menu){
		try {
			// used by SNAPPI.uploader to retry in uploadQueue
			var isSelected, target = menu.get('currentNode');	// cancel target only
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) { 
				isSelected = _Y.all(target);
			} else {
				var gallery, container;
				gallery = target.ancestor('section').next('section.gallery');
				container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet			
				isSelected = container.all('.FigureBox.selected');
			}	
			var uploader = SNAPPI.AIR.uploadQueue;
			isSelected.each(function(n,i,l){
				uploader.action_retry(n);
				n.removeClass('selected');
			});
		} catch (e) {}
		menu.hide();
	};	
	/**
	 * for SNAPPI AIR Uploader
	 */
	MenuItems.cancel_selected_beforeShow = function(menuItem, menu){
		try {
			// used by SNAPPI.uploader to retry in uploadQueue
			var isSelected, target = menu.get('currentNode');
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) { 
				// enabled for 'status-pending' 'status-paused'
				if (target.hasClass('status-pending') || target.hasClass('status-paused') || target.hasClass('status-active')) {
					menuItem.removeClass('disabled');
				} else menuItem.addClass('disabled');
				menuItem.removeClass('hide');
				return;
			}					
			var isSelected, container, 
				gallery = target.ancestor('section').next('section.gallery');
			container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet
			isSelected = container.all('.FigureBox.selected');
			var label = isSelected.size() ? 'Cancel Selected Snaps': 'Cancel All Snaps';
			menuItem.set('innerHTML', label);
			menuItem.removeClass('hide');
			return;
		} catch (e) {	}
		menuItem.addClass('hide');
	};	
	MenuItems.cancel_selected_click = function(menuItem, menu){
		try {
			// used by SNAPPI.uploader to retry in uploadQueue
			var isSelected, target = menu.get('currentNode');	// cancel target only
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) { 
				// if status allows cancel, then
				isSelected = _Y.all(target);
			} else {
				var gallery, container;
				gallery = target.ancestor('section').next('section.gallery');
				container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet			
				isSelected = container.all('.FigureBox.selected');
			}	
			var uploader = SNAPPI.AIR.uploadQueue;
			isSelected.each(function(n,i,l){
				uploader.action_cancel(n);
				n.removeClass('selected');
			});
		} catch (e) {}
		menu.hide();
	};	
	/**
	 * for SNAPPI AIR Uploader
	 */	
	MenuItems.remove_from_uploader_selected_click = function(menuItem, menu){
		try {
			// used by SNAPPI.uploader to remove from uploadQueue
			var isSelected, response, target = menu.get('currentNode');	// cancel target only
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) { 
				// if status allows cancel, then
				isSelected = _Y.all(target);
				response = confirm('Are you sure you want to remove this Snap?');
			} else {
				// selected
				gallery = target.ancestor('section').next('section.gallery');
				container = gallery;	// TODO: uploader does not use SNAPPI.Gallery yet			
				isSelected = container.all('.FigureBox.selected');
				response = confirm('Are you sure you want to remove all selected Snaps?');
			}	
			if (!response) {
				// Cancel remove:  remove .selected
				isSelected.removeClass('selected');
			} else {
				SNAPPI.AIR.uploadQueue.action_remove(isSelected);
			}
		} catch (e) {}
		menu.hide();
	};	
	MenuItems.delete_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		try {
			var target = menu.get('currentNode'),	// target
				enabled = true,
				isLightbox, g, 
				isContextMenu = menuItem.ancestor('#contextmenu-photoroll-markup');
			if (isContextMenu) {
				// context menu
			} else { // from selectAll
				g = target.ancestor('section').next('section.gallery').Gallery;
				enabled = g.getSelected().size();
				isLightbox = !isContextMenu && g._cfg.type == "Lightbox";
			} 			
			// TODO: how do you know if it is the ADMIN/EDITOR user from JS?
			// check if we have write permission?
			if (isLightbox || SNAPPI.STATE.controller.alias == 'my' ) {
				switch (SNAPPI.STATE.controller.action) {
					case 'home':
					case 'photos':
						menuItem.removeClass('hide');
						if (enabled) menuItem.removeClass('disabled');
						else menuItem.addClass('disabled');
						return;
				}
			}
			menuItem.addClass('hide');
		} catch (e) {
			menuItem.addClass('hide');
		}
	};	
	MenuItems.delete_click = function(menuItem, menu, e){
		var g, response, selected, isSelected, isContextMenu, isSelectAll, isLightbox,
			thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		// contextmenu or selectall menu
		isContextMenu = menuItem.ancestor('#contextmenu-photoroll-markup')
		isSelectAll = menuItem.ancestor('#menu-select-all-markup');
		try {
			g = (isContextMenu) ? 
				MenuItems.getGalleryFromTarget(thumbnail)
				: thumbnail.ancestor('section').next('section.gallery').Gallery;  // from Gallery.selectAll
			isLightbox = !isContextMenu && g._cfg.type == "Lightbox";
		} catch(e) {
			console.warn("Error: parsing attributes for MenuItem.delete_click");
			g=null;
			isLightbox = false;
		}
		if (!isLightbox && SNAPPI.STATE.controller.alias != 'my' ) {
			// TODO: allow delete by Role=EDITOR, etc.
			menu.hide();
			return;
		}
		// single or batch operation
		if (g && (isSelectAll || (isContextMenu && e.shiftKey))) {
			selected = g.getSelected();		// BATCH delete
		} else selected = new SNAPPI.SortedHash(null, SNAPPI.Auditions.find(thumbnail.get('uuid')));
		menu.hide();
		var cfg = {
				selector: '#markup .confirm-remove-snaps',
				uri: '/help/markup/dialogs',
				buttons: SNAPPI.Dialog.BUTTONS_OK_CANCEL
			};
		cfg.buttons[0].handler = function(e){
			var content = this.getStdModNode('body');
			if (!content.one('.confirm-remove-snaps')) return;	// WRONG DIALOG
			_Y.once('snappi:set-property-complete', function(){
				this.hide();
			}, this);
			g.deleteThumbnail(selected, content.one('.confirm-remove-snaps'));
		}
		return SNAPPI.Alert.load(cfg);
	};	
	MenuItems.preview_delete_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		try {
			var target = menu.get('currentNode'),	// target
				enabled = true;
			target = target.ancestor('.FigureBox');
			var audition = SNAPPI.Auditions.find(target.uuid);
			if (audition && audition.isOwner) {
				menuItem.removeClass('hide');
				if (enabled) menuItem.removeClass('disabled');
				else menuItem.addClass('disabled');
			} else menuItem.addClass('disabled');
		} catch (e) {
			menuItem.addClass('hide');
		}
	};	
	MenuItems.preview_delete_click = function(menuItem, menu){
		// preview has no gallery association. delete directly
		var node = menu.get('currentNode').ancestor('.FigureBox');
		var aids = [node.uuid];
		SNAPPI.AssetPropertiesController.deleteByUuid( node, {
			ids: aids.join(','), 
			actions: {'delete':1},
			context: menu,
			callbacks: {
				successJson: function(e, args){
					menu.hide();
					var aud,  
						auditions = SNAPPI.Auditions._auditionSH,
						shots = SNAPPI.Auditions._shotsSH;
					for (var i in aids) {
						aud = SNAPPI.Auditions.find(aids[i]);
						SNAPPI.Auditions.unbind(aud);
						auditions.remove(aud);
						try {
							shots.remove(aud.Audition.Shot.id);	
						} catch(e) {}
					}
					// click Next btn if gallery found
					var g= SNAPPI.Gallery.find['nav-'] ;
					if (g && g.container.one('.FigureBox.Photo')) {
						SNAPPI.Factory.Thumbnail['PhotoPreview'].next.call(node, 'next', null, node);	
					} else {
						SNAPPI.flash.flash("Snap removed.");
						_Y.later(2000, this, function(){
							window.location.href = '/my/photos';
						})
					}
					return false;
				}
			}
		});		
	};		
	// formerly _getPhotoRoll(), currently unused
	MenuItems.getGalleryFromTarget = function(target){
		if (target instanceof _Y.OverlayContext) {
			target = target.get('currentNode');	// contextmenu target
		}
		return SNAPPI.Gallery.getFromChild(target);
		// var g = false; 
		// var n = target.ancestor(
				// function(n){
					// g = n.Gallery || n.Gallery ||  (n.Lightbox && n.Lightbox.Gallery) || null; 
					// return g;
				// }, true );
		// return g;
	};	
	MenuItems.rating_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		if (!menuItem.Rating) {
			// add new rating group as LI > DIV
			menuItem.setAttribute('uuid', audition.id);
			SNAPPI.Rating.pluginRating(menuItem, menuItem, audition.rating);
			// rating handler: Rating.postRatingChangeAndCleanup()
			SNAPPI.Rating.startListeners(menuItem);
			menuItem.one('.ratingGroup').setAttribute('id', 'menuItem-contextRatingGrp');
		} else {
			var ratingCfg = {
					v : audition.rating,
					uuid : audition.id,		// TODO: audition.id or audition.Audition.id???
					listen : false
				};
			var r = SNAPPI.Rating.attach(menuItem, ratingCfg);
			r.thumbnail = thumbnail;
		}
	};

	MenuItems.batch_rating_beforeShow = function(menuItem, menu){
		if (!menuItem.Rating) {
			// add new rating group as LI > DIV
			var cfg = {
				v : 0,
				uuid : false,
				'applyToBatch' : SNAPPI.lightbox.applyRatingInBatch
			};
			SNAPPI.Rating.attach(menuItem, cfg);			
			menuItem.Rating.node.set('id', 'lbx-rating-group');
			SNAPPI.Rating.startListeners(menuItem);
		} else {
			menuItem.Rating.render(0);
		}
	};
		
	MenuItems.tag_beforeShow = function(menuItem, menu){
		if (!menuItem.one('input#lbx-tag-field')) {
			// var self = SNAPPI.lightbox; 
			// self.renderTagInput(menuItem);
		} else {
			// reset tag
			var input = menuItem.one('input#lbx-tag-field');
			input.set('value', 'Enter tags').addClass('help');
			menuItem.listen = menuItem.listen || {};
			if (!menuItem.listen.click) 
				menuItem.listen.click = menuItem.one('input[type=submit]').on(
					'click',
					function(e){
						SNAPPI.lightbox.applyTagInBatch(e.currentTarget);
					});
		}
	};	
	MenuItems.openJpg_beforeShow = function(menuItem, menu, e){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		if (e.ctrlKey) var src = audition.Audition.Photo.Img.Src.rootSrc;
		else var src = audition.Audition.Photo.Img.Src.rootSrc;
		menuItem.one('a').setAttribute('href', audition.getImgSrcBySize(audition.urlbase+src,'bp'));
	};
	MenuItems.autorotate_beforeShow = function(menuItem, menu, e){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		if (e.ctrlKey) var src = audition.Audition.Photo.Img.Src.rootSrc;
		else var src = audition.Audition.Photo.Img.Src.rootSrc;
		menuItem.one('a').setAttribute('href', audition.urlbase+src);
	};
	MenuItems.zoom_click = function(menuItem, menu, e){
		// menu.hide();
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		// for testing: ctrl-click to open JPG in new tab
		if (e.ctrlKey || e.metaKey) {
			var src = audition.Audition.Photo.Img.Src.rootSrc;
			src = audition.getImgSrcBySize(audition.urlbase+src,'bp');
			menu.hide(); // open JPG in popup
			window.open(src, '_blank');
			return;
		}
		var g = MenuItems.getGalleryFromTarget(thumbnail);
		SNAPPI.Factory.Gallery.nav.toggle_ContextMenu(g, e);
		var cfg = {
			// selector: [CSS selector, copies outerHTML and substitutes tokens as necessary],
			markup: "<div id='preview-zoom' class='preview-body'></div>",
			uri: '/combo/markup/null',
			height: 400,
			width: 400,
			skipRefresh: true,
		};
		var dialog = SNAPPI.Alert.load(cfg); // don't resize yet
		var previewBody = dialog.getStdModNode('body').one('.preview-body');
		_Y.once('snappi:preview-change', 
	        	function(thumb){
	        		if (thumb.Thumbnail._cfg.type == 'PhotoZoom' ) {
	        			_Y.fire('snappi:dialog-body-rendered', dialog);
	        		}
	        	}, '.FigureBox.PhotoZoom figure > img', dialog
	        )		
		SNAPPI.Factory.Thumbnail.PhotoZoom.bindSelected(audition, previewBody, {gallery:g});
		return false;
	}
	MenuItems.linkTo_click = function(menuItem, menu, e){
		// menu.hide();
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var g = MenuItems.getGalleryFromTarget(thumbnail);
		var linkTo = thumbnail.one('img').getAttribute('linkTo');
		if (g.castingCall.CastingCall) {
        	linkTo += '?ccid=' + g.castingCall.CastingCall.ID;
			try {
				var shotType = gcastingCall.CastingCall.Auditions.ShotType;
				if (shotType == 'Groupshot'){
					linkTo += '&shotType=Groupshot';
				}
			} catch (e) {}
        }
		window.location.href = linkTo;
	}
	MenuItems.workorder_linkTo_click = function(menuItem, menu, e){
		// menu.hide();
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var g = MenuItems.getGalleryFromTarget(thumbnail);
		var linkTo = thumbnail.one('img').getAttribute('linkTo');
		linkTo = linkTo.replace('/photos/home', _Y.Lang.sub('/{alias}/snap', SNAPPI.STATE.controller));
		if (g.castingCall.CastingCall) {
        	linkTo += '?ccid=' + g.castingCall.CastingCall.ID;
			try {
				var shotType = g.castingCall.CastingCall.Auditions.ShotType;
				if (shotType == 'Groupshot'){
					linkTo += '&shotType=Groupshot';
				}
			} catch (e) {}
        }
		window.location.href = linkTo;
	}
	MenuItems.refresh_click = function(menuItem, menu, e){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var img = thumbnail.one('figure > img');
		img.once('load', function(){
			SNAPPI.setPageLoading(false);
		});
		SNAPPI.setPageLoading(true);
		img.set('src', img.get('src')+'?t='+new Date().getTime());
		_Y.later(500, menu, function(){this.hide();});
	}	
	MenuItems.rotate_click = function(menuItem, menu, e){
		var rotate = menuItem.getAttribute('rotate');
		var thumbnail = menu.get('currentNode');
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var selected = thumbnail.uuid;
		if (e.shiftKey) {
			selected = MenuItems.getGalleryFromTarget(thumbnail).getSelected();			
		} 
		var options = {
			ids: selected,	// id or array of ids
			properties: {'rotate': rotate},
			actions: null,
			callbacks: {
				successJson: function(e, i, o,args){
					var resp = o.responseJson;
					var uuid = resp.response.uuid;
					if (_Y.Lang.isString(uuid)) uuid = [uuid];
					// reset all .FigureBoxes
					for (var j in uuid) {
						try {
							var img, src, audition = SNAPPI.Auditions.find(uuid[j]);
							for (var i in audition.bindTo) {
								if (audition.bindTo[i].hasClass('FigureBox')) {
									img = audition.bindTo[i].one('figure > img');
									src = img.get('src');
									img.set('src', src + '?rand=' + Math.random());
								}
							}
						} catch (e) {}
					}
					args.loadingmask.hide();
					return false;
				}, 
				complete: function(e, i, o, args) {
					args.loadingmask.hide();
				},
				failure : function (e, i, o, args) {
					// post failure or timeout, status=403 forbidden handled by pluginIO_RespondAsJson()
					var resp = o.responseJson || o.responseText || o.response;
					var msg = resp.message || resp;
					// TODO: flash error message
					if (console) console.error("ERROR: setProp() - "+msg);
					args.loadingmask.hide();
					return false;
				},
			},
		}
		SNAPPI.AssetRatingController.setProp(menuItem, options);
	};
	// deprecated. use click on hiddenshot icon instead
	MenuItems.showHiddenShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
    	try {
    		var shotId = audition.Audition.Substitutions.id;
    		if (!shotId) menuItem.hide();
    		else if (/(nav-)|(shot-)/.test(thumbnail.Thumbnail._cfg.ID_PREFIX)) menuItem.hide();
    		else menuItem.show();
		}catch(e){
			menuItem.hide();
		}		
	};
	
	MenuItems.showHiddenShot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		try {
			var g = MenuItems.getGalleryFromTarget(menu);
			
			// new pattern, reuse Thumbnail.PhotoPreview
			_Y.once('snappi:dialog-body-rendered', function(){
				menu.hide();
			});
			SNAPPI.Helper.Dialog.bindSelected2DialogHiddenShot(g, audition);
		} catch (e) {
		}	
	};

	MenuItems.groupAsShot_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var g = MenuItems.getGalleryFromTarget(thumbnail);
		try {
			// check if the user has permission to groupAsShot
			var hasPerm = g.castingCall.CastingCall.GroupAsShotPerm,
				shotType = g.castingCall.CastingCall.Auditions.ShotType;
		} catch (e) {}
		if (hasPerm && shotType && thumbnail.hasClass('selected')) {
			if (g.getSelected().count()>1) {
				menuItem.removeClass('disabled');
				return;
			}
		}
		menuItem.addClass('disabled');
	};	
	MenuItems._isShowHidden = function(g){
		g = g || this;
		var ShowHidden;
		if (/ShotGalleryShot/.test(g._cfg.type)) {
			ShowHidden = PAGE.jsonData.shot_CastingCall.CastingCall.ShowHidden ? 1 : 0;
		} else if (/DialogHiddenShot/.test(g._cfg.type)) {
			ShowHidden = 1;  // cc comes from /photos/hiddenshots, cc not cached in PAGE.jsonData
		} else { // raw=[0|1]
			ShowHidden = PAGE.jsonData.castingCall.CastingCall.ShowHidden ? 1 : 0;
		}
		return ShowHidden;
	}
	MenuItems.groupAsShot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		try {
			// from thumbnail context-menu
			var g = MenuItems.getGalleryFromTarget(menu);
			if (!g.castingCall.CastingCall.GroupAsShotPerm) return;
			var shotType = g.castingCall.CastingCall.Auditions.ShotType;
			var options = {
				menu: menu,
				loadingNode: menuItem,
				ShowHidden: MenuItems._isShowHidden(g),
				shotType: shotType,
			};
			// get userid or group_id for shot
			if (/Group/.test(SNAPPI.STATE.controller['class'])) {
				options.group_id = SNAPPI.STATE.controller.xhrFrom.uuid;
			}
			g.groupAsShot(null, options);
			return;
		} catch (e) {}	
	};	
	
	MenuItems.lightbox_group_as_shot_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		// from lightbox menuItem
		// check if the user has permission to groupAsShot
		var hasPerm = g.castingCall.CastingCall.GroupAsShotPerm,
			shotType = g.castingCall.CastingCall.Auditions.ShotType;
		var lightbox = menu.get('currentNode').ancestor('#lightbox').Lightbox;
		if (lightbox && lightbox.getSelected() && hasPerm && shotType){
			menuItem.removeClass('disabled');	
		} else menuItem.addClass('disabled');
		
	}
	MenuItems.lightbox_group_as_shot_click = function(menuItem, menu){
		try {
			// from lightbox menuItem
			var lightbox = menu.get('currentNode').ancestor('#lightbox').Lightbox;
			try {
				var shotType = 'unknown';		// lightbox photos can be from group or user 
				// check if the user has permission to groupAsShot
				if (!g.castingCall.CastingCall.GroupAsShotPerm) return;
				// shotType = g.castingCall.CastingCall.Auditions.ShotType
			} catch (e) {}				
			// shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
			var batch = lightbox.getSelected();
			// TODO: ??? lightbox.groupAsShot() or lightbox.Gallery.groupAsShot()??? 
			lightbox.Gallery.groupAsShot(batch, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				lightbox: lightbox,				// remove hiddenshot-hide from lightbox
				ShowHidden: MenuItems._isShowHidden(lightbox.Gallery),  // TODO: TEST
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});	
			return;		
		} catch (e) {}		
	}
	
	MenuItems.removeFromShot_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		try {
			// check if the user has permission to groupAsShot
			var hasPerm = g.castingCall.CastingCall.GroupAsShotPerm,
				shotType = g.castingCall.CastingCall.Auditions.ShotType;
		} catch (e) {}		
		// var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
		if (hasPerm && shotType && thumbnail.hasClass('selected')) {
			var g = MenuItems.getGalleryFromTarget(thumbnail);
			if (g.getSelected().count()>=1) {
				menuItem.removeClass('disabled');
				return;
			}
		}
		menuItem.addClass('disabled');
	};	
	
	MenuItems.removeFromShot_click = function(menuItem, menu){
		var batch, options, thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		var g = MenuItems.getGalleryFromTarget(menu);
		if (!g.castingCall.CastingCall.GroupAsShotPerm) return;
		var shotType = audition.Audition.Substitutions.shotType;
		// if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
		if (!shotType) {
			if (console) console.error("ERROR: shotType unknown in MenuItems.setBestshot_click()");
			return;
		}			
		batch = g.getSelected();
		// count remaining assets
		if (batch.count()==0) batch.add(audition);
		var remaining = g.auditionSH.count() - batch.count();
		options =  {
			menu: menu,
			loadingNode: menuItem,
			shotType: shotType,
			ShowHidden: MenuItems._isShowHidden(g),
		};
		if (/Group/.test(SNAPPI.STATE.controller['class'])) {
			options.group_id = SNAPPI.STATE.controller.xhrFrom.uuid;
		}		
		if (g._cfg.type == 'ShotGalleryShot' ) {
			options.success = function(e, id, o, args) {
				// mark shot as stale or update shot_id
				var check;
				return false;
			}
		}
		if (remaining > 1) {
			g.removeFromShot(batch, options);
		} else {
			g.unGroupShot(batch, options);			
		}
	};
	
	MenuItems.ungroupShot_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		try {
			// check if the user has permission to groupAsShot
			var g = MenuItems.getGalleryFromTarget(menu);
			// check if the user has permission to groupAsShot
			var hasPerm = g.castingCall.CastingCall.GroupAsShotPerm,
				shotType = g.castingCall.CastingCall.Auditions.ShotType;
			// var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
			if (hasPerm && shotType) {
	    		var shotId = audition.Audition.Substitutions.id;
	    		if (shotId) {
	    			menuItem.removeClass('disabled');
	    			return;
	    		}
	    	}
		}catch(e){	}
		menuItem.addClass('disabled');		
	};
		
	MenuItems.ungroupShot_click = function(menuItem, menu){
		var batch, options, thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		var g = MenuItems.getGalleryFromTarget(menu);
		if (!g.castingCall.CastingCall.GroupAsShotPerm) return;
		var shotType = audition.Audition.Substitutions.shotType;
		// if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
		if (!shotType) {
			if (console) console.error("ERROR: shotType unknown in MenuItems.setBestshot_click()");
			return;
		}			
		batch = g.getSelected();
		if (batch.count()==0) batch.add(audition);
		options =  {
			menu: menu,
			loadingNode: menuItem,
			shotType: shotType,
			ShowHidden: MenuItems._isShowHidden(g),
		};
		if (/Group/.test(SNAPPI.STATE.controller['class'])) {
			options.group_id = SNAPPI.STATE.controller.xhrFrom.uuid;
		}
		if (g._cfg.type == 'ShotGalleryShot' ) {
			options.success = function(e, id, o, args) {
				// mark shot as stale or update shot_id
				var check;
				return false;
			}
		}
		g.unGroupShot(batch, options);
	};
	
	MenuItems.setBestshot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		var g = MenuItems.getGalleryFromTarget(menu);
		var shotType = audition.Audition.Substitutions.shotType;
		if (!shotType) {
			if (console) console.error("ERROR: shotType unknown in MenuItems.setBestshot_click()");
			return;
		}
		g.setBestshot(thumbnail, {
			menu: menu,
			loadingNode: menuItem,
			shotType: shotType
		});
	};	
	MenuItems.create_pagegallery_click = function(menuItem, menu){
		try {
			var g = SNAPPI.Gallery.find['uuid-'] || SNAPPI.Gallery.find['nav-'];
			// check .gallery.photo, then lightbox for selected 
			var batch = g.getSelected();
			if (!batch.count() && SNAPPI.lightbox) {
				batch = SNAPPI.lightbox.getSelected();
			}	
			if (batch.count()) {
				// something selected. load then launch
				var delayed = new _Y.DelayedTask( function() {
					menu.hide();
				});
				delayed.delay(1000);
				try {
					SNAPPI.UIHelper.create.get_StoryPage();	
				}catch (e) {}
			} else { // nothing selected
				// if NOT launched, then show create new story help if nothing is selected(?)
				var showHint = function(){
					var next = '';
					if (!g) {	// no gallery, redirect to page
						var auth = SNAPPI.STATE.controller.userid; // authenticated
						var target = auth ? '/my/photos' : '/photos/all';
						next = '<div class="center"><a href="'+target+'"><button class="continue orange" type="submit">Show me some Snaps!</button></a></div><br />'
					}			
					// gallery found, but nothing selected, show MultiSelect help
					var cfg = {
						// markup: "<div id='preview-zoom'  class='preview-body'></div>",
						selector: '#hint-new-story',
						uri: '/help/markup/hint_NewStory',
						width: 600,
						addToMarkup: true,
						tokens: {
							next: next
						}
					};
					var dialog = SNAPPI.Alert.load(cfg);
					var detach = _Y.on('snappi:dialog-alert-xhr-complete', function(d){
						detach.detach();
						SNAPPI.util.setForMacintosh(d.getStdModNode('body'));
					});	
				}
				SNAPPI.LazyLoad.extras({module_group:'alert', ready: showHint});
			}
		} catch(e) {	}
		menu.hide();
		return;
	};	
	
	// what's the diff between express_upload and direct_upload
	MenuItems.express_upload_beforeShow = function(menuItem, menu, properties){
		if (!MenuItems.confirmAuth(menuItem)) return;
		// if this group is marked for express-upload, add .selected
		if (!PAGE.jsonData.expressUploadGroups) {
			menuItem.hide();
		} else {
			var isExpress = PAGE.jsonData.expressUploadGroups[properties.id] !== undefined;
			menuItem.origLabel = menuItem.origLabel || menuItem.get('innerHTML');
			if (isExpress) menuItem.setContent('▶'+menuItem.origLabel).setAttribute('title', 'click to disable Express Upload into this Circle');
			else menuItem.setContent(menuItem.origLabel).setAttribute('title', 'Ask to share uploaded photos directly with this Circle');;
			menuItem.show();
		} 			 
	};	
	MenuItems.express_upload_click = function(menuItem, menu, properties){
		var isExpress = menuItem.get('innerHTML')[0] == '▶';
		isExpress = isExpress ? 0 : 1;		// toggle value
		var cfg = {
			gid: properties.id,
			isExpress: isExpress,
			node: menuItem,
			menuItem: menuItem,
		}
		SNAPPI.UIHelper.groups.isExpress(cfg);
				// menuItem.addClass('selected');
	};
	// what's the diff between express_upload and direct_upload
	MenuItems.direct_upload_beforeShow = function(menuItem, menu, properties){
		if (!MenuItems.confirmAuth(menuItem)) return;
		// if this group is marked for express-upload, add 
		var controller = SNAPPI.STATE.controller;
		if (controller.alias == 'my' 
			||(controller['class'] == 'Group' && controller.action !='all') ) 
		{
			menuItem.removeClass('disabled').show();
		} else menuItem.addClass('disabled').hide();	// disabled if not signed in 	
	};
	MenuItems.direct_upload_click = function(menuItem, menu, properties){
		var controller = SNAPPI.STATE.controller;
		if (controller.alias == 'my' 
			||(controller['class'] == 'Group' && controller.action !='all') ) {
			window.location.href = '/groups/upload/'+properties.id;
		} 
	};	
	MenuItems.share_with_this_circle_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		try {
			if (SNAPPI.STATE.controller.name=='Groups') {
				menuItem.removeClass('disabled'); return;
			} 	
		} catch(e){}		
		menuItem.addClass('disabled');
	};	
	MenuItems.share_with_this_circle_click = function(menuItem, menu, e){
		if (!MenuItems.confirmAuth(menuItem)) return;
		try {
			var gid = SNAPPI.STATE.controller.xhrFrom.uuid;	
			SNAPPI.lightbox.applyShareInBatch(gid, menuItem);
		} catch (e) {}
	};	
	MenuItems.share_with_circle_beforeShow = function(menuItem, menu, e){
		if (!MenuItems.confirmAuth(menuItem)) return;
		try {
			auth = SNAPPI.STATE.controller.userid; // authenticated
			if (auth) {
				menuItem.removeClass('disabled'); return;	
			} else menuItem.set('title', 'Sign in to perform this action');
		} catch(e){}
		menuItem.addClass('disabled');
	};
	MenuItems.share_with_circle_click = function(menuItem, menu, e){
		var batch, thumbnail = menu.get('currentNode');
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		if (thumbnail.hasClass('FigureBox')) {	// from PhotoContextMenu
			batch = MenuItems.getGalleryFromTarget(thumbnail).getSelected();
			if (batch.count()==0) {
				batch.add(SNAPPI.Auditions.find(thumbnail.uuid));
			}
		}
			
		/*
		 * create or reuse Dialog
		 */
		var dialog_ID = 'dialog-select-circles';
		var dialog = SNAPPI.Dialog.find[dialog_ID];
		if (!dialog) {
        	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
        	var args = {
        		batch: batch,	// id or array of ids
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
						SNAPPI.setPageLoading(false);
						args.dialog.batch = args.batch;		// selected thumbs to share
						// not reusable, add content directly to dialog
						this.setStdModContent('body', '<div>'+o.responseText+'</div>');
						// start multi-select listener
						var body = this.getStdModNode('body').one('.container');
						var t = body.all('.FigureBox');
						if (t.size()==1) t.item(0).addClass('selected');
						SNAPPI.multiSelect.listen(body, true, SNAPPI.MultiSelect.singleSelectHandler);
						_Y.fire('snappi:dialog-body-rendered', args.dialog);
						return false;
					}					
				}
			};
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
			dialog.plug(_Y.Plugin.IO, ioCfg);
			// dialog_ID == dialog.get('boundingBox').get('id')
			SNAPPI.Dialog.find[dialog_ID] = dialog;
		} else {
			if (!dialog.get('visible')) {
				dialog.setStdModContent('body','<ul />');
				dialog.show();
			}
			dialog.set('title', 'My Circles');
		}
		
		// shots are NOT included. get shots via XHR and render
		var subUri = '/my/groups?preview=1';
		dialog.io.set('uri', subUri );
		// dialog.io.set('arguments', args ); 
		SNAPPI.setPageLoading(true);   			
		menu.hide();
		dialog.io.start();			
	};	
	MenuItems.photo_privacy_beforeShow = function(menuItem, menu){
		if (!MenuItems.confirmAuth(menuItem)) return;
		// TODO: confirm isOwner, if this is for 1 photo
		menuItem.removeClass('disabled');
		// what about a selection?
	}
	MenuItems.photo_privacy_click = function(menuItem, menu){
		var target = menu.get('currentNode');	// target
		if (target.ancestor('#lightbox')) target = target.ancestor('#lightbox').Lightbox;
		/*
		 * create or reuse Dialog
		 */
		var dialog_ID = 'dialog-select-privacy';
		var dialog = SNAPPI.Dialog.find[dialog_ID];
		if (!dialog) {
        	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
        	dialog.addAttr('target', {value:target});
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
						args.menu.hide();
						SNAPPI.setPageLoading(false);
						// add content
						var parent = args.dialog.getStdModNode('body');
						parent.setContent('<div>'+o.responseText+'</div>');
						// multi-select listener started in Dialog.load()
						_Y.fire('snappi:dialog-body-rendered', args.dialog);
						var check = args.dialog.get('target');
						return false;
					}					
				}
			};
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
			dialog.plug(_Y.Plugin.IO, ioCfg);
			// dialog_ID == dialog.get('boundingBox').get('id')
			SNAPPI.Dialog.find[dialog_ID] = dialog;
		} else {
			dialog.set('target', target);
			if (!dialog.get('visible')) {
				dialog.setStdModContent('body','');
				dialog.show();
			}
			dialog.set('title', 'Privacy Settings');
		}
		
		// shots are NOT included. get shots via XHR and render
		var subUri = '/combo/markup/settings_privacy';	// placeholder
		dialog.io.set('uri', subUri );
		// dialog.io.set('arguments', args );
		dialog.io.start();			
	};	
	MenuItems.uploader_setFolder_click = function(menuItem, menu){
		var target = menu.get('currentNode');
		if (menuItem.hasAttribute('batch')) {
			SNAPPI.AIR.UIHelper.set_UploadBatchid(menuItem, target);
		} else SNAPPI.AIR.UIHelper.set_Folder(menuItem, target);
		menu.hide();
	};	
	
	
	MenuItems.settings_beforeShow = function(menuItem, menu, properties){
		if (!MenuItems.confirmAuth(menuItem)) return;
		var target = menu.get('currentNode');
		if (properties.isOwner) menuItem.removeClass('disabled').show();
		else menuItem.addClass('disabled');
	};		
	MenuItems.settings_click = function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		menu.hide();
		// POST to /groups/join
		window.location.href = '/'+SNAPPI.STATE.controller.alias+'/settings/'+properties.id;
	};	
	/*
	 * Group .FigureBox
	 */
	MenuItems.join_beforeShow = function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		// TODO: check privacy/membership settings
		if (!properties.isMember && properties.membership_policy == 1) menuItem.show();
		else menuItem.hide();
	};		
	MenuItems.join_click = function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		menu.hide();
		// POST to /groups/join
		window.location.href = '/groups/join/'+properties.id;
	};		
	MenuItems.invite_beforeShow = function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		if (properties.isOwner || properties.invitation_policy == 1) menuItem.removeClass('disabled');
		else if (properties.isMember && properties.invitation_policy == 2) menuItem.removeClass('disabled');
		else menuItem.addClass('disabled');
	};		
	MenuItems.invite_click = function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		menu.hide();
		window.location.href = '/groups/invite/'+properties.id;
	};		
	MenuItems.unshare_from_group_beforeShow = function(menuItem, menu, properties){
		if (!MenuItems.confirmAuth(menuItem)) return;
		var target = menu.get('currentNode');
		if (target.ancestor('#related-content') && SNAPPI.STATE.controller.name == 'Assets') {
			menuItem.ancestor('.menu-item-group').removeClass('hide');
			menuItem.removeClass('disabled');
		} else {
			menuItem.ancestor('.menu-item-group').addClass('hide');
			menuItem.addClass('disabled');
		}
	};		
	MenuItems.unshare_from_group_click = function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		menu.hide();
	};	
	MenuItems.leave_beforeShow = function(menuItem, menu, properties){
		if (!MenuItems.confirmAuth(menuItem)) return;
		var target = menu.get('currentNode');
		// TODO: check privacy/membership settings
		if (properties.isMember && !properties.isOwner) menuItem.show();
		else menuItem.hide();
	};	
	MenuItems.leave_click= function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		// TODO:
	};	
	
	MenuItems.help_show_hints_beforeShow = function(menuItem, menu){
		if (!SNAPPI.Hint) menuItem.addClass('disabled');
		else menuItem.removeClass('disabled');
	}
	MenuItems.help_show_hints_click = function(menuItem, menu){
		SNAPPI.UIHelper.action.showHints();
		menu.hide();
	}
	MenuItems.help_show_help_beforeShow = function(menuItem, menu){
		var btn = menu.get('currentNode');
		if (btn.one('span').hasClass('green')) {
			_Y.later(50, this, function(){
				menu.hide();
				SNAPPI.UIHelper.action.showHelp(btn);	// close help
			});
		}
	}
	MenuItems.help_show_help_click = function(menuItem, menu){
		SNAPPI.UIHelper.action.showHelp();
		menu.hide();
	}
	
	/**
	 * Workorder Management System
	 */
	MenuItems.wms_create_workorder_beforeShow = function(menuItem, menu){
		var role = SNAPPI.STATE.controller.ROLE;
		if (/(EDITOR|MANAGER)/.test(role)) {
			menuItem.show();
		} else {
			menuItem.hide();
		}
	}
	MenuItems.wms_create_workorder_click = function(menuItem, menu){
		var role = SNAPPI.STATE.controller.ROLE;
		if (/(EDITOR|MANAGER)/.test(role)) {
			// POST to 
			var postData, ioCfg,
				controller = SNAPPI.STATE.controller;
			postData = {
				"data[Workorder][source_id]": controller.xhrFrom.uuid,
				"data[Workorder][source_model]": controller['class'],
				"data[Workorder][editor_id]": controller.userid,	// override in controller
				// manager_id: null,
				// client_id: null,
			}
			ioCfg = {
				uri: '/workorders/create/.json',
				method: "POST",
				qs: postData,
				on: {
					successJson: function(e, i,o,args) {
						var resp = o.responseJson;
						menu.hide();
						if (resp.success) {
							window.location.href = resp.response.next;
						}
					}
				}
			}
			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
			menuItem.plug(_Y.Plugin.IO, ioCfg);
		} else {
			menuItem.hide();
		}
	}
	MenuItems.wms_workorder_toggle_flag_beforeShow = function(menuItem, menu){
		var role = SNAPPI.STATE.controller.ROLE;
		if (/(EDITOR|MANAGER)/.test(role)) {
			var thumbnail = menu.get('currentNode');	// target
			if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
			var audition = SNAPPI.Auditions.find(thumbnail.uuid);
			var isFlagged = audition.Audition.Photo.Flagged;
			if (isFlagged && parseInt(isFlagged)) menuItem.setContent('clear Flag');
			else menuItem.setContent('raise Flag');
			menuItem.show();
		} else {
			menuItem.hide();
		}
	}
	MenuItems.wms_workorder_toggle_flag_click = function(menuItem, menu, e){
		var role = SNAPPI.STATE.controller.ROLE;
		if (!/(EDITOR|MANAGER)/.test(role)) {
			menuItem.hide();
			return;
		}
		var thumbnail = menu.get('currentNode');	// target
		if (!thumbnail.hasClass('FigureBox')) thumbnail = thumbnail.ancestor('.FigureBox');
		var g = MenuItems.getGalleryFromTarget(thumbnail);
		SNAPPI.Factory.Gallery.nav.toggle_ContextMenu(g, e);
		
		
		// POST to 
		var postData, ioCfg, target, args, controller, model, audition, isFlagged;
		
		audition = SNAPPI.Auditions.find(thumbnail.Thumbnail.uuid);
		isFlagged = audition.Audition.Photo.Flagged;
		controller = SNAPPI.STATE.controller;
		model = controller['class'];
		target = menu.get('currentNode');
		postData = {};
		postData["data["+model+"][id]"] = controller.xhrFrom.uuid;
		postData["data[Asset][id]"] = target.Thumbnail.uuid;
		postData["data[flag]"] = isFlagged ? 1 : 0;
		
		args = {
			gallery: g,
			thumbnail: target, 
			postData: postData,
			isFlagged: isFlagged,
			uri: '/'+controller.alias+'/flag/.json',
		};	
		
		
		var dialogCfg = {
			uri: '/combo/markup/flaggedCommentMarkup',
			selector:'div.flag.comment-form',
			height: 200,
			width: 600,
		};
		var _handleDialogSubmit = function(e, args){
				// POST to 
				var ioCfg,
					postNode = e.currentTarget,
					postData = args.postData,
					thumbnail = args.thumbnail
					action = e.currentTarget.getAttribute('action');
					
				if (action=='toggle-flag') {
					postData['data[flag]'] = args.isFlagged ? 0 : 1;	// toggle value
				}	
				postData["data[message]"] = dialog.getStdModNode('body').one('textarea').get('value');	
				args.dialog = this;
				
				if (postNode.io) {
					// after we close the dialog, we create NEW buttons for next call
					// thus, we never reuse the postNode;
					// TODO: loadingmask is in the wrong place, not visible
					postNode.loadingmask.show();
					postNode.io.set('data', postData);
					postNode.io.set('arguments', args);
					postNode.io.start();
				} else {
					ioCfg = {
						uri: args.uri,
						method: "POST",
						qs: postData,
						arguments: args,
						on: {
							successJson: function(e, i,o,args) {
								var resp = o.responseJson;
								if (resp.success) {
									if (postData['data[flag]']) {
										args.thumbnail.one('li.flag').setContent('F').replaceClass('cleared', 'flagged');
									} else {
										args.thumbnail.one('li.flag').setContent('F').replaceClass('flagged', 'cleared');
									}
									var audition = SNAPPI.Auditions.find(args.thumbnail.Thumbnail.uuid);
									audition.Audition.Photo.Flagged = postData['data[flag]'];
								}
								args.dialog.close();
								return false;	// do NOT replace menuItem content
							}
						}
					}
					ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
					postNode.plug(_Y.Plugin.IO, ioCfg);
				}				
		};
		_Y.once('snappi:dialog-body-rendered', function(dialog, cfg, g){
			g.node.listen['Keydown_stopListening'](); // stop gallery KeyDown listener so we can type
			if (parseInt(args.isFlagged) == 0) {
				// change label of button
				var b = dialog.getStdModNode('footer').one('.actions button.toggle-flag');
				b.setContent("Comment and raise flag").replaceClass('green','red');
			}
		}, dialog, g);
		var dialog = SNAPPI.Alert.load(dialogCfg); // don't resize yet
		dialog.once('close', function(e, g){
			g.node.listen['Keydown_startListening']();		// restart listener
		}, dialog, g);
		var detach = dialog.get('boundingBox').delegate('click', 
			function(e, args) {
				detach.detach();
				_handleDialogSubmit.call(this, e, args);
			},
			'.actions button', dialog, args);
		return false;
	}

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
	var CFG_Menu_Header = function(){};
	CFG_Menu_Header.load = function(cfg){
		var CSS_ID = 'menu-header-markup';
		var TRIGGER = '#userAccountBtn';
		var XHR_URI = '/combo/markup/headerMenu'; 
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, cfg);
	};	
	/**
	 * load user Create menu
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_Header_Create = function(){}
	CFG_Menu_Header_Create.load = function(cfg){
		var CSS_ID = 'menu-header-create-markup';
		var TRIGGER = 'nav.user li.menu-trigger-create';
		var XHR_URI = '/combo/markup/headerMenuCreate'; 
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, cfg);
	};
	/**
	 * load user Help menu
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_Header_Help = function(){}
	CFG_Menu_Header_Help.load = function(cfg){
		var CSS_ID = 'menu-header-help-markup';
		var TRIGGER = 'nav.user li.menu-trigger-help';
		var XHR_URI = '/combo/markup/headerMenuHelp'; 
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, cfg);
	};
	/**
	 * load WMS menu for role=MANAGER/EDITOR
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_Header_WMS = function(){}
	CFG_Menu_Header_WMS.load = function(cfg){
		var CSS_ID = 'menu-header-wms-markup';
		var TRIGGER = 'nav.user li.menu-trigger-wms';
		var XHR_URI = '/combo/markup/headerMenuWMS'; 
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, cfg);
	};	
	
	/**
	 * not currently used. see UIHelper.action.toggle_ItemMenu(); 
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_Item_Header = function(){}
	CFG_Menu_Item_Header.load = function(cfg){
		var CSS_ID = 'menu-item-header-markup';
		var TRIGGER = 'section.item-header';
		var XHR_URI = '/combo/markup/itemMenu'; 
		return _load_Single_Trigger_Menu(CSS_ID, TRIGGER, XHR_URI, cfg);
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
	
	/**
	 * load Create menu for making PageGalleries from Selected
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_Pagemaker_Create = function(){}; 
	CFG_Menu_Pagemaker_Create.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tl', 'bl'] },
			init_hidden: true
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-pagemaker-selected-create-markup';
		var TRIGGER = cfg.trigger || '#createBtn';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: _Y.one('#markup'),
			uri: '/combo/markup/pagemakerSelectedCreateMenu',
			end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};	
	
	var CFG_Context_Photoroll = function(){}; 
	/**
	 * load .gallery.photo > .FigureBox contextmenu 
	 * @param cfg
	 * @return
	 */
	CFG_Context_Photoroll.load = function(cfg){
		var CSS_ID = cfg.CSS_ID || 'contextmenu-photoroll-markup';
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
		// self-document XHR request for debugging
		if (cfg.CSS_ID) MARKUP.uri += '?id='+ cfg.CSS_ID;
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];
		
		
		var callback = function(){
			// menu.xy is positioned relative to overlay.get('trigger') 
			var menu = Menu.initContextMenu(MARKUP, TRIGGER, cfg);
			var trigger = menu.get('align').node;
			if (trigger.hasClass('FigureBox')) {
				trigger = trigger.one('> FIGURE > IMG');
				menu.set('align.node', trigger);
			}
			var offset = cfg.offset || {x:-20, y:20};
			menu.set('xy', Menu.moveIfUnconstrained(menu, null, offset));
			menu.on('xyChange', function(e){
					e.newVal = Menu.moveIfUnconstrained(this, e.newVal, offset);
				}
			)
		};
		return Menu.getMarkup(MARKUP , callback);
	};
	
	
	
	var CFG_Context_HiddenShot = function(){}; 
	/**
	 * load Gallery contextmenu for HiddenShots .thumbnail
	 * @param cfg
	 * @return
	 */
	CFG_Context_HiddenShot.load = function(cfg){
		var defaultCfg = {
				constrain: true,
		}
		cfg = _Y.merge(defaultCfg, cfg);
		
		var CSS_ID = cfg.CSS_ID || 'contextmenu-hiddenshot-markup';
		var TRIGGER = ' .FigureBox.Photo';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: '/combo/markup/hiddenShotContextMenu'
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			var menu = Menu.initContextMenu(MARKUP, TRIGGER, cfg);
			var offset = {x:-20, y:20};
			menu.set('xy', Menu.moveIfUnconstrained(menu, null, offset));
			menu.on('xyChange', function(e){
					e.newVal = Menu.moveIfUnconstrained(this, e.newVal, offset);
				}
			)			
		};
		return Menu.getMarkup(MARKUP , callback);
	};
	
	
	var CFG_Menu_SelectAll = function(){}; 
	CFG_Menu_SelectAll.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tl', 'bl'] },
			init_hidden: true,
			on: {
				show: function(e) {
					var target = this.get('currentNode');
					if (target.ancestor('#lightbox')) {	// up for lightbox
						this.set('align', { points:['bl', 'tl']})
						this.enable();
					} else if (target.one('span.menu-open')) {
						this.enable();
						this.set('align', { points:['tl', 'bl']});	
					} else this.disable();
					Menu.menuItem_beforeShow(e.target);
				}
			}
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-select-all-markup';
		var TRIGGER = cfg.trigger || 'li.select-all';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: _Y.one('#markup'),
			uri: '/combo/markup/selectAll',
			end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};	
		
	var CFG_Menu_Lightbox_Organize = function(){}; 
	/**
	 * load organize menu for lightbox
	 * @param cfg
	 * @return
	 */
	CFG_Menu_Lightbox_Organize.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['bl', 'tl'] },
			init_hidden: true
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-lightbox-organize-markup';
		var TRIGGER = cfg.trigger || 'section#lightbox ul.menu-trigger li.organize';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: _Y.one('#markup'),
			uri: '/combo/markup/lightbox',
			end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};		
	
	var CFG_Menu_Lightbox_Share = function(){}; 
	CFG_Menu_Lightbox_Share.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['bl', 'tl'] },
			init_hidden: true
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-lightbox-share-markup';
		var TRIGGER = cfg.trigger || 'section#lightbox ul.menu-trigger li.share';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: _Y.one('#markup'),
			uri: '/combo/markup/lightbox',
			end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};	
	
	/**
	 * load user shortcuts menu
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_SignIn = function(){}; 
	CFG_Menu_SignIn.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tr', 'br'] },
			init_hidden: true,
			on: {
				show: function(e) {
			    	if (SNAPPI.AIR.debug && _Y.one('#login select.postData')) {
			    		_Y.one('#login select.postData').removeClass('hide');
			    	}					
					// var menuTarget = e.target;
					// var contextTarget = menuTarget.get('currentNode');
// LOG('menu-sign-in-markup: onShow()');						
// var content = e.target.get('contentBox');
					try {
						SNAPPI.AIR.XhrHelper.resetSignInForm('#login');
						var username = SNAPPI.DATASOURCE.getConfigs().username;
						if (username) this.get('contentBox').one('input#UserUsername').set('value',username);
					} catch (e) {}
					e.target.get('contentBox').removeClass('hide');
				},
				hide: function(e) {
// LOG('menu-sign-in-markup: onHide()');						
// var content = e.target.get('contentBox');
					e.target.get('contentBox').addClass('hide');
				},
			},			
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-sign-in-markup';
		var TRIGGER = '#sign-in';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: '/combo/markup/headerMenu',
				end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};	
	 
	/**
	 * load menu for choosing Uploader batchid or baseurls
	 * @param cfg
	 * @return
	 */
	var CFG_Menu_Uploader_Batch = function(){};
	CFG_Menu_Uploader_Batch.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tl', 'bl'] },
			init_hidden: true,
			on: {
				show: function(e) {
					var content = e.target.get('contentBox');
					try {
						// SNAPPI.AIR.UIHelper.menu.load_folders(content);
						SNAPPI.AIR.UIHelper.menu.load_batches(content);
					} catch (e) {}
					content.removeClass('hide');
					SNAPPI.AIR.UIHelper.toggle_ContextMenu(false);	// hide contextmenu
				},
				hide: function(e) {
					e.target.get('contentBox').addClass('hide');
				},
			},			
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-uploader-batch-markup';
		var TRIGGER = '.gallery-display-options li.btn.choose-folder';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: '/combo/markup/headerMenu',
				end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};			
	
	var CFG_Menu_Uploader_Folder = function(){};
	CFG_Menu_Uploader_Batch.load = function(cfg){
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tl', 'bl'] },
			init_hidden: true,
			on: {
				show: function(e) {
					var content = e.target.get('contentBox');
					try {
						SNAPPI.AIR.UIHelper.menu.load_folders(content);
					} catch (e) {}
					content.removeClass('hide');
					SNAPPI.AIR.UIHelper.toggle_ContextMenu(false);	// hide contextmenu
				},
				hide: function(e) {
					e.target.get('contentBox').addClass('hide');
				},
			},
		};
		cfg = _Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-uploader-folder-markup';
		var TRIGGER = '.gallery-display-options li.btn.choose-folder';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: _Y.one('#markup'),
				uri: '/combo/markup/headerMenu',
				end: null
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];

		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
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
			'collection': {
				CSS_ID: 'contextmenu-collection-markup',	
				uri: '/combo/markup/collectionContextMenu',
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
