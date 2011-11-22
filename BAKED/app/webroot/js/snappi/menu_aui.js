(function(){

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

	
	var Menu = function(){
		if (Menu.doClassInit) Menu.classInit();
	};
	Menu.prototype = {};
	
	/*
	 * static properties and methods
	 */
	Menu.doClassInit = true;
	Menu.listen = {};		// ??? track Class listeners? or instance listeners?
	Menu.find = {};	// keep track of dialog instances for reuse
	Menu.overlayManager = null;
	Menu.classInit = function() {
		if (Menu.doClassInit == false) return;
		var Y = SNAPPI.Y;
		Menu.doClassInit = false;
	};
	
	/**
	 * fetch html markup using Plugin.IO
	 * @param cfg {uri:, selector:, container:, plug Y.Plugin.io cfg attrs}
	 * @param callback, callback method to init Menu
	 * @return menu or false on XHR request
	 */
	Menu.getMarkup = function(cfg, callback){
		var Y = SNAPPI.Y;
		var container = cfg.container;
		var selector = cfg.selector;
		
		var ioCfg = {
				uri: null,
				autoLoad: true,
				showLoading:false, 
				end: null
		};		
		var ioCfg = Y.merge(ioCfg, cfg);
		delete ioCfg.selector;
		delete ioCfg.selector;
		
		if (!Y.one(selector)) {
			// BUG: container.one('#menu-header') does NOT work in chrome
			var markupNode = Y.Node.create("<div />");
			container.append(markupNode);
			SNAPPI.setPageLoading(true);
			markupNode.plug(Y.Plugin.IO, ioCfg);	
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
	 * @param cfg {}, additional config for Y.OverlayContext
	 * @return
	 */
	Menu.initMenus = function(menus){
		var Y = SNAPPI.Y;
		var defaultMenus;
		try {
			defaultMenus = {
				'menu-header-markup': SNAPPI.STATE.controller.userid,	// authenticated
			};
		} catch (e) {
			defaultMenus = {};		// catch race condition
		}
		menus = Y.merge(defaultMenus, menus);
		for (var i in menus) {
			var CSS_ID = menus[i] ? i : null; 
			var cfg = Y.Lang.isObject(menus[i]) ? menus[i] : null;
	    	if (CSS_ID && !Menu.find[CSS_ID]) {
	    		Menu.CFG[CSS_ID].load(cfg);
	    	}			
		}
		Y.one('#markup').setStyle('display', 'block');
	};	
	/**
	 * 
	 * @param MARKUP
	 * @param TRIGGER
	 * @param cfg {}, additional config for Y.OverlayContext
	 * 		cfg.host: adds cfg.host.ContextMenu backreference
	 * 		cfg.triggerType	.gallery.[triggerType]
	 * 		cfg.triggerRoot '#'+cfg.triggerRoot.get('id')
	 * @return
	 */
	Menu.initContextMenu = function(MARKUP, TRIGGER, cfg){
		var Y = SNAPPI.Y;
		cfg = cfg || {};	// closure
		if (cfg.triggerType) TRIGGER = '.gallery.'+cfg.triggerType + TRIGGER;
		if (cfg.triggerRoot) TRIGGER = '#'+cfg.triggerRoot.get('id')+' '+ TRIGGER;
		var _cfg = {
				trigger: TRIGGER,
				contentBox: MARKUP.selector,
				boundingBox: MARKUP.selector
		};
		_cfg = Y.merge(DEFAULT_CFG_contextmenu, _cfg, cfg);
		if (cfg.currentTarget) _cfg.trigger = cfg.currentTarget;	// 'startup/disabled' trigger

		var menu = new Y.OverlayContext(_cfg);
		menu.render();
		menu.get('contentBox').removeClass('hide');
		if (cfg.init_hidden === false) menu.show();
		if (cfg.currentTarget) {
			menu.set('trigger', TRIGGER);	// 'enabled' trigger
			menu._stashTrigger = TRIGGER;
		}
		Menu.startListener(menu, cfg.handle_click);
		
		// lookup reference
		
		Menu.find[MARKUP.id] = menu;
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
	 * toggle menu enable/disable by changing trigger
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
				// no special clickhandler, so just find a.href
//					window.location.href = menuItem.one('a').getAttribute('href');
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
		
		
		var Y = SNAPPI.Y;
		var peek = ['refreshAlign', 'toggle', 'show', 'hide', 'updateCurrentNode', 'focus', 'render', 'enable' , 'disable' ];
		Y.before(function(){
			console.log("before A.OverlayContext.refreshAlign()");
		}, menu, 'refreshAlign', menu);  
		Y.before(function(){
			console.log("before A.OverlayContext.toggle()");
		}, menu, 'toggle', menu); 
		Y.before(function(){
			console.log("before A.OverlayContext.show()");
		}, menu, 'show', menu); 
		Y.before(function(){
			console.log("before A.OverlayContext.hide()");
		}, menu, 'hide', menu); 
		Y.before(function(){
			console.log("before A.OverlayContext.updateCurrentNode()");
		}, menu, 'updateCurrentNode', menu); 			
		Y.before(function(){
			console.log("before A.OverlayContext.focus()");
		}, menu, 'focus', menu); 			
		Y.before(function(){
			console.log("before A.OverlayContext.render()");
		}, menu, 'render', menu); 		
	};
	
	SNAPPI.MenuAUI = Menu;
	
	
	var MenuItems = function(){}; 
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
		var cb = target.previous('input[type="checkbox"]');
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
		var cb = target.previous('input[type="checkbox"]');
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
				isSelected = SNAPPI.Y.all(target);
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
				isSelected = SNAPPI.Y.all(target);
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
				isSelected = SNAPPI.Y.all(target);
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
		try {
			var target = menu.get('currentNode'),	// target
				enabled = true;
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) {
				// context menu
			} else {
				var g = target.ancestor('section').next('section.gallery').Gallery;
				enabled = g.getSelected().size();
			} 			
			// check if we have write permission
			if (SNAPPI.STATE.controller.alias == 'my' ) {
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
	MenuItems.delete_click = function(menuItem, menu){
		try {
			var g, response, isSelected, thumbnail = menu.get('currentNode');	// target
			if (menuItem.ancestor('#contextmenu-photoroll-markup')) { 
				// from context menu
				g = MenuItems.getGalleryFromTarget(thumbnail);
				response = confirm('Are you sure you want to remove this Snap from your account?');
				if (response)  g.deleteThumbnail(thumbnail, thumbnail);
				menu.hide();
			} else {
				// from selectAll
				g = thumbnail.ancestor('section').next('section.gallery').Gallery;
				response = confirm('Are you sure you want to remove ALL selected Snaps from your account?');
				if (response)  g.deleteThumbnail(null, menuItem);
				// menu.hide();
			}			
		} catch (e) {}		
	};	
	// formerly _getPhotoRoll(), currently unused
	MenuItems.getGalleryFromTarget = function(target){
		if (target instanceof SNAPPI.Y.OverlayContext) {
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
					uuid : audition.id,
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
			var self = SNAPPI.lightbox; 
			self.renderTagInput(menuItem);
		} else {
			var input = menuItem.one('input#lbx-tag-field');
			input.set('value', 'Enter tags').addClass('help');
		}
	};	
	
	MenuItems.rotate_click = function(menuItem, menu){
		var rotate = menuItem.getAttribute('rotate');
		var thumbnail = menu.get('currentNode');
		var options = {
			ids: thumbnail.uuid,	// id or array of ids
			properties: {'rotate': rotate},
			actions: null,
			callbacks: {
				successJson: function(e, i, o,args){
					var resp = o.responseJson;
					var uuid = resp.response.uuid;
					// reset all .FigureBoxes
					try {
						var img, src, audition = SNAPPI.Auditions.find(uuid);
						for (var i in audition.bindTo) {
							if (audition.bindTo[i].hasClass('FigureBox')) {
								img = audition.bindTo[i].one('figure > img');
								src = img.get('src');
								img.set('src', src + '?rand=' + Math.random());
							}
						}
					} catch (e) {}
					args.loadingmask.hide();
					return false;
				}, 
				complete: function(e, i, o, args) {
					args.loadingmask.hide();
				},
				failure : function (e, i, o, args) {
					// post failure or timeout
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
	},
	// deprecated. use click on hiddenshot icon instead
	MenuItems.showHiddenShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
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
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		try {
			var g = MenuItems.getGalleryFromTarget(menu);
			
			// new pattern, reuse Thumbnail.PhotoPreview
			SNAPPI.Helper.Dialog.bindSelected2DialogHiddenShot(g, audition);
			return;
		} catch (e) {
		}		
	};

	MenuItems.groupAsShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var g = MenuItems.getGalleryFromTarget(thumbnail);
		try {
			// check if the user has permission to groupAsShot
			var shotType = g.castingCall.CastingCall.GroupAsShotPerm;
		} catch (e) {}
		if (shotType && thumbnail.hasClass('selected')) {
			if (g.getSelected().count()>1) {
				menuItem.removeClass('disabled');
				return;
			}
		}
		menuItem.addClass('disabled');
	};	
	
	MenuItems.groupAsShot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		try {
			// from thumbnail context-menu
			var g = MenuItems.getGalleryFromTarget(menu);
			var shotType = g.castingCall.CastingCall.GroupAsShotPerm;
			var options = {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
			};
			// get userid or group_id for shot
			if (/(User)|(Group)/.test(SNAPPI.STATE.controller['class'])) {
				options.uuid = SNAPPI.STATE.controller.xhrFrom.uuid;
			} else {
				// get uuid from castingCall request
				var request = g.castingCall.CastingCall.Request.split('/');
				if (request[1] == 'my') options.uuid = SNAPPI.STATE.controller.userid;
				else if (request[3]) options.uuid = request[3];
			}
			g.groupAsShot(null, options);
			return;
		} catch (e) {}	
	};	
	
	MenuItems.lightbox_group_as_shot_click = function(menuItem, menu){
		try {
			// from lightbox menuItem
			var lightbox = menu.get('currentNode').ancestor('#lightbox').Lightbox;
			try {
				var shotType = 'unknown';		// lightbox photos can be from group or user 
				// check if the user has permission to groupAsShot
				shotType = g.castingCall.CastingCall.GroupAsShotPerm;
			} catch (e) {}				
			// shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
			var batch = lightbox.getSelected();
			// TODO: ??? lightbox.groupAsShot() or lightbox.Gallery.groupAsShot()??? 
			lightbox.Gallery.groupAsShot(batch, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				lightbox: lightbox,				// remove hiddenshot-hide from lightbox
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});	
			return;		
		} catch (e) {}		
	}
	
	MenuItems.removeFromShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		try {
			// check if the user has permission to groupAsShot
			var shotType = g.castingCall.CastingCall.GroupAsShotPerm;
		} catch (e) {}		
		// var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
		if (shotType && thumbnail.hasClass('selected')) {
			var g = MenuItems.getGalleryFromTarget(thumbnail);
			if (g.getSelected().count()>=1) {
				menuItem.removeClass('disabled');
				return;
			}
		}
		menuItem.addClass('disabled');
	};	
	
	MenuItems.removeFromShot_click = function(menuItem, menu){
		var batch, thumbnail = menu.get('currentNode');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		var g = MenuItems.getGalleryFromTarget(menu);
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
		if (remaining > 1) {
			g.removeFromShot(batch, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				shotUuid: audition.Audition.Substitutions.id,
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});
		} else {
			// TODO: confirm delete
			g.unGroupShot(batch, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});			
		}
	};
	
	MenuItems.ungroupShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		try {
			// check if the user has permission to groupAsShot
			var g = MenuItems.getGalleryFromTarget(menu);
			var shotType = g.castingCall.CastingCall.GroupAsShotPerm;
		} catch (e) {}	
		// var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
		if (!shotType) {
			menuItem.addClass('disabled');
			return;
		} 
    	try {
    		var shotId = audition.Audition.Substitutions.id;
    		if (shotId) menuItem.removeClass('disabled');
    		else menuItem.addClass('disabled');
		}catch(e){
			menuItem.addClass('disabled');
		}		
	};
		
	MenuItems.ungroupShot_click = function(menuItem, menu){
		var batch, thumbnail = menu.get('currentNode');	// target
		var audition = SNAPPI.Auditions.find(thumbnail.uuid);
		var g = MenuItems.getGalleryFromTarget(menu);
		var shotType = audition.Audition.Substitutions.shotType;
		// if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
		if (!shotType) {
			if (console) console.error("ERROR: shotType unknown in MenuItems.setBestshot_click()");
			return;
		}			
		batch = g.getSelected();
		if (batch.count()==0) batch.add(audition);
		g.unGroupShot(batch, {
			menu: menu,
			loadingNode: menuItem,
			shotType: shotType,
			uuid: SNAPPI.STATE.controller.xhrFrom.uuid
		});
	};
	
	MenuItems.setBestshot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
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
		var g = MenuItems.getGalleryFromTarget(menu);
		var batch = g.getSelected();
		if (!batch.count() && g._cfg.type=='Lightbox') {
			// batch = g.getSelected(true);			
		}		
		if (batch.count()) {
			var Y = SNAPPI.PM.Y || SNAPPI.Y;
//			var stage = SNAPPI.PageGalleryPlugin.stage;
//			var performance = stage ? stage.performance : null;
			var stage2 = Y.one('#stage-2');
			if (!stage2) {
				stage2 = g.container.create("<section class='container_16'><div id='stage-2' class='grid_16' style='position:absolute;top:200px;'></div></section>");
				Y.one('section#body-container').insert(stage2, 'after');
				stage2 = Y.one('#stage-2');
			}
			var sceneCfg = {
				roleCount: batch.count(),
				fnDisplaySize: {h:800},
				stage: stage2,
				noHeader: true,
				useHints: true
			};			
			SNAPPI.PM.node.onPageGalleryReady(sceneCfg);
		}
	};	
	MenuItems.express_upload_beforeShow = function(menuItem, menu, properties){
		// if this group is marked for express-upload, add .selected
		if (!PAGE.jsonData.expressUploadGroups) {
			menuItem.hide();
		} else {
			var isExpress = PAGE.jsonData.expressUploadGroups[properties.id] !== undefined;
			if (isExpress) menuItem.addClass('selected');
			else menuItem.removeClass('selected');
			menuItem.show();
		} 			 
	};	
	MenuItems.express_upload_click = function(menuItem, menu, properties){
		var isExpress = menuItem.hasClass('selected');
		isExpress = !isExpress
		// TODO: POST isExpress to set GroupsUser.isExpress
		if (isExpress) menuItem.addClass('selected');
		else menuItem.removeClass('selected');
	};	
	MenuItems.share_with_this_circle_beforeShow = function(menuItem, menu){
		if (/^Groups/.test(SNAPPI.STATE.controller.name)==false) {
			menuItem.addClass('disabled');
		}
	};	
	MenuItems.share_with_this_circle_click = function(menuItem, menu){
		try {
			var gid = SNAPPI.STATE.controller.xhrFrom.uuid;	
			SNAPPI.lightbox.applyShareInBatch(gid, menuItem);
		} catch (e) {}
	};	
	MenuItems.share_with_circle_click = function(menuItem, menu){
    		/*
    		 * create or reuse Dialog
    		 */
    		var dialog_ID = 'dialog-select-circles';
    		var dialog = SNAPPI.Dialog.find[dialog_ID];
    		if (!dialog) {
            	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
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
							parent.setContent(o.responseText);
							// start multi-select listener
							var container = parent.one('.gallery.group .container');
							SNAPPI.multiSelect.listen(container, true, SNAPPI.MultiSelect.singleSelectHandler);
							return false;
						}					
					}
    			};
    			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
    			dialog.plug(SNAPPI.Y.Plugin.IO, ioCfg);
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
			var subUri = '/my/groups';
			dialog.io.set('uri', subUri );
			// dialog.io.set('arguments', args );    			
			document.body.style.cursor = 'wait';
			dialog.io.start();			
	};	
	MenuItems.photo_privacy_click = function(menuItem, menu){
    		/*
    		 * create or reuse Dialog
    		 */
    		var dialog_ID = 'dialog-select-privacy';
    		var dialog = SNAPPI.Dialog.find[dialog_ID];
    		if (!dialog) {
            	dialog = SNAPPI.Dialog.CFG[dialog_ID].load();
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
							// use div#settings-asset-privacy-markup
							var parent = SNAPPI.Y.Node.create(o.responseText); 
							var markup = parent.one('div#settings-asset-privacy-markup');
							return markup.removeClass('hide');
						}					
					}
    			};
    			ioCfg = SNAPPI.IO.pluginIO_RespondAsJson(ioCfg);
    			dialog.plug(SNAPPI.Y.Plugin.IO, ioCfg);
    			// dialog_ID == dialog.get('boundingBox').get('id')
    			SNAPPI.Dialog.find[dialog_ID] = dialog;
    		} else {
    			if (!dialog.get('visible')) {
    				dialog.setStdModContent('body','<ul />');
    				dialog.show();
    			}
    			dialog.set('title', 'Privacy Settings');
    		}
    		
			// shots are NOT included. get shots via XHR and render
			var subUri = '/combo/markup/settings';	// placeholder
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
		window.location.href = '/groups/invitation/'+properties.id;
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
		var target = menu.get('currentNode');
		// TODO: check privacy/membership settings
		if (properties.isMember && !properties.isOwner) menuItem.show();
		else menuItem.hide();
	};	
	MenuItems.leave_click= function(menuItem, menu, properties){
		var target = menu.get('currentNode');
		// TODO:
	};	
	
	/*
	 * MenuCfgs
	 */
	
	var CFG_Menu_Header = function(){}; 
	/**
	 * load user shortcuts menu
	 * @param cfg
	 * @return
	 */
	CFG_Menu_Header.load = function(cfg){
		var Y = SNAPPI.Y;
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tr', 'br'] },
			init_hidden: true
		};
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-header-markup';
		var TRIGGER = '#userAccountBtn';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
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
	
	var CFG_Menu_Pagemaker_Create = function(){}; 
	/**
	 * load Create menu for making PageGalleries from Selected
	 * @param cfg
	 * @return
	 */
	CFG_Menu_Pagemaker_Create.load = function(cfg){
		var Y = SNAPPI.Y;
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tl', 'bl'] },
			init_hidden: true
		};
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-pagemaker-selected-create-markup';
		var TRIGGER = cfg.trigger || '#createBtn';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
		var CSS_ID = 'contextmenu-photoroll-markup';
		var TRIGGER = ' .FigureBox';
		var defaultCfg = {
				// constrain: true,
		}
		cfg = Y.merge(defaultCfg, cfg);

		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
				uri: '/combo/markup/photoRollContextMenu',
		};
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];
		
		
		Menu.classInit(); 


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
	
	
	
	var CFG_Context_HiddenShot = function(){}; 
	/**
	 * load Gallery contextmenu for HiddenShots .thumbnail
	 * @param cfg
	 * @return
	 */
	CFG_Context_HiddenShot.load = function(cfg){
		var Y = SNAPPI.Y;
		var defaultCfg = {
				constrain: true,
		}
		cfg = Y.merge(defaultCfg, cfg);
				
		var CSS_ID = 'contextmenu-hiddenshot-markup';
		var TRIGGER = ' .FigureBox.Photo';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tl', 'bl'] },
			init_hidden: true,
			on: {
				show: function(e) {
					var target = this.get('currentNode');
					if (target.ancestor('#lightbox')) {	// up for lightbox
						this.set('align', { points:['bl', 'tl']})
					} else {
						this.set('align', { points:['tl', 'bl']});	
					}
					Menu.menuItem_beforeShow(e.target);
				}
			}
		};
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-select-all-markup';
		var TRIGGER = cfg.trigger || 'li.select-all a.menu-open';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['bl', 'tl'] },
			init_hidden: true
		};
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-lightbox-organize-markup';
		var TRIGGER = cfg.trigger || 'section#lightbox ul.menu-trigger li.organize';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['bl', 'tl'] },
			init_hidden: true
		};
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-lightbox-share-markup';
		var TRIGGER = cfg.trigger || 'section#lightbox ul.menu-trigger li.share';
		var MARKUP = {
			id: CSS_ID,
			selector: '#'+CSS_ID,
			container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
		var defaultCfg = {
			showOn: 'click',	
			align: { points:['tr', 'br'] },
			init_hidden: true,
			on: {
				show: function(e) {
			    	if (SNAPPI.AIR.debug && Y.one('#login select.postData')) {
			    		Y.one('#login select.postData').removeClass('hide');
			    	}					
					// var menuTarget = e.target;
					// var contextTarget = menuTarget.get('currentNode');
// LOG('menu-sign-in-markup: onShow()');						
// var content = e.target.get('contentBox');
					e.target.get('contentBox').removeClass('hide');
					try {
						SNAPPI.AIR.XhrHelper.resetSignInForm('#login');
					} catch (e) {}
				},
				hide: function(e) {
// LOG('menu-sign-in-markup: onHide()');						
// var content = e.target.get('contentBox');
					e.target.get('contentBox').addClass('hide');
				},
			},			
		};
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-sign-in-markup';
		var TRIGGER = '#sign-in';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
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
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-uploader-batch-markup';
		var TRIGGER = '.gallery-display-options li.btn.choose-folder';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
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
		var Y = SNAPPI.Y;
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
		cfg = Y.merge(defaultCfg, cfg);
		var CSS_ID = 'menu-uploader-folder-markup';
		var TRIGGER = '.gallery-display-options li.btn.choose-folder';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
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
	
	var CFG_Context_FigureBox = function(){}; 
	/**
	 * load .FigureBox contextmenu .gallery.[type] > .FigureBox contextmenu 
	 * @param cfg. cfg.triggerType = [group, person, etc.]
	 * @return
	 */
	CFG_Context_FigureBox.load = function(cfg){
		var Y = SNAPPI.Y;
		var TRIGGER = ' .FigureBox';
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

		var CSS_ID = typeDefaults.CSS_ID;
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
		cfg = Y.merge(defaultCfg, cfg);

		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
				uri: typeDefaults.uri,
		};
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];
		
		
		Menu.classInit(); 


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
	
		
	// SNAPPI.MenuAUI
	// global lookup by CSS ID
	Menu.CFG = {
		'menu-header-markup': CFG_Menu_Header,
		'contextmenu-photoroll-markup': CFG_Context_Photoroll,
		'contextmenu-hiddenshot-markup': CFG_Context_HiddenShot,
		'menu-pagemaker-selected-create-markup': CFG_Menu_Pagemaker_Create, 
		'menu-select-all-markup': CFG_Menu_SelectAll,
		'menu-lightbox-organize-markup': CFG_Menu_Lightbox_Organize,
		'menu-lightbox-share-markup': CFG_Menu_Lightbox_Share,
		'menu-sign-in-markup': CFG_Menu_SignIn,
		'menu-uploader-batch-markup': CFG_Menu_Uploader_Batch,		
		'menu-uploader-folder-markup': CFG_Menu_Uploader_Folder,
		// context menus
		'contextmenu-group-markup': CFG_Context_FigureBox,		
		'contextmenu-person-markup': CFG_Context_FigureBox,
	};
})();
