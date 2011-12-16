/*
 * SNAPPI util module
 */
(function(){
	var _Y = null;
        
    /*
     * Snappi Global Init
     */
    var _main = function(Y){
// console.log('_main()');    	
        Y = Y || _Y || SNAPPI.Y;
        
        SNAPPI.mergeSessionData();

        SNAPPI.MenuAUI.initMenus();

        /****************************************************************************
         * add additional onload events
         */        
        // rename event to snappi:onXhrLoad
        Y.on('snappi:ajaxLoad', function(){
        	var check;
        });
        
        /*
         * these methods reference audition property
         */
        var once1 = Y.on('snappi:after_GalleryInit', function(){
            /**
             * fired by: /groups/roll, /member/roll initOnce()
             */
            once1.detach();
			SNAPPI.UIHelper.markup.set_ItemHeader_WindowOptions();
			SNAPPI.setPageLoading(false);
        });
        var once2 = Y.on('snappi:after_PhotoGalleryInit', function(){
            /**
             * 	- fired by: GalleryFactory.Photo.build(), GalleryFactory.PhotoAIR,
             */
            once2.detach();
        	var delayed = new Y.DelayedTask( function() {
				SNAPPI.lightbox = new SNAPPI.Lightbox();
			});
			delayed.delay(1000);	
			SNAPPI.UIHelper.markup.set_ItemHeader_WindowOptions();
			SNAPPI.setPageLoading(false); 
        });        
        Y.on('snappi:afterLightboxInit', function(){
            /**
             * 	- fired by: new Lightbox.init(),
             */
        });        
        
        /****************************************************************************
         * init singletons AFTER ALL SCRIPTS HAVE BEEN LOADED
         */
        
        /*
         *  SNAPPI.sortConfig:  config defaults for SNAPPI.sortConfig.byRating, etc.
         */
        SNAPPI.sortConfig.init();	
        
        /*
         * filter bar, filters json set in AppController::beforeRender()
         */
        SNAPPI.filter.renderBar(SNAPPI.STATE.filters);
        
        /*
         * embedded PAGE.init scripts
         */ 
        LazyLoad.helpers.parseDeferredInlineJs(); 
        SNAPPI.xhrFetch.fetchXhr(null, {delay:2000});

		// start document UI listeners
		var listeners = {
			'DragDrop': 1,
		};
		for (var listen in listeners) {
			if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
		}        	
    	
        
        // ready now, or after Gallery init   
        var isXhrGet =  Y.one('#body-container .xhr-get');
        if (!isXhrGet) {
        	SNAPPI.setPageLoading(false);
        }                   
        /**********************************************************
         * optional inits
         * - there should be room to optimize what we init for each page
         */
        // TODO: restore Lightbox is useful only if we are saving to desktop cookie
        // 			right now default is SERVER cookie
        //		SNAPPI.lightbox.restoreLightboxByCookie();	
        
        // Pagemaker default cfg  
        try {
        	SNAPPI.PM.cfg = {
                    fn_DISPLAY_SIZE: function(){
                        return {
                            h: 600
                        };
                    }
                };
        }catch (e){
        	
        }
        
        Y.fire('snappi:afterMain');
        
    };	
    
	
    /*********************************************************************************
     * Globals
     */
	namespace = function(){
        var a = arguments, o = null, i, j, d;
        for (i = 0; i < a.length; i = i + 1) {
            d = a[i].split(".");
            o = window;
            for (j = 0; j < d.length; j = j + 1) {
                o[d[j]] = o[d[j]] || {};
                o = o[d[j]];
            }
        }
        return o;
    };
    // define global namespaces
    namespace('SNAPPI');
    namespace('PAGE');
    
   
    /*
     * init *GLOBAL* SNAPPI as root for namespace
     */
    if (!SNAPPI.id) {
        SNAPPI.id = 'SNAPPI';
		SNAPPI.name = 'Snaphappi';
		SNAPPI.namespace = namespace
    }
    PAGE.init = PAGE.init || [];
    
	/*
	 * protected
	 */
    
    /***********************************************************************************
     * Config - bootstrap config methods
     */
	var Config = function(){};    
	SNAPPI.Config = Config;	// make global
	var _CFG = {		// frequently used startup Config params 
			DEBUG : {	// default when hostname==git*
	    		snappi_comboBase: 'baked/app/webroot&',
	    		air_comboBase: 'app/air&',
	    		snappi_useCombo: 1,					// <-- TESTING SNAPPI useCombo
	    		pagemaker_useCombo: true,
	    		alloy_useCombo: true,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.3.0',	// this is actually set in aui.js
	    		// yui_CDN == true => use "http://yui.yahooapis.com/combo?"
				// yui_CDN == false => use snaphappi hosted yui libs: "/combo/js?"
		    },
	    	PROD : {	// use for unix/server testing
	    		snappi_comboBase: 'app/webroot&',
	    		air_comboBase: 'app/air&',
	    		snappi_useCombo: 1,
	    		pagemaker_useCombo: true,
	    		alloy_useCombo: true,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.3.0',
	    	}
	    }
	namespace('CFG');
	CFG = _CFG;
	
	Config.getYuiConfig = function(force){
		if (SNAPPI.yuiConfig && force !== true) {
			return SNAPPI.yuiConfig;
		}
		
    	
		
		/*
		 * bootstrap YUI, Alloy, and snaphappi javascript
		 * will automatically set to CFG.DEBUG for /git/.test(hostname)
		 */
		var hostCfg = Config.getHostConfig(	{} );
	    var Y;
		var yuiConfig = { // GLOBAL
	    	// AUI will set base for yui load	
	     	// yui3 base for alloy_useCombo=false
	    	// base: "/svc/lib/yui3/",		 
	        timeout: 10000,
	        loadOptional: false,
	        combine: hostCfg.alloy_useCombo,	// yui & alloy combine values will match 
	        allowRollup: true,
	//      filter: "MIN",		// ['MIN','DEBUG','RAW'], default='RAW'        
	//		filter: "DEBUG",
	        filter: hostCfg.alloy_useCombo ? 'MIN' : "RAW",
	        insertBefore: 'css-start',
			groups: {
	    		alloy: Config.addModule_alloy(hostCfg),
	    		snappi: Config.addModule_snappi(hostCfg),
	    		gallery: Config.addModule_gallery(hostCfg),
	    		pagemaker: Config.addModule_pagemaker(hostCfg),
	    		jsLib: Config.addModule_jsLib(hostCfg),
	    		AIR: Config.addModule_AIR(hostCfg),
    			AIR_CSS: Config.addModule_AIR_CSS(hostCfg),
	    	}
	    };
	    // update yuiConfig for yahoo CDN config
	    if (hostCfg.alloy_useCombo && hostCfg.yahoo_CDN == false) {
	    	// use hosted combo services
	    	yuiConfig.comboBase = 'http://' + hostCfg.host + '/combo/js?baseurl=svc/lib/yui_'+hostCfg.YUI_VERSION+'/yui/build&';
	    	yuiConfig.root = '/';
	    }
	    
	    SNAPPI.yuiConfig = yuiConfig;		// make global
	    return yuiConfig;
	};
   /**
    * LazyLoad Static Class
    */
	var LazyLoad = function(){}
	SNAPPI.LazyLoad = LazyLoad;
	/*	
	 * Helper Functions for managing async state of Y.use()
	*/	
	LazyLoad.helpers = {
		before_LazyLoad : function(){
			// BEFORE Y instance, Y.node is available
			// initialize wait
			document.body.className += ' wait';
		},
		before_LazyLoadCallback : function(Y, result){
			// SNAPPI.Y = Y;	// update global with new modules
			LazyLoad.helpers.add_ynode(Y);
			// defer inits until Y instance available
			if (SNAPPI.onYready) {
				for (var f in SNAPPI.onYready) {
					try {
						SNAPPI.onYready[f](Y);
						delete 	SNAPPI.onYready[f];
					} catch (e){}
				}
			}			
			SNAPPI.setPageLoading = LazyLoad.helpers.setPageLoading;
		},
		after_LazyLoadCallback : function(Y){
			LazyLoad.helpers.setPageLoading(false);
		},		
		setPageLoading : function (value) {
        	try {
	        	if (value == undefined) return _Y.one('body').hasClass('wait');
	        	if (value) _Y.one('body').addClass('wait');
	        	else {
	        		_Y.one('body').removeClass('wait');
	        		_Y.one('#related-content').removeClass('hide');
	        	}
        	} catch (e) {}
        	return value ? true : false;
        },		
		add_ynode : function(Y) {
			Y = Y || _Y;
	        Y.Node.prototype.dom = function(){
	            return Y.Node.getDOMNode(this);
	        };
	        Y.Node.prototype.ynode = function(){
	            return this;
	        };
	        try {	// ie8 incompatibility
		        HTMLElement.prototype.dom = function(){
		            return this;
		        };
		        HTMLElement.prototype.ynode = function(){
		            return Y.one(this);
		        };
	        } catch(e) {}	
		},
		// execute inline/deferred js scripts 
		parseDeferredInlineJs : function(){        // execute embedded PAGE.init scripts
			try {
			    while (PAGE.init.length) {
			        var init = PAGE.init.shift();
			        try {
			        	init();	
			        } catch (e) {}
			    }
			} catch (e) {}
	    },   
	}
	/**
	 * wrapper for AUI/Y.use(), calls for a loadModule config
	 * @params cfg.before, cfg.after methods for onlazyload
	 */
	LazyLoad.use = function(modules, onlazyload, cfg) {
		// before/after calling onlazyload callback
		cfg = cfg || {};
		before = cfg.before || LazyLoad.helpers.before_LazyLoadCallback;
		after = cfg.after || LazyLoad.helpers.after_LazyLoadCallback;
		var wrappedCallback = function(Y, result){
			if (!result.success) {
				
				Y.log('Load failure: ' + result.msg, 'warn', 'Example')
				
			}
			if (before) before(Y, result);
			onlazyload(Y, result);
			if (after) after(Y, result);
		}
		modules.push(wrappedCallback);
		
		if (_Y===null) {
			_Y = AUI(Config.getYuiConfig());
			SNAPPI.Y = _Y;
		} 
		LazyLoad.helpers.before_LazyLoad();
		
		// begin loading modules
		_Y.use.apply(_Y, modules);
	}
	// supports WindowOptionClick, primary header menu, xhr init	
	LazyLoad.min = function(cfg){
		cfg = cfg || {};	// closure for onlazyload
		
		var modules = ['snappi-ui-helpers', 'snappi-io', 'snappi-menu-aui'];
		var onlazyload = function(Y, result){
				
				// update session data
				SNAPPI.mergeSessionData();
				
				// init menus, default is 'menu-header-markup'
				SNAPPI.MenuAUI.initMenus();
				
				// start listeners
				var listeners = {
					'WindowOptionClick':null,
					'ContentMenuClick': false, 
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}
		}
		LazyLoad.use(modules, onlazyload, cfg);
	}; 
	// adds support for SNAPPI.xhrFetch
	LazyLoad.xhr= function() {
		cfg = cfg || {};	// closure for onlazyload
		// supports WindowOptionClick, primary header menu, xhr init
		var modules = ['snappi-ui-helpers','snappi-io', 'snappi-menu-aui'];
		var onlazyload = function(Y, result){

				// update session data
				SNAPPI.mergeSessionData();
				
				// init menus, default is 'menu-header-markup'
				SNAPPI.MenuAUI.initMenus();
				
				// start listeners
				var listeners = {
					'WindowOptionClick':null,
					'ContentMenuClick': false, 
				};
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}
				
				if (cfg.fetchXhr) {
					/*
			         * embedded PAGE.init scripts, if any
			         */ 
			       LazyLoad.helpers.parseDeferredInlineJs();
			        SNAPPI.xhrFetch.fetchXhr(null, {delay:2000});						
				}
		}
		LazyLoad.use(modules, onlazyload, cfg);
	};
	LazyLoad.gallery = function(cfg){
		cfg = cfg || {};	// closure for onlazyload
		// supports WindowOptionClick, primary header menu, xhr init
		var modules_1 = [
			// load first to minimize CSS flash, required for menu-aui/dialog-aui CSS
			// 'aui-skin-classic-all', 	// LOAD IN MARKUP
			/*
		     * required by base
		     */
		    'node', 'event', 'event-custom', 
		    
		    /*
		     * add custom/synthetic hover event
		     */
		    "event-synthetic", "event-mouseenter", 
		    
			/*
		     * 'async-queue': required by snappi-imageloader, singleton init()
		     */
		    'async-queue',
		];
		var modules_2 = [
			/*
			 * aui modules
			 */
		    'aui-delayed-task', 'aui-io', 'aui-loading-mask', 	        	
			/*
			 * primary scripts
			 */
			'snappi-event-hover',
			'snappi-dragdrop', 
			'snappi-sortedhash',
			'snappi-group',
			'snappi-thumbnail-helpers', 
			'snappi-imageloader',
			'gallery-util',
			'snappi-thumbnail',
			'snappi-io',
			'snappi-menu-aui',
			'snappi-paginator',
			'snappi-gallery-helpers',
			'snappi-io-helpers',
			'snappi-ui-helpers',
			'snappi-util',
			'snappi-gallery', 
			'snappi-lightbox',
			'snappi-filter', 'snappi-tabs',
			 
			/*
			 * util scripts
			 */
			
			// deprecated
			// 'snappi-property', 'snappi-menucfg', 'snappi-toolbutton', 'snappi-menu', 'snappi-menuitem', 'snappi-dialogboxCfg','snappi-dialogbox', 'snappi-zoom',
			// UNUSED
			// 'aui-resize', 
		];
		modules_3 = [
			'snappi-dialog-aui',
			/*
			 * pagemaker
			 */
			// 'pagemaker-base',
		]
		
		// var onlazyload_1 = function(Y, result){
			// Y.on("domready", function() {
		    	// SNAPPI.domready = true;
		    // });
		    // YAHOO = SNAPPI.Y.YUI2; // YUI2 deprecate when possible
		    // LazyLoad.use(modules_2, onlazyload_2, {before: null});
		// }
		var onlazyload = function(Y, result){
			/*
			 * all script files loaded, begin init
			 */
			Y.on("domready", function() {
		    	_main(Y);
		    });
			// if (SNAPPI.domready) {
				// _main();		// domready event already called
			// } else {
// console.warn('Y.ready() BEFORE domready');					
				// var detach = Y.on("domready", function() {
					// detach.detach();
			    	// _main();
			    // });
			// }
			// LazyLoad.use(modules_3, onlazyload_3, {before: null});
		}
		var onlazyload_3 = function(Y, result){
			// init for dialog, pagemakerPlugin
		}
		var modules = modules_1.concat(modules_2, modules_3);
		LazyLoad.use( modules, onlazyload, null );
	}
	LazyLoad.AIRDesktopUploader = function(cfg){
		cfg = cfg || {};	// closure for onlazyload
		
		var modules = [
			// css
    		'AIR-upload-ui-css',
    		/*
    		 * required
    		 */
    		'node', 'event', 'event-delegate', 'event-custom', "event-mouseenter",
    		'node-event-simulate',
    		/*
    		 * early load modules
    		 */
    		// 'AIR-firebug-stable',
    		'AIR-firebug-1.2',
    		/*
    		 * snappi modules
    		 */
    		'snappi-sortedhash','snappi-io', 'snappi-io-helpers', 
    		'snappi-paginator', 'snappi-menu-aui', 
    		// 'snappi-dialog-aui', 'snappi-gallery-helpers', 
    		/*
    		 * air modules - bootstrap only. add additional modules after init 
    		 */
        	// 'AIR-menu-aui',
    		'AIR-api-bridge',  'AIR-upload-ui', 
    		/*
    		 * save for 2nd load
    		 */
    		'AIR-upload-manager', 'AIR-file-progress',
    		/*
    		 * unused
    		 */
    		'AIR-js-datasource',
//    		'AIR-test-api',		
		];
		var onlazyload = function(Y, result){
				SNAPPI.Y = Y;
    			
    			LOG(" *********** base.js:  SNAPPI.Y = " + SNAPPI.Y.version);
    			LOG(SNAPPI.AIR);
    			try {
    				SNAPPI.AIR.Helpers.add_snappiHoverEvent(Y);
    			} catch (e) {}
    			
    			/*********************************************************************************
    			 * domready init
    			 */
    			Y.on('domready', function(){
    				SNAPPI.AIR.Helpers.go();
    			});
		}
		
		// before before_LazyLoad
		var before_LazyLoad = function(){
			ALLOY_VERSION='alloy-1.0.2';
			namespace('SNAPPI.AIR');
			SNAPPI.AIR.Config = Config;	// make global
			SNAPPI.AIR.Config.CONSTANTS = {
				uploader: {
					perpage: 48,
					end: null
				},
				datasource: {
					perpage: 999,
					uploadHostLookup: "/air/flex-uploader/lib/_php/getUploadHost.php",
		    		updateServer: '/snappi/debugPost',	// post url
		    		syncServer: '/air/flex-uploader/lib/_php/syncstatus.php',
		    		setSyncAndSetDataUrl: '/air/flex-uploader/lib/_php/set_syncstatus.php',				
					end: null
				},			
			}
			
			/*
			 * Set host from <BASE> tag, which is first set in snaphappi.mxml:bootstrap_html() 
			 */
			try {
				SNAPPI.id = "SNAPPI Desktop Uploader";
				// host attribute set in flex on init
				var base = document.getElementsByTagName('base')[0];
				SNAPPI.AIR.host = base.getAttribute('host');
				SNAPPI.AIR.debug = base.getAttribute('debug')=='true';
				SNAPPI.isAIR = true;	// deprecate
			} catch (e) {
				alert("baseurl is not set");
				SNAPPI.AIR.host = 'git3:88';
			}	
			/*
			 * helper functions, debug for firebug lite
			 */
			// GLOBAL
			var serialize = function(o) {
				var serialized = [];
				for ( var p in o) {
					serialized.push(p + ': ' + o[p]);
				}
				return serialized;
			};
			LOG = function(msg) {
		    	try {
		    		console.log(msg);
		    	} catch (e) {
		    		try {
			        	msg = serialize(msg);
			        	console.log(msg);
		    		} catch(e) {}
		    	}
			}
		    firebugLog = function (msg) {
		    	if (   Object.prototype.toString.call(msg) == '[object Array]'
		    		|| Object.prototype.toString.call(msg) == '[object ScriptBridgingArrayProxyObject]'
		    	) {
		    		console.log('FLEX> '+msg);
		    		console.log(serialize(msg));
		//    	} else if (SNAPPI.coreutil.isObject(msg)) {
		//    		 LOG('FLEX> '+msg);
		//    		 LOG(msg);
		    	} else LOG('FLEX> '+msg);
		    };
		    LazyLoad.helpers.before_LazyLoad();	
		    
		    
		    CFG.DEBUG.snappi_useCombo = 0;
 
		}
		
		
		before_LazyLoad();	
		LazyLoad.use(modules, onlazyload, cfg);
	};
	


	// static methods
	/**
	 * getHost 
	 * - infers correct host config depending on startup mode [localhost|server|AIR]
	 * - runs BEFORE Y.merge is available	
	 */
	Config.getHostConfig = function(cfg) {
		cfg = cfg || {};
	    var defaultCfg, o = {};		
	    try {
	        // get host from AIR bootstrap
	        var host = SNAPPI.isAIR ? SNAPPI.AIR.host : window.location.host;
	    } catch (e) {
	        host = window.location.host;	// hostname:port number
	    }
        //                console.log("host=" + host);
	    o.host = host;
	    o.isLocalhost = /git/.test(host); // live vs dev site	
	    	
	    if (o.isLocalhost) defaultCfg = CFG.DEBUG;
	    else defaultCfg = CFG.PROD;
	    
	    // merge defaultCfg + overrides
	    for (var prop in defaultCfg) {
	    	o[prop] = defaultCfg[prop];
	    }
	    for (prop in cfg) {
	    	o[prop] = cfg[prop];
	    }
		return o;
	};    
	/**
	 * config loading for AlloyUI module, 
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_alloy = function(hostCfg) {
	    /*
	     * use COMBO LOADING for alloy js lib
	     * 	NOTES:
	     * 	- requires javascript bootstrap: 'alloy-1.0.1/build/aui/aui.js'
	     * 	- use AUI, not YUI 
	     * 	- set filter in yuiConfig.yui 
	     */
		var yuiConfig_alloy;
		if (hostCfg.alloy_useCombo == true) {
			yuiConfig_alloy = {
	            combine: true,
	            base: 'http://' + hostCfg.host + '/svc/lib/'+ALLOY_VERSION+'/build/',
	            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl=svc/lib/'+ALLOY_VERSION+'/build&',
	            root: '/',		// base for combo loading, combo load uri = comboBase+root+[module-name]
	//	        filter: "MIN",    // filter ['MIN'|'DEBUG'|'RAW'], set in yuiConfig.yui    
	            filter:'MIN',            
	            modules: AUI.AUI_config.groups.alloy.modules,
	            name: 'alloy'
		    };
		} else {
			// default is combine==false, filter='RAW'
			yuiConfig_alloy = AUI.AUI_config.groups.alloy;
		}
	    return yuiConfig_alloy;
	}; 	
	/**
	 * snappi javascript module
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_snappi = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();
	    var yuiConfig_snappi = {
            combine: hostCfg.snappi_useCombo,
            base: 'http://' + hostCfg.host + '/js/snappi/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
            root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
	    		'snappi-event-hover': {
	    			path: 'event_hover.js',
	    			requires:['event',"event-synthetic"]
	    		},
                'snappi-group': {
                    path: 'groups3.js',
                    requires: ['node', 'snappi-sortedhash', 'snappi-dragdrop']
                },
                // 'snappi-datasource': {
                    // path: 'datasource3.js',
                    // requires: ['node', 'async-queue', 'io', 'datatype-xml']
                // },
                'snappi-auditions': {
                    path: 'auditions.js',
                    requires: ['node', 'gallery-util', 'snappi-group', 'snappi-sortedhash']
                },	    		
        		'snappi-toolbutton': {
        			path:'toolbuttons.js',
        			requires:['node']
        		},
        		'snappi-property' : {
        			path : 'property.js',
        			requires : ['node', 'base']
        		},
        		'snappi-dialog-aui': {
        			path: 'dialog_aui.js',
        			requires:['node', 'aui-skin-classic-all', 'aui-aria', 'aui-dialog', 'aui-overlay-manager', 'dd-constrain']
        		},
        		'snappi-menu-aui': {
        			path: 'menu_aui.js',
        			// BUG: requires A.Plugin.IO, found in "aui-io", but not available
        			requires:['event-mouseenter', 'aui-io', 'aui-aria', 'aui-overlay-context', 'aui-overlay-manager']
        		},        		
        		'snappi-paginator': {
        			path: 'paginator_aui.js',
        			requires:['aui-io', 'aui-paginator']
        		},              		
        		'XXXsnappi-dialogbox': {
        			path: 'dialogbox.js',
        			requires:['node']
        		},        		
        		'XXXsnappi-dialogboxCfg': {
           			path: 'dialogboxcfg.js',	
           			requires:['node', 'snappi-dialogbox']
           		},
        		'xxxsnappi-menu': {
        			path: 'menu.js',
        			requires:['node']
        		},
        		'xxxsnappi-menuitem': {
        			path: 'menuitem.js',
        			requires:['node', 'substitute']
        		},
        		'XXXsnappi-menucfg': {
        			path: 'menucfg.js',
        			requires:['node', 'node-event-simulate', 'snappi-menu', 'snappi-menuitem']
        		},
        		'snappi-zoom': {
    				path: 'zoom.js',
    				requires:['node']
    			},
                'snappi-tabs': {
                    path: 'tabs.js',
                    requires: ['node']
                },
                'snappi-sort': {
                    path: 'sort.js',
                    requires: ['node']
                },
                'snappi-sortedhash': {
                    path: 'sortedhash.js',
                    requires: ['node', 'snappi-sort']
                },
                'snappi-dragdrop': {
                    path: 'dragdrop3.js',
                    requires: ['node', 'event', 'dd', 'dd-delegate', 'dd-plugin', 'dd-drop-plugin', 'anim']
                },
                'snappi-imageloader': {
                    path: 'imageloader.js',
                    requires: ['node', 'async-queue', 'event'] // also references SNAPPI.util.LoadingPanel
                },	                
                'snappi-rating': {
                    path: 'rating.js',
                    requires: ['node', 'event', 'stylesheet', 'snappi-thumbnail', 'aui-loading-mask']
                },
                'snappi-thumbnail': {
                    path: 'thumbnail3.js',
                    requires: ['node', 'substitute', 'stylesheet', 'event', 'overlay', 'gallery-util', 'snappi-rating', 'snappi-group', 'snappi-dragdrop', 'snappi-thumbnail-helpers', 'snappi-imageloader']
                    //'gallery-util' SNAPPI.util.hash(bindTo) may be deprecated 
                },
                'snappi-thumbnail-helpers': {
                    path: 'thumbnail-helpers.js',
                    requires: []
                },
                'snappi-gallery': {
                    path: 'gallery.js',
                    requires: ['node', 'event', 'event-key', 'snappi-event-hover', 
                    'snappi-util', // uses SNAPPI.ShotController
                    'snappi-auditions',
                    'snappi-rating', 'snappi-menu-aui', 'snappi-paginator', 'snappi-gallery-helpers', 'snappi-thumbnail-helpers',
                    // 'snappi-dialog-aui', 
                    ] // snappi-util -> SNAPPI.shotController(move)
                },                                       
                'snappi-gallery-helpers': {
                    path: 'gallery-helpers.js',
                    requires: []
                },
                'XXXsnappi-domJsBinder': {
                    path: 'domJsBinder.js',
                    requires: ['node', 'event-custom', 'io', 'snappi-auditions', 'snappi-sort', 'snappi-gallery']
                },
                'snappi-lightbox': {
                    path: 'lightbox.js',
                    requires: ['node', 'substitute', 'event', 'io', 'dd', 'dd-plugin', 'snappi-util', 'snappi-sortedhash', 'snappi-gallery', 'snappi-dragdrop',  'snappi-rating', 
                    // 'snappi-domJsBinder',
                               /*
                                * experimental
                                */
//	                               'yui2-resize'
                               // 'resize' // yui3 resize
                               ]
                    // snappi-util -> SNAPPI.shotController, SNAPPI.ratingManager, SNAPPI.io (move)
                },
                'snappi-util': {
                    path: 'util.js',
                    requires: ['node', 'event-custom', 'io', 'substitute',
                               'snappi-rating', 
                               // 'snappi-lightbox'
                               ]
                },
                'snappi-io': {
                    path: 'io.js',
                    requires: ['node', 'io', 'json', 'aui-io', 'aui-loading-mask']
                },  
                'snappi-io-helpers': {
                    path: 'io_helpers.js',
                    requires: ['async-queue', 'node', 'substitute', 'snappi-io']
                },           
                'snappi-ui-helpers': {
                    path: 'ui-helpers.js',
                    requires: ['node', 'snappi-io'],
                },                  
                'snappi-filter': {
                    path: 'filter.js',
                    requires: ['node', 'snappi-rating']
                }
            }
        };
	    return yuiConfig_snappi;
	};
	/**
	 * legacy module, move to snappi
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_gallery = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_gallery = {
	            combine: hostCfg.snappi_useCombo,
	            base: 'http://' + hostCfg.host + '/js/gallery/',
	            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
	            root: 'js/gallery/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
	            modules: {
	                'gallery-util': {
	                    path: 'util.js',
	                    requires: ['node']
	                },
	                // 'gallery-data-element': {
	                    // path: 'dataelement.js',
	                    // requires: ['node']
	                // },
	            }
	        };
	     		
	    return yuiConfig_gallery;
	};   
	/**
	 * Pagemaker module, bootstrap file for Pagemaker module 
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_pagemaker = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_pagemaker = {
            combine: hostCfg.pagemaker_useCombo,
            base: 'http://' + hostCfg.host + '/app/pagemaker/js/create/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl=PAGEMAKER&',
            root: 'js/create/',
            modules: {
                'pagemaker-base': {
                    path: 'base.js',
                    requires: ['node', 'io', 'fleegix_xml']
                }
            }
        };
	    return yuiConfig_pagemaker;
	}; 	
	
	/**
	 * utility and external modules, 
	 * @param hostCfg
	 * @return
	 */
	Config.addModule_jsLib = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_jsLib = {
            combine: 0, // hostCfg.snappi_useCombo,
            base: 'http://' + hostCfg.host + '/js/lib/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
            root: 'js/lib/',
            modules: {
                'fleegix_xml': {
                    path: 'xml.js'
    		    },	
    		    'datejs': {
    		    	path: 'datejs.js'
    	    	},
    	        'coreutil': {
    	        	path: 'coreutil.js'
                },
    	        'snappi-debug': {
    	        	path: 'debug.js'
                }
            }
        };
	    return yuiConfig_jsLib;
	}; 	
	
	/**
	 * snappi AIR uploader javascript module
	 * @param hostCfg
	 * @return
	 */	
	Config.addModule_AIR = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_AIR = {
	    	// combine: hostCfg.snappi_useCombo,
	        combine: false,		
	        base: '/app/air/js/',
	//        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
			comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.air_comboBase,
	        root: 'js/',			// base for combo loading, combo load uri = comboBase+root+[module-name]
	        modules: {
	            'AIR-ui-helpers': {
	                path: 'ui-helpers.js',
	                requires: ['snappi-event-hover', 'snappi-paginator'] 
			    },		        	
	            'AIR-helpers': {	//TODO: deprecate. move to ui-helpers.js
	                path: 'helpers.js',
	                requires: ['AIR-ui-helpers'],
			    },			    
	            // 'AIR-init': {
	                // path: 'init.js',
	                // requires: ['AIR-helpers', 'AIR-api-bridge', 'AIR-file-progress', 'AIR-upload-manager', 'AIR-upload-ui'] 
			    // },		    
	            'AIR-file-progress': {
	                path: 'fileprogress.js',
	                requires: ['node','snappi-thumbnail-helpers', 'AIR-helpers']                 
			    },    	
		        'AIR-api-bridge': {
			        path: 'api_bridge.js',
			        requires: ['AIR-helpers', 'coreutil']
			    },
	            'AIR-test-api': {
	                path: 'testapi.js',
	                requires: ['AIR-api-bridge']                           
		        },    	
	            'AIR-upload-manager': {		// wrapper for SNAPPI.DATASOURCE/'AIR-api-bridge'
	                path: 'upload_manager.js',
	                requires: ['snappi-sortedhash', 'coreutil', 'AIR-api-bridge', 'AIR-file-progress']
			    },		    
	            'AIR-upload-ui': {
	                path: 'upload_ui.js',
	                // 'AIR-snappi-css' loaded in HTML head
	                requires: ['AIR-upload-ui-css', 'coreutil', 'AIR-api-bridge', 'AIR-file-progress', 'AIR-ui-helpers']
			    },
		        'AIR-firebug-stable': {	// not supported in AIR/webkit browser
		            path: 'https://getfirebug.com/firebug-lite.js#startOpened',
		        },  			    
		        'AIR-firebug-1.3': {	// not supported in AIR/webkit browser
		            path: 'debug/firebug-lite.1.3.2.js#startOpened',
		        },    	
		        'AIR-firebug-1.2': {
		            path: 'debug/firebug-lite-compressed.1.2.3.1.js',	            
		        },
	    		// 'AIR-menuCfg': {
	    			// path: 'menucfg.js',
	    			// requires:['snappi-menu', 'snappi-menuitem', 'node-event-simulate']
	    		// },	        
	            'AIR-js-datasource': {
	                path: 'jsDatasource.js',
	                requires: []                           
		        },  	        
        		// 'AIR-menu-aui': {
        			// path: 'AIR_menu_aui.js',
        			// // BUG: requires A.Plugin.IO, found in "aui-io", but not available
        			// requires:['aui-io', 'aui-aria', 'aui-overlay-context', 'aui-overlay-manager']
        		// }, 		        
        		// 'AIR-dialog-aui': {
	    			// path: 'dialog_aui.js',
	    			// requires:['node', 'aui-aria', 'aui-dialog', 'aui-overlay-manager', 'dd-constrain']
	    		// },	        
	        }
	    };  
	    return yuiConfig_AIR; 
	}

	/**
	 * snappi AIR CSS module
	 * @param hostCfg
	 * @return
	 */ 
    /*
     *  NOTE: getting security violation if I try to load CSS from "/app/air/js/css" 
     *  load CSS from mx:HTML location file
     */    
    Config.addModule_AIR_CSS = function(hostCfg) {
    	hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_AIR_CSS = {
            combine: false,
            // base: '/app/air/js/css/',
            base: 'http://' + hostCfg.host + '/app/air/js/css/',
			comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.air_comboBase,
	        root: 'js/css',			// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
            	'960-reset-css': { 	// load manually in HTML HEAD
            		path: 'reset.css',
            		requires: [],
            		type: 'css'
            	},
            	'AIR-upload-ui-css': {
    		        path: 'upload_ui.css',
    		        // requires: ['snappi-cake-css','old-snappi-css','snappi-menu-css'],
    		        requires: [],
    		        type: 'css'
    		    },
    			'AIR-snappi-css': {		// load manually in HTML HEAD
    		        path: 'AIR_snappi.css',
    		        requires: ['960-reset-css', 'AIR-upload-ui-css'],		
    		        type: 'css'
    			},       		     	        
            }
        };    	
		return yuiConfig_AIR_CSS; 
	};	
	/**********************************************************************************
	 * end static Class Configure
	 **********************************************************************************/
	
	
	
	

    
    
    
    
    
    
    
    
    
    /*************************************************************************************
     * 	MODULE Startup/Init
     */

   	/*
	 * merge session/state data
	 * 	use PAGE.jsonData.STATE (global) to pass default session/state data via HTTP GET/XHR
	 *  use SNAPPI.STATE (global) to store local session/state across XHR gets
	 */    
    SNAPPI.mergeSessionData = function(castingCall){
        try {
            SNAPPI.STATE = SNAPPI.STATE  || {}; // stores session data across XHR calls
        	SNAPPI.STATE = _Y.merge(PAGE.jsonData.STATE, SNAPPI.STATE);
        	PAGE.jsonData.STATE = {};
    		// merge PAGE into SNAPPI.STATE
    		try {
    			castingCall = castingCall || PAGE.jsonData.castingCall;
	    		var ccPage = castingCall.CastingCall.Auditions;
	    		SNAPPI.STATE.displayPage = {
	    				page: ccPage.Page,
	    				perpage: ccPage.Perpage,    				
	    				pageCount: ccPage.Pages,
	    				total: ccPage.Total
	    		};
    		} catch (e) {}
        } catch (e) { }
        try {
        	SNAPPI.STATE.filters = SNAPPI.STATE.filters || [];
        	SNAPPI.STATE.filters = PAGE.jsonData.filter; // this is an array
        } catch(e) {}
        	
        if (SNAPPI.STATE.controller === undefined) SNAPPI.STATE.controller = PAGE.jsonData.controller;
        
        
        
      
        return SNAPPI.STATE;
    };
    
    
    // LazyLoad.gallery();  // move to snappi and snappi-wide layout
    
    
})();
