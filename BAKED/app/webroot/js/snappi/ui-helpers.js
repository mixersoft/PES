/**
 *
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero GNU General Public License for more details.
 *
 * You should have received a copy of the Affero GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 *
 */

(function() {
	var _Y = null;
	var PM = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.UIHelper = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.UIHelper = UIHelper;
	}	
	SNAPPI.namespace('SNAPPI.STATE');

	var UIHelper = function(cfg) {	}; 
	
	/*
	 * static methods/properties
	 */
	UIHelper.listen = {};		// global ref to active listeners
	
	UIHelper.nav = {
		'goto' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		}, 
		'orderBy' : function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		},
		'goSearch' : function() {
			var value = _Y.one('#search input').get('value');
			if (value) {
				if (value.length>2) {
					var here = window.location.href;
					var namedData = {q:value, page: null};
					window.location.href = SNAPPI.IO.setNamedParams(here, namedData);
					return true;
				} else {
					alert('please enter at least 3 chars in your search');
				}
			}  
			return false;
		},
		'showHelp': function(btn, node){
			try {
				var container = node || _Y.one('section.help');
				btn = _Y.one(btn);
				if (/\/help\/topic/.test(window.location.href)) {
					return;
				}
				if (btn.hasClass('green')) {
					container.addClass('hide');
					btn.replaceClass('green', 'blue-gloss');
				} else if (container.one('article.topic')) {
					container.removeClass('hide');
					btn.replaceClass('blue-gloss','green');
				} else {
					var article, uri, topics = container.getAttribute('topics').split(':');
					container.setContent().removeClass('hide');
					for (var i in topics) {
						uri = '/help/topic/'+topics[i];
						article = container.create('<article class="grid_16 topic wrap-v cf"></article>');
						container.append(article);
						var ioCfg = {
							parseContent: true,
							autoLoad: true,
							context: container,
							dataType: 'html',
						};
						ioCfg = SNAPPI.IO.getIORequestCfg(uri, {}, ioCfg);
						article.plug(_Y.LoadingMask,{});
						article.plug(_Y.Plugin.IO, ioCfg);
					}
					btn.replaceClass('blue-gloss','green');
				}
			} catch(e) {}
		},
		toggleDisplayOptions  : function(o){
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				UIHelper.nav.setDisplayOptions();
			} catch (e) {}
		},
		/*
		 * restore open/closed state for Gallery display options
		 */
		setDisplayOptions : function(){
			try {
				if (SNAPPI.STATE.showDisplayOptions) {
					_Y.one('section.gallery-header li.display-option').addClass('open');
					_Y.one('section.gallery-display-options').removeClass('hide');

					// for /photo/roll.ctp: init rating
					// TODO: move to a better spot?
					var ratingFilterNode = _Y.one('#filter-rating-parent');
			        if (ratingFilterNode) {
			        	SNAPPI.filter.initRating();
			        }
			        					
				} else {
					_Y.one('section.gallery-header li.display-option').removeClass('open');
					_Y.one('section.gallery-display-options').addClass('hide');
				}	
			} catch (e) {}
		},
		toggle_fullscreen : function(value) {
			if (value == undefined) value = SNAPPI.STATE.controller.isWide ? false : true;
			value = value ? 1 : null;
			var here = SNAPPI.IO.setNamedParams(SNAPPI.STATE.controller.here, {wide: value});
			window.location.href = here;
		},
		toggle_ItemMenu : function(e) {
			var ID_LOOKUP = {
				'group': 'contextmenu-group-markup',
				'person': 'contextmenu-person-markup',
				// use 'div.preview .FigureBox.PhotoPreview li.icon.context-menu' instead
				// 'photo': 'contextmenu-photoroll-markup',	// use div.preview .FigureBox.PhotoPreview li.icon.context-menu instead
			}
			var type = (SNAPPI.STATE.controller.label).toLowerCase();
			var CSS_ID = ID_LOOKUP[ type ];
	    	if (e==false && !SNAPPI.MenuAUI.find[CSS_ID]) return;
	    	// load/toggle menu
	    	
	    	var listenerNode = e.container;
	    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
	    		var itemMenuCfg = {
	    			CSS_ID: CSS_ID,
	    			// TRIGGER: 'div.item-class',
	    			// force_TRIGGER: 'div.item-class',
	    			force_TRIGGER: '.icon.context-menu',
	    			triggerType: type,		// NOTE: add .gallery.group to id=groups-preview-xhr
	    			align: { points:['tr', 'br'] },
	    			init_hidden: false,
	    			offset: {x:10, y:0},
				};
				
	    		SNAPPI.MenuAUI.CFG[CSS_ID].load(itemMenuCfg);
	    		e.container.one(itemMenuCfg.force_TRIGGER).setAttribute('uuid', SNAPPI.STATE.controller.xhrFrom.uuid);
	    		// stop LinkToClickListener
	    		listenerNode.listen['disable_LinkToClick'] = true;
	    	} else {
	    		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
				if (menu.get('disabled')) {
					menu.enable();
					menu.show();
					listenerNode.listen['disable_LinkToClick'] = false;
				} else {
					menu.disable();
					menu.hide();
					listenerNode.listen['disable_LinkToClick'] = true;
				}
	    	}	    	
		},
		toggle_ContextMenu : function(e, cfg) {
			cfg = cfg || {};
			// copied from SNAPPI.Gallery
			var ID_LOOKUP = {
				'group': 'contextmenu-group-markup',
				'person': 'contextmenu-person-markup',
				'photo': 'contextmenu-photoroll-markup',
				'photoPreview': 'contextmenu-photoroll-markup',
			}
			type = cfg.type || UIHelper.listeners.getGalleryType(e.currentTarget);
			// var isPreview = 0&& !e.currentTarget.test('.gallery.'+type+' .FigureBox');
	    	var CSS_ID = ID_LOOKUP[ type ];
	    	if (e==false && !SNAPPI.MenuAUI.find[CSS_ID]) return;
	    	
	    	// load/toggle contextmenu
	    	var listenerNode = cfg.listenerNode || e.currentTarget.ancestor('.container');
	    	if (!SNAPPI.MenuAUI.find[CSS_ID]) {
	    		var contextMenuCfg = {
	    			TRIGGER: ' .FigureBox',
	    			triggerType: type,		// NOTE: add .gallery.group to id=groups-preview-xhr
	    			currentTarget: e.currentTarget,	// init TRIGGER is currentTarget
	    			triggerRoot:  listenerNode,
	    			init_hidden: false,
				};
				contextMenuCfg = _Y.merge(contextMenuCfg, cfg);
	    		SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
	    		// stop LinkToClickListener
	    		listenerNode.listen['disable_LinkToClick'] = true;
	    	} else {
	    		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
	    		if (menu.get('disabled')) {
        			listenerNode.listen['disable_LinkToClick'] = false;
        		} else {
        			listenerNode.listen['disable_LinkToClick'] = true;
        		}
	    	}		
		}		
	};
	
	UIHelper.groups = {
		// groups, filter by groupType
		// formerly: PAGE.myGroups()
		myGroups : function(o){
			var set = /selected/.test(o.className) ? null : 1;
			var href = window.location.href;
			window.location.href = SNAPPI.IO.setNamedParams(href, {'filter-me':set});
		},
		getProperties : function(triggerType, node) {
			var data = [], 
				uuid = node.getAttribute('uuid') || node.get('id');
			switch(triggerType) {
				case 'group':
					data = PAGE.jsonData.Group || PAGE.jsonData.Membership; 
					break;
				case 'person':
					data = PAGE.jsonData.User || PAGE.jsonData.Member; 
					break;	
			}
			for (var i in data ) {
				if (uuid == data[i].id) {
					return data[i];
				}
			}
			return null;
		},
		/**
		 * @params cfg.gid, uuid of Group
		 * @params cfg.isExpress Boolean, NOT NULL
		 * @params cfg.node, _Y.Node for loading mask, menuItem
		 */
		isExpress: function(cfg){
			var data = {
					'data[Group][id]' : cfg.gid,
					'data[Group][isExpress]': cfg.isExpress
			};
			var uri = '/groups/express_upload/.json';	
			var loadingNode = cfg.node;
			if (loadingNode.io == undefined) {
				var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
					uri: uri ,
					parseContent:true,
					method: 'POST',
					qs: data,
					dataType: 'json',
					context: this,	
					arguments: cfg,
					on: {
						successJson:  function(e, id, o, args) {
							var resp = o.responseJson;
							if (args.menuItem) {
								try {
									if (args.isExpress) {
										args.menuItem.addClass('selected');
										// update local copy
										if (_Y.Lang.isArray(PAGE.jsonData.expressUploadGroups)) PAGE.jsonData.expressUploadGroups = {};
										PAGE.jsonData.expressUploadGroups[args.gid] = 1;
									} else {
										args.menuItem.removeClass('selected');
										delete (PAGE.jsonData.expressUploadGroups[args.gid]);
									}
								} catch(ex) {}
								
							}
							// args.node.io.loadingmask.hide();
							return false;
						}
					}
				});
	            loadingNode.plug(_Y.Plugin.IO, ioCfg );
			} else {
				loadingNode.io.set('data', data);
				loadingNode.io.set('context', this);
				loadingNode.io.set('uri', uri);
				loadingNode.io.set('arguments', cfg);
				loadingNode.io.start();
	        }
	        return;			
		},
	}
	UIHelper.create = {
		getStage : function(){
			var MAX_HEIGHT = 800;	
			var PADDING_TOP = 140;	// header+offsets = 140px
			var markup = "<div id='stage-2' class='pagemaker-stage'></div>";
			var cfg = {
				// selector: '#stage-2',
				markup: markup,
    			// uri: '/combo/markup/importComplete',
    			height: MAX_HEIGHT,		// -> dialog.top
    			width: 940,
    			tokens: {},
    		};
    		var stage, dialog = SNAPPI.Dialog.find['dialog-alert']; 
    		if (dialog 
    			&& (stage = dialog.getStdModNode('body').one('#stage-2'))) 
    		{
    			if (dialog.get('destroyed') == false) return stage;
    			else {
    				// reuse stage
    				stage.setContent('');
    				cfg.bodyNode = stage;
    			}
    		}
    		dialog = SNAPPI.Alert.load(cfg);
    		stage = dialog.getStdModNode('body').one('#stage-2');
    		stage.onrender = stage.onrender || _Y.on('snappi-pm:render', function(P, node){
    			var d = SNAPPI.Dialog.find['dialog-alert'];
    			var header = stage.one('.stage-header');
    			var header_h = header ? header.get('clientHeight') : 0;
    			var body_border = 20;	// margin: 10px 0 on node
    			var offset_top = 6 + 29 + 12    + 10;
    			var h = Math.min(node.get('clientHeight'), MAX_HEIGHT);
    			if (h < MAX_HEIGHT) {
    				stage.setStyle('overflowY', 'hidden');
    			} else {
    				stage.setStyle('overflowY', 'auto');
    			}
    			stage.setStyle('height', 'auto');
    			d.set('height', h + body_border + header_h + offset_top);
    			SNAPPI.setPageLoading(false);
    		})
    		
    		
			return stage;
		},
		/*
		 * find the active gallery/batch
		 */
		getCastingCall: function(){
			var g = SNAPPI.Gallery.find['uuid-'];
				// check .gallery.photo, then lightbox for selected 
			var batch = g && g.getSelected();
			if (!batch || !batch.count()) {
				g = SNAPPI.lightbox.Gallery;
				batch = SNAPPI.lightbox.getSelected();
			}
			var o = {
				batch: batch,
				gallery: g,
			}
			return o;
		},
		/*
		 * @params cfg.batch, cfg.gallery, output from getCastingCall()
		 * @return ioCfg for getting CastingCall from server
		 */
		postCastingCall: function(cfg){
			var ioCfg, 
				batch = cfg.batch,
				g = cfg.gallery;
			switch(g && g._cfg.type) {
				case 'Lightbox':  // use POST to get lightbox selected
					var uri = SNAPPI.lightbox._cfg.GET_CASTINGCALL_URI;
					var assetIds = new Array();
					batch.each(function(audition) {
						assetIds.push(audition.id);
		            }); 
		            var postData = {
		            	'data[Asset][ids]': assetIds.join(","),
		            }   
		            // deprecate, use pluginIO_RespondAsJson
		            var aidsAsString = assetIds.join(",");
					var data = "data[Asset][ids]=" + aidsAsString;
					
					ioCfg = {
						method : "POST",
						data : data,
						postData: postData,
						// qs : postData,	// for pluginIO_RespondAsJson
						uri : uri,
					};
					// SNAPPI.PM.main.launch(ioCfg);
					// SNAPPI.PM.main.go(this);
					break;
				case 'Photo':
					break;
			}
			return ioCfg;			
		},
		/*
		 * cfg.batch, cfg.gallery
		 */
		getSceneCfg: function(cfg){
			cfg = cfg || this.getCastingCall();
			/*
			 * check if we need to POST to get complete/updated results
			 */
			var sceneCfg = {
				roleCount: cfg.batch.count(),
				auditions: cfg.batch, 
				tryout: null,			// reset tryout, using auditions instead
				sortedhash: null,		// deprecate: reset tryout, using auditions instead
				fnDisplaySize: {h:800},
				// stage: this.getStage(), 	// use PM.pageMakerPlugin.setStage()
				noHeader: true,
				useHints: true,
				hideRepeats : false,
				performance: null,		// reset performance
			};
			sceneCfg = _Y.merge(sceneCfg, cfg);
			return sceneCfg;
		},
		/*
		 * require a POST to /photos/getCC/.json to get ALL auditions
		 */
		isPost: function(cfg){
			try {
				cfg = cfg || this.getCastingCall();
				var lightbox, g = cfg.gallery ? cfg.gallery : cfg;
				var isPreview = isSelectAll = false;
				lightbox = g.container.ancestor('#lightbox');
				if (g && g._cfg.type == 'Lightbox') {
					isPreview = (lightbox.hasClass('is-preview'));
					isSelectAll = cfg.batch.count() == g.auditionSH.count();
					return isPreview && isSelectAll;
				}
			} catch(e) {}
			return false;
		},
		getCreate: function(cfg) {
			cfg = cfg || this.getCastingCall();
			var g = cfg.gallery ? cfg.gallery : cfg;
			var fn_create;
			if (this.isPost(cfg)) {
				// #Lightbox.is-preview
				// use ioCfg and POST for auditions
				// use sceneCfg.tryout
				PM.pageMakerPlugin.sceneCfg.auditions = null; 
				if (!g.node.listen['content-changed']) {
					g.node.listen['content-changed'] = _Y.on('snappi:lightbox-content-changed', function(L){
						try {
							// mark as stale
							PM.pageMakerPlugin.ioCfg.complete = false;
							delete PM.main.tryoutLoadStatus;
							delete PM.main.tryoutSortedhash;
						} catch (e){}
					});
				}
				if (!PM.pageMakerPlugin.ioCfg.complete) {
					// Plugin already loaded, just launch with Post
					var ioCfg = this.postCastingCall(cfg);
					PM.pageMakerPlugin.setPost(ioCfg);
					fn_create = function() {
						SNAPPI.PM.main.launch(PM.pageMakerPlugin);
					}
				} else {
					var check = PM.pageMakerPlugin.sceneCfg.tryout;
					PM.pageMakerPlugin.setStage(this.getStage());
					fn_create = SNAPPI.PM.main.makePageGallery;
				}
				return fn_create;
				/*
				 * 	return ******************************
				 */				
			} else {
				// prepare for simple case
				var sceneCfg = this.getSceneCfg(cfg);
				PM.pageMakerPlugin.setScene(sceneCfg);
				PM.pageMakerPlugin.setStage(this.getStage());
				fn_create = SNAPPI.PM.main.makePageGallery
				return fn_create;
			}
			
			return;
			/*
			 * 
			 */
			switch(g && g._cfg.type) {
				case 'Lightbox':  // use POST to get lightbox selected
					// SNAPPI.PM.main.launch(ioCfg);
					var lightbox = g.container.ancestor('#lightbox');
					if (lightbox.hasClass('is-preview')) {
						// use ioCfg and POST for auditions
						// use sceneCfg.tryout
						PM.pageMakerPlugin.sceneCfg.auditions = null; 
						
						if (!PM.pageMakerPlugin.ioCfg.complete) {
							// Plugin already loaded, just launch with Post
							var ioCfg = this.postCastingCall(cfg);
							PM.pageMakerPlugin.setPost(ioCfg);
							fn_create = function() {
								SNAPPI.PM.main.launch(PM.pageMakerPlugin);
							}
						} else {
							// TODO: remember to invalidate after lightbox drop
							var check = PM.pageMakerPlugin.sceneCfg.tryout;
							PM.pageMakerPlugin.setStage(this.getStage());
							fn_create = SNAPPI.PM.main.makePageGallery;
						}
						return fn_create;
						/*
						 * 	return ******************************
						 */
					} else {
						// simple case. use lightbox.Gallery.auditionSH 
						fn_create = SNAPPI.PM.main.makePageGallery;
					}
					break;
				case 'Photo':
					// simple case. use sceneCfg.auditions to render performance
					fn_create = SNAPPI.PM.main.makePageGallery;
					break;
			}	
			
			// prepare for simple case
			var sceneCfg = this.getSceneCfg(cfg);
			PM.pageMakerPlugin.setScene(sceneCfg);
			PM.pageMakerPlugin.setStage(this.getStage());
			
			return 	fn_create;	
		},		
		/*
		 * load/load/init/create lifecycle 
		 * 	1a) load EXTERNAL plugin module, load_PageMakerPlugin 
		 * 		-> listen: afterPageMakerPluginLoad, or onReady_PageMakerPlugin()
		 *  1b) load PM Pagemaker modules, PM.pageMakerPlugin.load() 
		 * 		-> listen:'snappi-pm:afterPagemakerLoad'
		 *  2) init Pagemaker with castingCall, PM.main.launch() 
		 * 		-> listen:'snappi-pm:after-launch' 
		 *  3) create Story: Gallery.createPageGallery()
		 */
		// load 'pagemaker-base' MODULE if SNAPPI.PM.PageMakerPlugin class does not exist
		load_PageMakerPlugin: function(external_Y){
			PM = SNAPPI.PM;
			// check plugin
			if (!PM || !PM.pageMakerPlugin) {
				/*
				 * lazyLoad PageMakerPlugin module
				 */
				var modules = ['pagemaker-base','snappi-dialog-aui']
				/*
				 * (after PageMakerPlugin load,)
				 * IMMEDIATELY lazyLoad PageMaker module
				 * listen: snappi-pm:afterPagemakerLoad
				 */
				var callback = function(Y, result){
					Y.fire('snappi-pm:afterPageMakerPluginLoad', Y);
					external_Y.fire('snappi-pm:afterPageMakerPluginLoad', Y);
					
					// TODO: put in 'snappi-pm:afterPageMakerPluginLoad' handler?
					PM.pageMakerPlugin = new PM.PageMakerPlugin(external_Y);
					PM.pageMakerPlugin.load();
				};
				SNAPPI.LazyLoad.use(modules, callback);
			} else if (!SNAPPI.PM.main) {
				// after-load: launch/create Pagemaker page 
				var launched = _Y.on('snappi-pm:after-launch', function(stage) {
	        		launched.detach();
	        		// node.ynode().set('innerHTML', 'Create Page');
	        		var create = this.getCreate();
	        		create();
	        	}, g);
		        	
				// Plugin alread loaded, just launch Pagemaker
				var sceneCfg = this.getSceneCfg(this.getCastingCall());
				PM.pageMakerPlugin.setScene(sceneCfg);
				PM.main.launch(PM.pageMakerPlugin);
			} else {
				var create = this.getCreate();
	        	create();
			}			
		},
		// on 'snappi-pm:afterPagemakerLoad'
		afterLoad_PageMakerPlugin: function(cfg){
console.error("2) afterLoad_PageMakerPlugin");			
			var ioCfg, sceneCfg = this.getSceneCfg(cfg);
			if (this.isPost(cfg)) ioCfg = this.postCastingCall(cfg);
			var PM = SNAPPI.PM;
			// PM.pageMakerPlugin.setStage(sceneCfg.stage);
			PM.pageMakerPlugin.setScene(sceneCfg).setPost(ioCfg);
			PM.pageMakerPlugin.setStage(this.getStage());
console.error("2b) FIRST call to PM.main.launch. ioCfg set");			
			PM.main.launch(PM.pageMakerPlugin);	// 'Photo', ioCfg=null
		},
		afterLaunch_PageMakerPlugin: function(cfg){
		},
		launchPagemaker : function(){
			var cfg = this.getCastingCall();
			var g = cfg.gallery;
			
        	var loaded = _Y.on('snappi-pm:afterPagemakerLoad', function(PM_Y) {
console.error("1) launchPagemaker(): snappi-pm:afterPagemakerLoad");        		
        		loaded.detach();
				UIHelper.create.afterLoad_PageMakerPlugin(cfg);
        	});
			
			// after-load: launch/create Pagemaker page 
			var launched = _Y.on('snappi-pm:after-launch', function(stage) {
console.error("4) launchPagemaker(): snappi-pm:after-launch"); 				
        		launched.detach();
        		// node.ynode().set('innerHTML', 'Create Page');
        		// fn_create();
        		var create = UIHelper.create.getCreate(cfg);
        		create();
        	}, g);
        	
        	this.load_PageMakerPlugin(_Y);
		},
	}
	UIHelper.util = {
		checkSupportedBrowser : function(){
			//TODO: note: need to check on first _Y.use for ie, not in _Y.ready()
			try {
				if (1 ) {
					var browserOk = _Y.UA.gecko || _Y.UA.webkit;
					if (!browserOk) {
						// show recomended browser
						// alert('unsupported browser');
					} 
				}
			}catch(e){}			
		}
	}	
	UIHelper.markup = {
		set_ItemHeader_WindowOptions: function(){
			try {
				var found = _Y.one('div.properties.hide');
				if (found) {
					var itemHeader = _Y.one('.item-header');
					SNAPPI.UIHelper.listeners['WindowOptionClick'](itemHeader);
					itemHeader.one('.window-options').removeClass('hide');
				}
			} catch (e) {}
		}
	}
	UIHelper.listeners = {
		/*
		 * markup "gallery" helpers, migrates to SNAPPI.Gallery when ready
		 * compares to GalleryFactory.listeners{}
		 */
		getGalleryType : function(node) {
			node = node || _Y.one('.gallery-container section.gallery');
			if (node.hasClass('FigureBox')) {
				if (node.hasClass('Group')) return 'group';
				if (node.hasClass('Person')) return 'person';	
			} else {
				node = node.ancestor('section.gallery', true);
				if (node.hasClass('group')) return 'group';
				if (node.hasClass('person')) return 'person';	
			}
			return null;
		},
        LinkToClick : function(cfg) {
        	var node = cfg.node || _Y.one('.gallery .container');
        	var action = 'LinkToClick';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('click', 
	                function(e){
	            		var linkTo = e.currentTarget.getAttribute('linkTo');
	            		if (linkTo) {
	            			e.halt();	// intercepts A.click action
		                	if (this.listen['disable_LinkToClick']) {
		                		UIHelper.nav.toggle_ContextMenu(e);	// hide contextmenu
		                		return;		// allows temp disabling of listener
		                	}	            			
	            			window.location.href = linkTo;
	            		} 
	                }, '.FigureBox > figure > img, figure > a > img', node);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        },   
        /**
         * @params cfg object, cfg.node, 
         * 		deprecate: cfg.type = [group, photo, person], 
         * 		i.e. .FigureBox.Group
         */
        ItemHeaderClick : function(cfg) {
        	var node = cfg.node || _Y.one('section.item-header');
        	var action = 'ItemHeaderClick';
        	var selector = '*';
        	// if (cfg.type) selector += '.'+cfg.type ;
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('click', 
	                function(e){
	                	e.halt();
	                	UIHelper.nav.toggle_ItemMenu(e);
	                }, selector, UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        },             
        /**
         * @params cfg object, cfg.node, cfg.type = [group, photo, person], 
         * 		i.e. .FigureBox.Group
         */
        ContextMenuClick : function(cfg) {
        	var node = cfg.node || _Y.one('.gallery .container');
        	var action = 'ContextMenuClick';
        	var selector = '.FigureBox';
        	if (cfg.type) selector += '.'+cfg.type ;
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('contextmenu', 
	                function(e){
	                	e.halt();
	                	UIHelper.nav.toggle_ContextMenu(e);
	                }, selector, UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        }, 	        
        MultiSelect : function (node) {
        	node = node || _Y.one('.gallery .container');
        	var container = node;
        	var action = 'MultiSelect';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
            	SNAPPI.multiSelect.listen(node, true);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];	        	
        	
        	// select-all checkbox listener
        	var galleryHeader = _Y.one('.gallery-container .gallery-header');
        	if (galleryHeader && !container.listen['selectAll']) {
	        	container.listen['selectAll'] = galleryHeader.delegate('click', 
	        	function(e){
	        		var checked = e.currentTarget.get('checked');
	        		if (checked) this.all('.FigureBox').addClass('selected');
	        		else {
	        			this.all('.FigureBox').removeClass('selected');
	        			SNAPPI.STATE.selectAllPages = false;
	        		}
	        	},'li.select-all input[type="checkbox"]', container);
	        	// enable select-all menu
				SNAPPI.MenuAUI.initMenus({'menu-select-all-markup':1});
        	}
        	return;
        },		
        DisplayOptionClick : function(node) {
        	node = node || _Y.one('.gallery-display-options');
        	var action = 'DisplayOptionClick';
        	
        	node.listen = node.listen || {};
            if (node.listen[action] == undefined) {
				node.listen[action] = node.delegate('click', 
	                function(e){
	                	// hide contextmenu when opening display option menus
	                	UIHelper.nav.toggle_ContextMenu(false);	
	                	var action = e.currentTarget.getAttribute('action').split(':');
			    		switch(action[0]) {
			    			case 'filter':
			    				break;
			    			case 'sort':
			    				break;
			    		}		                	
	                }, 'ul > li.btn', UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];  
		},
        /*
         * Click-Action listener/handlers
         * 	start 'click' listener for action=
         * 		set-display-size:[size] 
         * 		set-display-view:[mode]
         * adds minimize/maximize btns for item-header
         */
        WindowOptionClick : function(node) {
        	node = node || _Y.one('.item-header');        	
        	if (!node) return;
        	var action = 'WindowOptionClick';
        	node.listen = node.listen || {};
        	var delegate_container = node.one('.window-options');
            if (delegate_container && node.listen[action] == undefined) {
            	delegate_container.removeClass('hide');
				node.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// action=[set-display-size:[size] | set-display-view:[mode]]
	                	// context = node
	                	if (this.hasClass('item-header')) {
	                		// show/hide properties
	                		var properties = this.next('.properties');
	                		var action = e.currentTarget.getAttribute('action').split(':');
	                		switch(action[0]) {
				    			case 'set-display-view':
				    				if (action[1]=='minimize') properties.addClass('hide');
				    				else properties.removeClass('hide');
				    				break;
			    			}	
	                	}
	                }, 'ul > li', node);
				// back reference
				UIHelper.listen[action] = node.listen[action];	                
			}
        },
        DragDrop : function(){
        	SNAPPI.DragDrop.pluginDrop(_Y.all('.droppable'));
        	SNAPPI.DragDrop.startListeners();
        },
        CommentReply : function(node) {
        	node = node || _Y.one('div.comments-main');  
        	if (_Y.Lang.isString(node)) node = _Y.one(node);      	
        	var action = 'CommentReply';
        	node.listen = node.listen || {};
        	if (node.listen[action] == undefined) {
	        	node.listen[action] = node.delegate('click', 
	                function(e){
	                	var href = e.currentTarget.getAttribute('href');
	                	var post = node.one('div.post');
	                	var form = post.one('form').setAttribute('action', href);
	                	var before = e.currentTarget.ancestor('div.comments');
	                	before.insert(post, 'after');
	                	var title = before.one('span.title a');
	                	if (title && title.get('innerHTML')) {
	                		title = title.get('innerHTML');
	                		post.one('label[for="CommentTitle"]').addClass('hide');
	                	} else {
	                		title = '';
	                		post.one('label[for="CommentTitle"]').removeClass('hide');
	                	}
	                	post.one('input#CommentTitle').set('value', title);
	                }, 'div.posted a.reply', node);
				// back reference
				UIHelper.listen[action] = node.listen[action];
			}
        }
	}
	
	
})();