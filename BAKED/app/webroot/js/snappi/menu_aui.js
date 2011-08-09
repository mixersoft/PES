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
				 console.warn('contextmenu: on show', e);
				 var menuTarget = e.target;
				 Menu.menuItem_beforeShow(menuTarget);
			},
			hide: function(e) {
//				 console.warn('contextmenu: on hide', e);
			}
		},
		after: {
			show: function(e) {
				 console.warn('contextmenu: after show', e);
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
			markupNode.plug(Y.Plugin.IO, ioCfg);	
			markupNode.io.afterHostMethod('insert', callback);
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
		var defaultMenus = {
				'menu-header-markup': SNAPPI.STATE.controller.userid,	// authenticated
				end: 0
		};
		menus = SNAPPI.Y.merge(defaultMenus, menus);
		for (var i in menus) {
			var CSS_ID = menus[i] ? i : null; 
	    	if (CSS_ID && !SNAPPI.MenuAUI.find[CSS_ID]) {
	    		SNAPPI.MenuAUI.CFG[CSS_ID].load();
	    	}			
		}
	};	
	/**
	 * 
	 * @param MARKUP
	 * @param TRIGGER
	 * @param cfg {}, additional config for Y.OverlayContext
	 * @return
	 */
	Menu.initContextMenu = function(MARKUP, TRIGGER, cfg){
		var Y = SNAPPI.Y;
		cfg = cfg || {};	// closure
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
		Menu.startListener(menu);
		
		// lookup reference
		
		Menu.find[MARKUP.id] = menu;
		return menu;
	};
	
	/*
	 * toggle menu enable/disable by changing trigger
	 */
	Menu.toggleEnabled = function(menu_ID, e) {
		var menu = Menu.find[menu_ID];
		if (menu.get('disabled')) {
			menu.enable();
			menu.set('trigger', e.currentTarget);			// 'startup/disabled' trigger
			menu.show();
			menu.set('trigger', menu._stashTrigger); 		// 'enabled' trigger
		} else {
			menu.disable();
			menu.hide();
			menu.set('trigger', 'disabled');
		}
		return menu;
	};
	
	Menu.startListener = function(menu){
		var parent = menu.get('contentBox');
		menu.listen = menu.listen || {};
		if (!menu.listen['delegate_click']) {
			menu.listen['delegate_click'] = parent.delegate('click', function(e){
				var menuItem = e.currentTarget;
				var methodName = menuItem.getAttribute('action')+'_click';
				if (MenuItems[methodName]) {
					e.preventDefault();
					MenuItems[methodName](menuItem, this);
				} else {
					// default
					// no special clickhandler, so just find a.href
//					window.location.href = menuItem.one('a').getAttribute('href');
				}
			}, 'ul > li',  menu);
		}
	};
	
	Menu.menuItem_beforeShow = function(menu){
		var content = menu.get('contentBox');
		if (content) content.all('ul > li.before-show').each(function(n,i,l){
			// call beforeShow for each menuItem
			if (n.hasClass('before-show')) {
				var methodName = n.getAttribute('action')+'_beforeShow';
				if (MenuItems[methodName]) MenuItems[methodName](n, menu);
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
	
	
	var MenuItems = function(){}; MenuItems.prototype = {};
	
	// formerly _getPhotoRoll(), currently unused
	MenuItems.getPhotoRollFromTarget = function(target){
		if (target instanceof SNAPPI.Y.OverlayContext) {
			target = target.get('currentNode');	// contextmenu target
		}
		var hasPhotoroll = false, 
			found = target.ancestor(
				function(n){
					hasPhotoroll = n.Photoroll || n.dom().PhotoRoll ||  (n.dom().Lightbox && n.dom().Lightbox.PhotoRoll) || null; 
					return hasPhotoroll;
				}, true );
		return hasPhotoroll;
	};	
	MenuItems.rating_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		if (!menuItem.Rating) {
			// add new rating group as LI > DIV
//			menuItem.audition = thumbnail.audition;
			menuItem.setAttribute('uuid', thumbnail.audition.id);
			SNAPPI.Rating.pluginRating(menuItem, menuItem, thumbnail.audition.rating);
			SNAPPI.Rating.startListeners(menuItem);
			menuItem.one('.ratingGroup').setAttribute('id', 'menuItem-contextRatingGrp');
		} else {
			var ratingCfg = {
					v : thumbnail.audition.rating,
					uuid : thumbnail.audition.id,
					listen : false
				};
			var r = SNAPPI.Rating.attach(menuItem, ratingCfg);
			r.thumbnail = thumbnail;
		}
	};
	
	MenuItems.showHiddenShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
    	try {
    		var shotId = thumbnail.audition.Audition.Substitutions.id;
    		if (shotId) menuItem.show();
    		else menuItem.hide();
		}catch(e){
			menuItem.hide();
		}		
	};
	
	MenuItems.showHiddenShot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		try {
			var audition = thumbnail.audition;
			var photoRoll = MenuItems.getPhotoRollFromTarget(menu);
			var shotType = audition.Audition.Substitutions.shotType;
			if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
			photoRoll.showHiddenShotsInDialog(audition, shotType);
		} catch (e) {
		}		
	};

	MenuItems.groupAsShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
		if (show && thumbnail.hasClass('selected')) {
			var photoRoll = MenuItems.getPhotoRollFromTarget(thumbnail);
			if (photoRoll.getSelected().count()>1) {
				menuItem.show();
				return;
			}
		}
		menuItem.hide();
	};	
	
	MenuItems.groupAsShot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		try {
			var audition = thumbnail.audition;
			var photoRoll = MenuItems.getPhotoRollFromTarget(menu);
			var shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
			photoRoll.groupAsShot(null, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});
		} catch (e) {
		}		
	};	
	
	
	MenuItems.removeFromShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
		if (show && thumbnail.hasClass('selected')) {
			var photoRoll = MenuItems.getPhotoRollFromTarget(thumbnail);
			if (photoRoll.getSelected().count()>=1) {
				menuItem.show();
				return;
			}
		}
		menuItem.hide();
	};	
	
	MenuItems.removeFromShot_click = function(menuItem, menu){
		var batch, thumbnail = menu.get('currentNode');	// target
		var audition = thumbnail.audition;
		var photoRoll = MenuItems.getPhotoRollFromTarget(menu);
		var shotType = audition.Audition.Substitutions.shotType;
		if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
		batch = photoRoll.getSelected();
		// count remaining assets
		if (batch.count()==0) batch.add(audition);
		var remaining = photoRoll.auditionSH.count() - batch.count();
		if (remaining > 1) {
			photoRoll.removeFromShot(batch, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				shotUuid: thumbnail.audition.Audition.Substitutions.id,
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});
		} else {
			// TODO: confirm delete
			photoRoll.unGroupShot(batch, {
				menu: menu,
				loadingNode: menuItem,
				shotType: shotType,
				uuid: SNAPPI.STATE.controller.xhrFrom.uuid
			});			
		}
	};
	
	MenuItems.ungroupShot_beforeShow = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var show = /^Users|^Groups/.test(SNAPPI.STATE.controller.name);
		if (!show) {
			menuItem.hide();
			return;
		} 
    	try {
    		var shotId = thumbnail.audition.Audition.Substitutions.id;
    		if (shotId) menuItem.show();
    		else menuItem.hide();
		}catch(e){
			menuItem.hide();
		}		
	};
		
	MenuItems.ungroupShot_click = function(menuItem, menu){
		var batch, thumbnail = menu.get('currentNode');	// target
		var audition = thumbnail.audition;
		var photoRoll = MenuItems.getPhotoRollFromTarget(menu);
		var shotType = audition.Audition.Substitutions.shotType;
		if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
		batch = photoRoll.getSelected();
		if (batch.count()==0) batch.add(audition);
		photoRoll.unGroupShot(batch, {
			menu: menu,
			loadingNode: menuItem,
			shotType: shotType,
			uuid: SNAPPI.STATE.controller.xhrFrom.uuid
		});
	};
	
	MenuItems.setBestshot_click = function(menuItem, menu){
		var thumbnail = menu.get('currentNode');	// target
		var audition = thumbnail.audition;
		var photoRoll = MenuItems.getPhotoRollFromTarget(menu);
		var shotType = audition.Audition.Substitutions.shotType;
		if (!shotType) shotType = /^Groups/.test(SNAPPI.STATE.controller.name) ? 'Groupshot' : 'Usershot';
		photoRoll.setBestshot(thumbnail, {
			menu: menu,
			loadingNode: menuItem,
			shotType: shotType
		});
	};	
	
	MenuItems.create_pagegallery_click = function(menuItem, menu){
		var photoRoll = MenuItems.getPhotoRollFromTarget(menu);
		var batch;	// target
		var audition = photoRoll.auditionSH.get(0);
		batch = photoRoll.getSelected();
		if (batch.count()) {
			var Y = SNAPPI.PM.Y;
//			var stage = SNAPPI.PageGalleryPlugin.stage;
//			var performance = stage ? stage.performance : null;
			var stage2 = photoRoll.container.create("<div id='stage-2' class='grid_16' style='position:absolute;top:200px;'></div>");
			Y.one('#content').append(stage2);
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
	/*
	 * MenuCfgs
	 */
	
	var CFG_Menu_Header = function(){}; CFG_Menu_Header.prototype = {};	
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
	
	var CFG_Menu_Pagemaker_Create = function(){}; CFG_Menu_Pagemaker_Create.prototype = {};	
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
		var TRIGGER = '#createBtn';
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
	
	var CFG_Context_Photoroll = function(){}; CFG_Context_Photoroll.prototype = {};	
	/**
	 * load Header menu 
	 * @param cfg
	 * @return
	 */
	CFG_Context_Photoroll.load = function(cfg){
		var Y = SNAPPI.Y;
		var CSS_ID = 'contextmenu-photoroll-markup';
		var TRIGGER = 'ul.photo-roll > section.thumbnail';
		var MARKUP = {
				id: CSS_ID,
				selector: '#'+CSS_ID,
				container: Y.one('#markup'),
				uri: '/combo/markup/photoRollContextMenu'
		};
		
		// reuse, if found
		if (Menu.find[CSS_ID]) 
			return Menu.find[CSS_ID];
		
		
		Menu.classInit(); 


		var callback = function(){
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};
	
	
	
	var CFG_Context_HiddenShot = function(){}; CFG_Context_HiddenShot.prototype = {};	
	/**
	 * load PhotoRoll contextmenu for HiddenShots .thumbnail
	 * @param cfg
	 * @return
	 */
	CFG_Context_HiddenShot.load = function(cfg){
		var Y = SNAPPI.Y;
		var CSS_ID = 'contextmenu-hiddenshot-markup';
		var TRIGGER = 'ul.substitutes > section.thumbnail';
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
			Menu.initContextMenu(MARKUP, TRIGGER, cfg);
		};
		return Menu.getMarkup(MARKUP , callback);
	};
	
	
	
	// SNAPPI.MenuAUI
	Menu.CFG = {
		'menu-header-markup': CFG_Menu_Header,
		'contextmenu-photoroll-markup': CFG_Context_Photoroll,
		'contextmenu-hiddenshot-markup': CFG_Context_HiddenShot,
		'menu-pagemaker-selected-create-markup': CFG_Menu_Pagemaker_Create, 
		end: null
	};
	
})();
