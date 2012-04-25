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
	var PMPlugin = null;		// PM.pageMakerPlugin on load
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
				btn = _Y.one() || _Y.one('nav.user li.help span');
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
		setDisplayOptions : function(value){
			try {
				if (value !== undefined) SNAPPI.STATE.showDisplayOptions = value ? 1 : 0;
				if (SNAPPI.STATE.showDisplayOptions) {
					_Y.one('section.gallery-header li.display-option').addClass('open');
					_Y.one('section.gallery-display-options').removeClass('hide');
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
	    		listenerNode.listen['disable_ThumbnailClick'] = true;
	    	} else {
	    		var menu = SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
				if (menu.get('disabled')) {
					menu.enable();
					menu.show();
					listenerNode.listen['disable_ThumbnailClick'] = false;
				} else {
					menu.disable();
					menu.hide();
					listenerNode.listen['disable_ThumbnailClick'] = true;
				}
	    	}	    	
		},
		toggle_ContextMenu : function(e, cfg) {
			cfg = cfg || {};
	    	var CSS_ID = UIHelper.util.getContextMenuIdFromNode(e.currentTarget, cfg.type);
	    	var menu = SNAPPI.MenuAUI.find[CSS_ID];
	    	if (e==false && !menu) return;
	    	
	    	// load/toggle contextmenu
	    	var listenerNode = cfg.listenerNode || e.currentTarget.ancestor('.container');
	    	if (!menu) {
	    		var contextMenuCfg = {
	    			TRIGGER: ' .FigureBox',
	    			triggerType: cfg.type.toLowerCase(),		// NOTE: add .gallery.group to id=groups-preview-xhr
	    			currentTarget: e.currentTarget,	// init TRIGGER is currentTarget
	    			triggerRoot:  listenerNode,
	    			init_hidden: false,
				};
				contextMenuCfg = _Y.merge(contextMenuCfg, cfg);
	    		SNAPPI.MenuAUI.CFG[CSS_ID].load(contextMenuCfg);
	    	} else {
	    		SNAPPI.MenuAUI.toggleEnabled(CSS_ID, e);
	    	}		
		}		
	};
	UIHelper.action = {
		get: {
			filterByOptions: function(dom) {
				var container = dom.ynode();
				if (container.io) return;
				// TODO: check if we have already loaded batchIds
				var controller = SNAPPI.STATE.controller;
				var href = '/'+controller.alias+'/batch_ids/';
				if (controller.alias == 'my') href += '.json';
				else href += controller.xhrFrom.uuid +'/.json';
				var check;
		/*
		 * plugin _Y.Plugin.IO
		 */
		var loadingmaskTarget = container.get('parentNode');
		container.plug(_Y.LoadingMask, {
			strings: {loading:''}, 	// BUG: A.LoadingMask
			target: loadingmaskTarget,
		});    			
		container.loadingmask._conf.data.value['target'] = loadingmaskTarget;
		container.loadingmask.overlayMask._conf.data.value['target'] = container.loadingmask._conf.data.value['target'];
		container.loadingmask.set('zIndex', 10);
		container.loadingmask.overlayMask.set('zIndex', 10);
		var args = {
				uri: href,
				node: container, 
		};
		args.loadingmask = container.loadingmask;
		var	ioCfg = {
   					uri: args.uri,
					context: container,
					arguments: args, 
					method: "POST",
					dataType: 'json',
					on: {
						successJson: function(e, i, o,args){
							var resp = o.responseJson;
							if (resp.response && resp.response.batchIds) {
								var selected, option, value, dateLabel;	// tranform JSON to options
								selected = args.node.one('option').get('value');
								for (var i in resp.response.batchIds) {
									value = resp.response.batchIds[i];
									dateLabel = SNAPPI.util.formatUnixtimeAsTimeAgo(value);
									option = args.node.create('<option></option>');
									option.set('value', value).setContent(dateLabel);
									if (value == selected) option.set('selected', 'selected');
									args.node.append(option);
								}
								args.node.append('<option value="">(remove filter)</option>');
							}
							return false;
						}, 
						complete: function(e, i, o, args) {
							args.loadingmask.hide();
						},
						failure : function (e, i, o, args) {
							// post failure or timeout
						},
					}
			};
		container.loadingmask.show();	
		container.plug(_Y.Plugin.IO, SNAPPI.IO.pluginIO_RespondAsJson(ioCfg));				
				
			}
		},
		filter: {
			batchId: function(dom) {
				var n = dom.ynode();
				var value = n.get('value');
				if (!value) value = null;
				var href = SNAPPI.io.setNamedParams(window.location.href, {'batchId':value})
				window.location.href = href;
			},
			rating: function(e) {
				var href = window.location.href;
				if (e.target.hasClass('remove')) {  	// remove tag
					window.location.href = SNAPPI.IO.setNamedParams(href, {'rating':null});
				} 
			},
			tag: function(e){
				if (e.target.test('input.tag')) {
					e.halt();	// halt click on input[text] field
					return;
				}
				var tag, href = window.location.href;
				if (e.target.hasClass('remove')) {		// remove tag
					window.location.href = SNAPPI.IO.setNamedParams(href, {'context':'remove'});
				} else {
					tag = e.currentTarget.one('input').get('value');
					// TODO: using Context to set Tag filter for now. set directly?
					window.location.href = SNAPPI.IO.setNamedParams(href, {'context':'Tag~'+tag});
				}
			}, 
		},
		'section-view': {
			montage: function(e, view){
				if (!e.currentTarget.hasClass('focus')) {
					SNAPPI.io.writeSession({'section-header.Photo':'Montage'});
				}
				e.currentTarget.addClass('focus').siblings().removeClass('focus');
				var montage = _Y.one('.montage-container div.pageGallery');
				if (montage) montage.ancestor('.montage-container').removeClass('hide');
				else {
					SNAPPI.setPageLoading(true);
					SNAPPI.UIHelper.create._GET_MONTAGE();
				}
				_Y.one('.gallery-container').addClass('hide');
			},
			gallery: function(e, view){
				if (!e.currentTarget.hasClass('focus')) {
					SNAPPI.io.writeSession({'section-header.Photo':'Gallery'});
				}
				e.currentTarget.addClass('focus').siblings().removeClass('focus');
				// TODO: switch to g._cfg.type ??
				var ID_PREFIX = SNAPPI.Factory.Gallery[SNAPPI.STATE.galleryType].defaultCfg.ID_PREFIX;
				var g = SNAPPI.Gallery.find[ID_PREFIX];
				if (!g) {
					SNAPPI.setPageLoading(true);
					g = new SNAPPI.Gallery({type:SNAPPI.STATE.galleryType});	
				}
				if (g) g.container.ancestor('.gallery-container').removeClass('hide');
				_Y.one('.montage-container').addClass('hide');
			},
		}
	}
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
										args.menuItem.setContent('&#x25B6;'+args.menuItem.origLabel);
										// update local copy
										if (_Y.Lang.isArray(PAGE.jsonData.expressUploadGroups)) PAGE.jsonData.expressUploadGroups = {};
										PAGE.jsonData.expressUploadGroups[args.gid] = 1;
									} else {
										args.menuItem.setContent(args.menuItem.origLabel);
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
		getStage_modal : function(cfg){
			cfg = cfg || {};
			var MAX_HEIGHT = 800;	
			var PADDING_TOP = 140;	// header+offsets = 140px
			var markup = "<div id='stage-2' class='pagemaker-stage'><div class='stage-body'></div></div>";
			var dialogCfg = {
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
    				// dialog.getStdModNode('header').setContent('');
    				stage.setContent('');
    				dialogCfg.bodyNode = stage;
    			}
    		}
    		dialog = SNAPPI.Alert.load(dialogCfg);
    		
    		var stageTitle = cfg.stageTitle || 'Create Story'; 
    		dialog.setStdModContent('header', '<span>'+stageTitle+'</span>', 'before');
    		stage = dialog.getStdModNode('body').one('#stage-2');
    		stage.noHeader = true;
    		if (!stage.listen) {
    			stage.listen = {};
    			/*
    			 * @params node div.pageGallery
    			 */
    			var _setStageDim = function(node){
    					if (!node.ancestor('#stage-2')) return; 
    					stage.removeClass('hide');
		    			var d = SNAPPI.Dialog.find['dialog-alert'];
		    			_Y.fire('snappi:dialog-body-rendered', d, {
		    				bodySelector:'.stage-body',
		    				marginH:20,
		    			});
						try {
		    				if (n.get('clientHeight') < node.origRect.H) {
		    					PMPlugin.player.winResize(null);
		    				}	
		    			} catch(e){}
		    			SNAPPI.setPageLoading(false);
		    			try {
		    				/* re-enable hints for PM, disabled on dialog/menu show */
				    		var hint = SNAPPI.Hint.instance;
				    		hint.set('disabled', false);
				    		hint.set('trigger', hint.triggers.join(','));
				    		SNAPPI.Hint.sleepHints(0);
				    		/* end */
		    			} catch(e){}	
		    			return;		    			
		    	};
	    		stage.listen['render'] = _Y.on('snappi-pm:render', function(Performance, node){
		    			_setStageDim(node);
		    			var CSS_ID = 'menu-pm-toolbar-edit';	
						SNAPPI.PM.Menu.copyMenuToDialogHeader(CSS_ID, null);
						try {
							var dialog = SNAPPI.Dialog.find['dialog-alert'],
							header = dialog.getStdModNode('header');
							var STORY_ID = PAGE.Cookie.pagemaker['STORY_ID'];
							if (STORY_ID) header.one('input#story_id').setAttribute('value', STORY_ID);
						} catch(e){}
	    			}, this,  _setStageDim);
	    		stage.listen['resize'] = _Y.on('snappi-pm:resize', 
	    			function(player, containerH){
	    				var node = this.one('div.pageGallery');
	    				_setStageDim(node);
	    			}, stage);
	    			
	    		/*
	    		 * on first render
	    		 */	
	    		_Y.once('snappi-pm:render', function(){	
	    			try {
	    			SNAPPI.Hint.flushQueue();		// if Hint already available    			
					SNAPPI.Hint.instance.set('disabled', false);
					} catch(e){}
	    		});	
	    		
    		}
    		stage.stageType = cfg.stageType || 'modal';
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
				try {
					g = SNAPPI.lightbox.Gallery;
					batch = SNAPPI.lightbox.getSelected();
				} catch (e) {}
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
			if (!cfg || !cfg.batch.count()) cfg = this.getCastingCall(); 
			/*
			 * check if we need to POST to get complete/updated results
			 */
			var sceneCfg = {
				roleCount: cfg.batch.count(),
				auditions: cfg.batch, 
				tryout: null,			// reset tryout, using auditions instead
				sortedhash: null,		// deprecate: reset tryout, using auditions instead
				fnDisplaySize: {h:800},
				// stage: this.getStage_modal(), 	// use PMPlugin.setStage()
				noHeader: true,
				useHints: true,
				hideRepeats : false,
				performance: null,		// reset performance
				// thumbPrefix: 'bm',	// use 'bm' for montage
			};
			sceneCfg = _Y.merge(sceneCfg, cfg);
			return sceneCfg;
		},
		/*
		 * require a POST to /photos/getCC/.json to get ALL auditions
		 */
		isPost: function(cfg){
			try {
				if (!cfg || !cfg.batch.count()) cfg = this.getCastingCall();
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
			if (!cfg || !cfg.batch.count()) cfg = this.getCastingCall();
			var g = cfg.gallery ? cfg.gallery : cfg;
			var fn_create;
			if (this.isPost(cfg)) {
				// #Lightbox.is-preview
				// use ioCfg and POST for auditions
				// use sceneCfg.tryout
				PMPlugin.sceneCfg.auditions = null; 
				if (!g.node.listen['content-changed']) {
					g.node.listen['content-changed'] = _Y.on('snappi:lightbox-content-changed', function(L){
						try {
							// mark as stale
							PMPlugin.ioCfg.complete = false;
							delete PM.main.tryoutLoadStatus;
							delete PM.main.tryoutSortedhash;
						} catch (e){}
					});
				}
				if (!PMPlugin.ioCfg.complete) {
					// Plugin already loaded, just launch with Post
					var ioCfg = this.postCastingCall(cfg);
					PMPlugin.setPost(ioCfg);
					fn_create = function() {
						SNAPPI.PM.main.launch(PM.pageMakerPlugin);
					}
				} else {
					var check = PMPlugin.sceneCfg.tryout;
					var stage = (cfg.getStage) ? cfg.getStage(cfg) : this.getStage_modal(cfg);
					PMPlugin.setStage(stage);
					fn_create = SNAPPI.PM.main.makePageGallery;
				}
				return fn_create;
				/*
				 * 	return ******************************
				 */				
			} else {
				// prepare for simple case
				var sceneCfg = this.getSceneCfg(cfg);
				PMPlugin.setScene(sceneCfg);
				var stage = (cfg.getStage) ? cfg.getStage(cfg) : this.getStage_modal(cfg);
				PMPlugin.setStage(stage);
				fn_create = SNAPPI.PM.main.makePageGallery
				return fn_create;
			}
			
			return;
		},		
		/*
		 * load/load/init/create lifecycle 
		 * 	1a) load EXTERNAL plugin module, load_PageMakerPlugin 
		 * 		-> listen: afterPageMakerPluginLoad, or onReady_PageMakerPlugin()
		 *  1b) load PM Pagemaker modules, PMPlugin.load() 
		 * 		-> listen:'snappi-pm:pagemaker-load-complete'
		 *  2) init Pagemaker with castingCall, PM.main.launch() 
		 * 		-> listen:'snappi-pm:pagemaker-launch-complete' 
		 *  3) create Story: Gallery.createPageGallery()
		 */
		// load 'pagemaker-base' MODULE if SNAPPI.PM.PageMakerPlugin class does not exist
		load_PageMakerPlugin: function(external_Y, cfg){
			PM = SNAPPI.PM;
			external_Y.once('snappi-pm:PageMakerPlugin-load-complete', function(){
			    try {
					/*
		    		 * remove existing Hints, just show story hints
		    		 */
		    		SNAPPI.Hint.lookupHintByTriggerSH.clear();
		    		SNAPPI.STATE.hints['HINT_PMToolbarEdit'] = true;
		    		SNAPPI.STATE.hints['HINT_PMPlay'] = true;
	    		} catch (e){}
	    	});
			
			// check plugin
			if (!PM || !PM.pageMakerPlugin) {
				/*
				 * lazyLoad PageMakerPlugin module
				 */
				var modules = ['pagemaker-base','snappi-dialog-aui']
				var callback = function(Y, result){
					PM = SNAPPI.PM;
					/*
					 * (after PageMakerPlugin load,)
					 * DO NOT wait for 'snappi:lazyload-complete', 
					 * 		i.e. after LazyLoad.helpers.after_LazyLoadCallback()
					 * IMMEDIATELY lazyLoad PageMaker module
					 * listen: snappi-pm:pagemaker-load-complete
					 */
					Y.fire('snappi-pm:PageMakerPlugin-load-complete', Y);
					external_Y.fire('snappi-pm:PageMakerPlugin-load-complete', Y);
// console.info("snappi-pm:PageMakerPlugin-load-complete");					
					// TODO: put in 'snappi-pm:afterPageMakerPluginLoad' handler?
					PMPlugin = PM.pageMakerPlugin = new PM.PageMakerPlugin(external_Y);
					PMPlugin.load();
				};
				SNAPPI.LazyLoad.use(modules, callback);
				return;
			}
			
			if (!SNAPPI.PM.main) {
				// should be same as this.launch_Pagemaker()
				// after-load: launch/create Pagemaker page 
				var launched = _Y.on('snappi-pm:pagemaker-launch-complete', function(stage) {
	        		launched.detach();
	        		// node.ynode().set('innerHTML', 'Create Page');
	        		var create = this.getCreate();
	        		create();
	        	}, this);
		        	
				// Plugin alread loaded, just launch Pagemaker
				var sceneCfg = this.getSceneCfg(this.getCastingCall());
				PMPlugin.setScene(sceneCfg);
				PM.main.launch(PM.pageMakerPlugin);
				return;
			} 
			
			// Plugin loaded AND launched, ready to create()
			var Plugin = PM.pageMakerPlugin;
			if (Plugin.stage 
				&& Plugin.stage.stageType
				&& cfg.stageType
				&& Plugin.stage.stageType != cfg.stageType){
				// redo pre-launch
				cfg = _Y.merge(cfg, this.getCastingCall());
				var ioCfg, sceneCfg = this.getSceneCfg(cfg);
				if (this.isPost(cfg)) ioCfg = this.postCastingCall(cfg);
				PMPlugin.setScene(sceneCfg).setPost(ioCfg);
				var stage = (cfg.getStage) ? cfg.getStage() : this.getStage_modal();
				PMPlugin.setStage(stage);
				// ready to create?
			} 
			var create = this.getCreate(cfg);
			_Y.later(100, this, create);
			
		},
		// on 'snappi-pm:pagemaker-load-complete'
		launch_PageMaker: function(cfg){
// console.error("2a) on 'snappi-pm:pagemaker-load-complete'");	
			var ioCfg, sceneCfg = this.getSceneCfg(cfg);
			if (this.isPost(cfg)) ioCfg = this.postCastingCall(cfg);
			var PM = SNAPPI.PM;
			// PMPlugin.setStage(sceneCfg.stage);
			PMPlugin.setScene(sceneCfg).setPost(ioCfg);
			var stage = (cfg.getStage) ? cfg.getStage() : this.getStage_modal();
			PMPlugin.setStage(stage);
// console.error("2b) FIRST call to PM.main.launch. ioCfg set");			
			PM.main.launch(PM.pageMakerPlugin);	// 'Photo', ioCfg=null
		},
		launchComplete_PageMakerPlugin: function(cfg){
		},
		// entry point for Stories
		load_then_launch_PageMaker : function(cfg){
			if (!cfg || !cfg.batch.count()) cfg = this.getCastingCall();
			cfg.arrangement = null;
			cfg.spacing = 2;		// border spacing
			cfg.stageType = cfg.stageType || 'modal';
			var g = cfg.gallery;
			
        	var loaded = _Y.on('snappi-pm:pagemaker-load-complete', function(PM_Y) {
// console.error("1) load_then_launch_PageMaker(): snappi-pm:pagemaker-load-complete");        		
        		loaded.detach();
				UIHelper.create.launch_PageMaker(cfg);
        	});
			
			// after-load: launch/create Pagemaker page 
			var launched = _Y.on('snappi-pm:pagemaker-launch-complete', function(stage) {
// console.error("4) load_then_launch_PageMaker(): snappi-pm:pagemaker-launch-complete"); 				
        		launched.detach();
        		// node.ynode().set('innerHTML', 'Create Page');
        		// fn_create();
        		var create = UIHelper.create.getCreate(cfg);
        		_Y.later(100, this, create);
        	}, g);
        	
        	this.load_PageMakerPlugin(_Y, cfg);
		},
		// set cfg.batch, cfg.getStage, cfg.gallery???
		load_then_launch_Montage : function(cfg){
			var g = cfg.gallery;

        	var loaded = _Y.on('snappi-pm:pagemaker-load-complete', function(PM_Y) {
        		loaded.detach();
				UIHelper.create.launch_PageMaker(cfg);
        	});
			
			// after-load: launch/create Pagemaker page 
			var launched = _Y.on('snappi-pm:pagemaker-launch-complete', function(stage) {
        		launched.detach();
        		// node.ynode().set('innerHTML', 'Create Page');
        		// fn_create();
        		var create = UIHelper.create.getCreate(cfg);
        		// SNAPPI.setPageLoading(true);
        		_Y.later(100, this, create);
        	}, g);
        	
        	this.load_PageMakerPlugin(_Y, cfg);
		},
		getStage_montage : function(cfg) {
				cfg = cfg || {};
				var selector = cfg.selector || '#content .montage-container';
				var stage = _Y.one(selector);
				if (!stage) {
					var markup = "<section class='montage-container container grid_16'><div class='stage-body'></div></section>";
					_Y.one('nav.section-header').insert( markup ,'after');	
					stage = _Y.one(selector);
					stage.noHeader = cfg.noHeader;
					stage.stageType = 'montage';
				} 
				return stage;
		},
		_GET_MONTAGE : function(cfg){
			var cfg = cfg || {};
			try {
				var g = _Y.one('.gallery.photo').Gallery;
				cfg.gallery = g;
				cfg.batch = g.auditionSH.slice(0,16);	
			} catch (e) {
				var onDuplicate = SNAPPI.Auditions.onDuplicate_REPLACE;
				var castingCall = cfg.castingCall || PAGE.jsonData.castingCall;
				if (!castingCall.auditionSH) { 
					var auditionSH = SNAPPI.Auditions.parseCastingCall(
							castingCall, 
							null, 
							null, 
							onDuplicate);
					}
				cfg.batch = castingCall.auditionSH;	
				if (cfg.roleCount)	{
					var max = Math.min(castingCall.auditionSH.count(), cfg.roleCount.hi);
					var min = Math.min(castingCall.auditionSH.count(), cfg.roleCount.lo);
					var roleCount = Math.floor(Math.random()*(max-min+1)) + min;
console.info('Getting Story for rolecount='+roleCount);					
					cfg.batch = cfg.batch.slice(0, roleCount);
				}
			}
			
			// skip slice, use Role.suggestedPhotoId
			cfg.stageType = cfg.stageType || 'montage';
			cfg.noHeader = true;
			cfg.getStage = cfg.getStage || this.getStage_montage;
			cfg.thumbnailMarkup = '<article class="FigureBox Montage"><figure><img src="{src}" title="{title}" linkTo="{linkTo}" style="height:{height}px;width:{width}px;left:{left}px;top:{top}px;border:{borderSpacing}px solid transparent;"></figure></article>';
			cfg.isMontage = true;	// uses Pr.getThumbPrefix to get min thumb size by crop
			cfg.spacing = 1;		// border spacing
			cfg.arrangement = PAGE.jsonData.montage;
			cfg.listeners = ['LinkToClick', 'MultiSelect', 'Contextmenu'];
						
			// initialize stage and reuse later
			var listener, stage = cfg.getStage(cfg);
			if (!stage.listen) { 
				/*
				 * 
				 */
				stage.listen = {};
				listener = 'MultiSelect';
				stage.listen[listener] = UIHelper.listeners[listener](stage);
				listener = 'LinkToClick';
				stage.listen[listener] = UIHelper.listeners[listener]({node: stage});

				// listener = 'Contextmenu';
				// stage.listen[listener] = UIHelper.listeners[listener]({node: stage});
				listener = 'render';
				stage.listen[listener] = _Y.on('snappi-pm:render', 
						/*
		    			 * @params P Performance
		    			 * @params node div.pageGallery
		    			 */
						function(P, node){
							if (!node.ancestor('.montage-container')) return; 
							stage.removeClass('hide');
							SNAPPI.setPageLoading(false);
						});        
			}
			SNAPPI.setPageLoading(true);
			this.load_then_launch_Montage(cfg);
		},
	}
	UIHelper.util = {
		getGalleryType : function(node) {
			node = node || _Y.one('.gallery-container section.gallery');
			if (node.hasClass('FigureBox')) {
				if (node.hasClass('Group')) return 'group';
				if (node.hasClass('Person')) return 'person';
				if (node.hasClass('Collection')) return 'collection';	
			} else {
				node = node.ancestor('section.gallery', true);
				if (node.hasClass('group')) return 'group';
				if (node.hasClass('person')) return 'person';	
				if (node.hasClass('collection')) return 'collection';	
			}
			return null;
		},
		getContextMenuIdFromNode : function(node, type){
			// copied from SNAPPI.Gallery
			var ID_LOOKUP = {
				// from .FigureBox.Type
				'Group': 'contextmenu-group-markup',
				'Collection': 'contextmenu-collection-markup',
				'Person': 'contextmenu-person-markup',
				// from getGalleryType(), .gallery.type
				'group': 'contextmenu-group-markup',
				'collection': 'contextmenu-collection-markup',
				'person': 'contextmenu-person-markup',
				'photo': 'contextmenu-photoroll-markup',
				'photoPreview': 'contextmenu-photoroll-markup',
			}
			type = type || this.getGalleryType(node);
			// var isPreview = 0&& !e.currentTarget.test('.gallery.'+type+' .FigureBox');
	    	var CSS_ID = ID_LOOKUP[ type ];	
	    	return CSS_ID;		
		},
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
	            			var CSS_ID = UIHelper.util.getContextMenuIdFromNode(e.currentTarget, cfg.type);
	    					var menu = SNAPPI.MenuAUI.find[CSS_ID];
	            			// if contextmenu is visible, hide
		                	if (menu && menu.get('visible')) { // menu may be closed BEFORE this event
		                		UIHelper.nav.toggle_ContextMenu(e);	// hide contextmenu
		                		return;		// allows temp disabling of listener
		                	}
	                		try {	     
	                			// TODO: find CastingCall from Gallery OR Montage       	
			                	if (this.Gallery.castingCall.CastingCall) {
			                    	linkTo += '?ccid=' + this.Gallery.castingCall.CastingCall.ID;
									var shotType = this.Gallery.castingCall.CastingCall.Auditions.ShotType;
									if (shotType == 'Groupshot'){
										linkTo += '&shotType=Groupshot';
									}
								}
							} catch (e) {}
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
        	var selector = 'li.icon.context-menu';
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
         * @params cfg object, cfg.node, cfg.type = [group, photo, person, or Group|Collection|Person], 
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
	                	UIHelper.nav.toggle_ContextMenu(e, cfg);
	                }, selector, UIHelper);
			}
			// back reference
			UIHelper.listen[action] = node.listen[action];
        }, 	        
        MultiSelect : function (node) {
        	node = node || _Y.one('.gallery .container');
        	var container = node;
        	var action = 'MultiSelect';
        	
        	container.listen = container.listen || {};
            if (container.listen[action] == undefined) {
            	SNAPPI.multiSelect.listen(container, true);
			}
			// back reference
			UIHelper.listen[action] = container.listen[action];	        	
        	
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
        /*
         *  display options including filter and sort
         */
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
	                	try {
			    		switch(action[0]) {
			    			case 'filter':
			    				UIHelper.action.filter[action[1]](e);
			    				break;
			    			case 'sort':
			    				break;
			    		}} catch(e) {
			    			console.error("UIHelper.listeners.DisplayOptionClick(): possible error on action name.");
			    		}	                	
	                }, 'ul > li.btn, span.btn.remove', UIHelper);
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
        /*
         * Montage or Gallery
         */
 		SectionOptionClick : function(node) {
        	node = node || _Y.one('nav.section-header');        	
        	if (!node) return;
        	var action = 'SectionOptionClick';
        	node.listen = node.listen || {};
        	var delegate_container = node;
            if (delegate_container && node.listen[action] == undefined) {
            	delegate_container.removeClass('hide');
				node.listen[action] = delegate_container.delegate('click', 
	                function(e){
	                	// action=[section-view:[montage|gallery]
	                	// context = node
                		var action = e.currentTarget.getAttribute('action').split(':');
                		try {
			    		switch(action[0]) {
			    			case 'section-view':
			    				UIHelper.action['section-view'][ action[1] ](e, action[1]);
			    				break;
			    			case 'xxx':
			    				break;
			    		}} catch(e) {
			    			console.error("UIHelper.listeners.SectionOptionClick(): possible error on action name.");
			    		}	
	                }, 'ul > li', node);
				// back reference
				UIHelper.listen[action] = node.listen[action];	   
				
				// TODO: move to UIHelper.init.section{}
				// initialize state
				try {
					if (PAGE.jsonData.montage) {
						// open montage view
						node.one('li.montage').addClass('focus');
						SNAPPI.UIHelper.create._GET_MONTAGE();
						_Y.one('.gallery-container').addClass('hide');
					} else {
						node.one('li.gallery').addClass('focus'); 
console.log('initializing GalleryView');						 
					    new SNAPPI.Gallery({type:SNAPPI.STATE.galleryType});
					    _Y.one('.montage-container').addClass('hide');
					}				
				} catch(e){}
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