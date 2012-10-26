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
        
        _Y.on('click', function(e){
// console.log("global any-click");    
			SNAPPI.last_action_ms = new Date().getTime();			
		}, document);
		_Y.on('contextmenu', function(e){
// console.log("global any-click");    
			SNAPPI.last_action_ms = new Date().getTime();			
		}, document);
		
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
			'WindowOptionClick': null,
			'DragDrop': 1,
		};
		SNAPPI.startListeners(listeners);
        
        // ready now, or after Gallery init   
        var isXhrGet =  Y.one('#body-container .xhr-get');
        if (!isXhrGet) {
        	SNAPPI.setPageLoading(false);
        }         
        
        var delayed = new Y.DelayedTask( function() {
        	var load_module;
        	switch(SNAPPI.STATE.controller.name) {
        		case 'Workorders': 
        			load_module = 'workorder'; break;
        		default: 
        			load_module = 'gallery';
        	}
			SNAPPI.LazyLoad.extras({
	        	module_group: load_module,
	        }); 
		});
		delayed.delay(3000);	
                 
        // /**********************************************************
         // * optional inits
         // * - there should be room to optimize what we init for each page
         // */
        // // TODO: restore Lightbox is useful only if we are saving to desktop cookie
        // // 			right now default is SERVER cookie
        // //		SNAPPI.lightbox.restoreLightboxByCookie();	
//         
        // // Pagemaker default cfg  
        try {
        	SNAPPI.PM.cfg = {
                    fn_DISPLAY_SIZE: function(){
console.error('DEPRECATE: SNAPPI.PM.cfg.fn_DISPLAY_SIZE() in main()');                    	
                        return {
                            h: 600
                        };
                    }
                };
        }catch (e){}
        
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
	Config._name = 'snappi';  
	Config.staticHost = {			// also defined in /pagemaker/js/create/base_aui.js
		subdomain: 'snappi',		// subdomain prefix for static host
		server_count: 2, 			// count of subdomains on this prefix
		FORCE_HOST: false,			// if string, then set host to string value
	};
	
	/*
	 * force static host to snappi-cn for these conditions, usually 10.1.2.207
	 */
	if (window.location.host.match( /snappi-cn/i )) Config.staticHost.FORCE_HOST = window.location.host;
	if (window.location.host.match( /aws\.snaphappi\.com/i )) Config.staticHost.FORCE_HOST = '10.1.2.203';
	/*
	 * end force static host
	 */
	
	SNAPPI.Config = Config;	// make global
	var _CFG = {		// frequently used startup Config params 
			DEBUG : {	// default when hostname==git* or snappi-dev
	    		snappi_comboBase: 'app/webroot&',
	    		snappi_minify: 0,
	    		air_comboBase: 'app/air&',
	    		snappi_useCombo: 1,					// <-- TESTING SNAPPI useCombo
	    		pagemaker_comboBase: 'PAGEMAKER&',	// filepath, not baseurl
	    		pagemaker_useCombo: 0,
	    		alloy_useCombo: 1,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.4.0',	// this is actually determined by aui version, aui.js
	    		// yui_CDN == true => use "http://yui.yahooapis.com/combo?"
				// yui_CDN == false => use snaphappi hosted yui libs: "/combo/js?"
		    },
	    	PROD : {	// use for unix/server testing
	    		snappi_comboBase: 'app/webroot&',
	    		snappi_minify: 0,	// auto minify for preview.snaphappi.com
	    		air_comboBase: 'app/air&',
	    		snappi_useCombo: 1,
	    		pagemaker_comboBase: 'PAGEMAKER&',	// filepath, not baseurl
	    		pagemaker_useCombo: 1,
	    		alloy_useCombo: 1,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.4.0',
	    	}
	    }
	namespace('CFG');	// make global
	CFG = _CFG;
	
	/**
	 * @params hashkey mixed, use this value to determine staticHost index
	 * @return host, string, same form as window.location.host, i.e. hostname:port
	 */
	Config.getStaticHost = function(hashkey){
		if (Config.staticHost.FORCE_HOST) return Config.staticHost.FORCE_HOST;
		var match, needle, subdomain, i;
		host = window.location.host;
		try {
			i = hashkey % Config.staticHost.server_count;
			if (isNaN(i)) { // scan for stageN
				match = hashkey.match(/.*\/stage(\d+)\/.*/);	
				hashkey = (match && match.length==2) ? parseInt(match[1]) : 0;
				i = hashkey % Config.staticHost.server_count;		
			}
		}catch(e){}
		subdomain = i ? Config.staticHost.subdomain+i : Config.staticHost.subdomain;
		match = host.match(/(.*)\.snaphappi\.com/);
		needle = (match && match.length==2) ? match[1] : window.location.hostname;
		host = host.replace(needle, subdomain);
		return host;
	}
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
	    	yuiConfig.comboBase = 'http://' + Config.getStaticHost(0) + '/combo/js?baseurl=svc/lib/yui_'+hostCfg.YUI_VERSION+'/yui/build&';
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
			var className = document.body.className;
			if (!/wait/.test(className)) document.body.className += ' wait';
		},
		before_LazyLoadCallback : function(Y, result){
			// SNAPPI.Y = Y;	// update global with new modules
			LazyLoad.helpers.add_ynode(Y);
			// defer inits until Y instance available
			if (SNAPPI.onYready) {
// console.log("before_LazyLoadCallback()  SNAPPI.onYready");				
// console.log(SNAPPI.onYready);				
				for (var f in SNAPPI.onYready) {
					try {
						var fn = SNAPPI.onYready[f];
						delete 	SNAPPI.onYready[f];
						fn(Y);
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
        	} catch (e) {
        		var check;
        	}
        	return value ? true : false;
        },		
		add_ynode : function(Y) {
console.warn("Node.ynode() may not be compatible with ie8");			
			Y = Y || _Y;
			if (Y.Node.prototype.ynode) return;
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
		var before = cfg.before || LazyLoad.helpers.before_LazyLoadCallback;
		var after = cfg.after || LazyLoad.helpers.after_LazyLoadCallback;
		var wrappedCallback = function(Y, result){	// SNAPPI
			if (!result.success) {
				
				Y.log('Load failure: ' + result.msg, 'warn', 'Example')
				
			} else SNAPPI.Y = _Y = Y;
			if (before) before(Y, result);
			try {
				onlazyload(Y, result);
			} catch(e){
				console.error('ERROR: exception in LazyLoad.onlazyLoad();')
			}
			if (after) after(Y, result);
			_Y.fire('snappi:lazyload-complete');
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
		
		var modules = ['snappi-ui-helpers', 'snappi-io', 'snappi-menu-aui', 'cookie'];
		var onlazyload = function(Y, result){
				// update session data
				SNAPPI.mergeSessionData();
				
				// init menus, default is 'menu-header-markup'
				SNAPPI.MenuAUI.initMenus();
				
				// start listeners
				var listeners = {
					// 'WindowOptionClick':null,
					'ContextMenuClick': false, 
				};
				if (PAGE.jsonData && PAGE.jsonData.listeners) {
					listeners = Y.merge(listeners, PAGE.jsonData.listeners);
					delete PAGE.jsonData.listeners;
				}
				for (var listen in listeners) {
					if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
				}
				SNAPPI.setPageLoading(false);
		}
		LazyLoad.use(modules, onlazyload, cfg);
	}; 
	/**
	 * load extra modules AFTER initial page rendering
	 * @params cfg.module_group = string, set of modules to load by key
	 */			
	LazyLoad.extras = function(cfg){	// load on _Y.later() after initial startup
		var module_group = {
			'gallery': ['snappi-dialog-aui', 'snappi-hint', 'pagemaker-base'],
			'workorder': ['snappi-dialog-aui', 'pagemaker-base'],
			'hint':['snappi-hint'],
			'preview': ['snappi-dialog-aui', 'snappi-auditions', 'snappi-hint'],
			'alert': ['snappi-dialog-aui'],
			'pagemaker-plugin': ['pagemaker-base','snappi-dialog-aui'],
		}
		var modules = module_group[cfg.module_group];
		if (modules) {
			onlazyload = cfg.ready || function(){
				return true;
			};	// closure for onlazyload
			delete cfg.ready;
			LazyLoad.use(modules, onlazyload, cfg);
		}
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
					'ContextMenuClick': false, 
				};
				if (PAGE.jsonData && PAGE.jsonData.listeners) {
					listeners = Y.merge(listeners, PAGE.jsonData.listeners);
					delete PAGE.jsonData.listeners;
				}
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
		    'transition',
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
			'snappi-dragdrop', 
			'snappi-sortedhash',
			'snappi-group',
			'snappi-thumbnail-helpers', 
			'snappi-imageloader',
			'snappi-util-misc',
			'snappi-thumbnail',
			'snappi-io',
			'snappi-menu-aui',
			'snappi-paginator',
			'snappi-gallery-helpers',
			'snappi-io-helpers',
			'snappi-ui-helpers',
			'snappi-util-post',
			'snappi-gallery', 
			'snappi-lightbox',
			'snappi-filter', 
			'snappi-tabs',
			 
			/*
			 * util scripts
			 */
			
			// deprecated
			// 'snappi-property', 'snappi-menucfg', 'snappi-toolbutton', 'snappi-menu', 'snappi-menuitem', 'snappi-dialogboxCfg','snappi-dialogbox', 
			// UNUSED
			// 'aui-resize', 
		];
		var onlazyload = function(Y, result){
			/*
			 * all script files loaded, begin init
			 */
			// TODO: listen for Y.on('snappi-pm:lazyload-complete' ????
			Y.on("domready", function() {
		    	_main(Y);
		    });
		}
		var onlazyload_3 = function(Y, result){
			// init for dialog, pagemakerPlugin
		}
		var modules = modules_1.concat(modules_2);
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
    		'snappi-util-misc', 
    		'snappi-sortedhash','snappi-io', 'snappi-io-helpers', 
    		'snappi-paginator', 'snappi-menu-aui', 
    		'snappi-dialog-aui', 
    		// 'snappi-gallery-helpers', 
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
// LOG("LazyLoad.AIRDesktopUploader  Y.on(domready)");    				
    				try {
    					Y.once('snappi-air:markup-loaded', function(){
// LOG('FIRED snappi-air:markup-loaded');    						
							SNAPPI.AIR.Helpers.go();
    						SNAPPI.AIR.UIHelper.listeners.ImportComplete();
    						SNAPPI.AIR.XhrHelper.checkUpdate();
    					});
    					SNAPPI.AIR.XhrHelper.getMarkup();
    					
						Y.later(10000, this, function(){
							Y.ready('snappi-hint', function(Y){
									SNAPPI.namespace('SNAPPI.STATE.hints');
									SNAPPI.STATE.hints['HINT_Upload'] = true;
									SNAPPI.onYready.Hint(Y);
							});	
				      	});
    				} catch (e) {}
	    		});
		}
		
		// before before_LazyLoad
		var before_LazyLoad = function(){
			// ALLOY_VERSION='alloy-1.5.0';		// set in bootstrap.html
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
				SNAPPI.AIR.host = 'snappi-dev';
			}	
			Config.staticHost.FORCE_HOST = SNAPPI.AIR.host;	// MULTIPLE subdomains doesn't work with AIR
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
		    /*
	         * override String
	         */
	        String.prototype.trim = function(){
	            //            return this.replace(/^\s*/, "").replace(/\s*$/, "");
	            return this.replace(/^\s+|\s+$/g, '');
	        };
		    
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
	        if (SNAPPI.AIR.debug) {
	        	PAGE.isDev = true;
	        }
	    } catch (e) {
	        host = window.location.host;	// hostname:port number
	    }
        //                console.log("host=" + host);
	    o.host = host;
	    defaultCfg = (PAGE.isDev) ? _CFG.DEBUG : _CFG.PROD;
	    
	    
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
	            comboBase: 'http://' + Config.getStaticHost(1) + '/combo/js?baseurl=svc/lib/'+ALLOY_VERSION+'/build&',
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
            base: 'http://' + hostCfg.host + '/js/snappi/', // must be same as alloy?
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
            root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
	    		'snappi-event-hover': {	// deprecate, use yui 'hover'
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
                    requires: ['node', 'snappi-util-misc', 'snappi-group', 'snappi-sortedhash']
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
        		'snappi-tab': {
        			path: 'tabs.js',
        			requires:['node'],
        		},       		
        		'snappi-paginator': {
        			path: 'paginator_aui.js',
        			requires:['history', 'aui-io', 'aui-paginator']
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
                    requires: ['node', 'stylesheet', 'event', 'overlay', 'snappi-util-misc', 'snappi-rating', 'snappi-group', 'snappi-dragdrop', 'snappi-thumbnail-helpers', 'snappi-imageloader']
                    //'snappi-util-misc' SNAPPI.util.hash(bindTo) may be deprecated 
                },
                'snappi-thumbnail-helpers': {
                    path: 'thumbnail-helpers.js',
                    requires: []
                },
                'snappi-gallery': {
                    path: 'gallery.js',
                    requires: ['node', 'event', 'event-key', 
                    'snappi-util-post', // uses SNAPPI.ShotController
                    'snappi-auditions',
                    'snappi-rating', 'snappi-menu-aui', 'snappi-paginator', 'snappi-gallery-helpers', 'snappi-thumbnail-helpers',
                    // 'snappi-dialog-aui', 
                    ] // snappi-util -> SNAPPI.shotController(move)
                },                                       
                'snappi-gallery-helpers': {
                    path: 'gallery-helpers.js',
                    requires: []
                },
                'snappi-lightbox': {
                    path: 'lightbox.js',
                    requires: ['node', 'event', 'io', 'dd', 'dd-plugin', 'snappi-util-post', 'snappi-sortedhash', 'snappi-gallery', 'snappi-dragdrop',  'snappi-rating', 
                    // 'snappi-domJsBinder',
                               /*
                                * experimental
                                */
//	                               'yui2-resize'
                               // 'resize' // yui3 resize
                               ]
                    // snappi-util -> SNAPPI.shotController, SNAPPI.ratingManager, SNAPPI.io (move)
                },
                'snappi-util-misc': {
                    path: 'util-misc.js',
                    requires: ['node']
                },
                'snappi-util-post': {
                    path: 'util-post.js',
                    requires: ['node', 'event-custom', 'io', ,
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
                    requires: ['async-queue', 'node', 'snappi-io']
                },           
                'snappi-ui-helpers': {
                    path: 'ui-helpers.js',
                    requires: ['node', 'snappi-io'],
                },                  
                'snappi-filter': {
                    path: 'filter.js',
                    requires: ['node', 'snappi-rating']
                },
                'snappi-hint': {
                	path: 'hint.js',
                    requires: ['aui-tooltip', 'snappi-util-misc', 'snappi-io', 'snappi-sortedhash', 'cookie']
                }
            }
        };
        if (hostCfg.host == 'preview.snaphappi.com' || hostCfg.snappi_minify) {
			/* use minify
             *   - mods, strip leading ',' from f=
             *   - remove & delimiter, using only ,
             */
           
            yuiConfig_snappi.comboBase = 'http://' + hostCfg.host + '/min/b=js/snappi&yuiconfig&f=';
            yuiConfig_snappi.root = ',';        	
        } 
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
	            comboBase: 'http://' + Config.getStaticHost(1) + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
	            root: 'js/gallery/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
	            modules: {
	            	// TODO: deprecate. refactored, moved to 'snappi-util-misc'
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
	    if (hostCfg.host == 'preview.snaphappi.com' || hostCfg.snappi_minify) {
			/* use minify
             *   - mods, strip leading ',' from f=
             *   - remove & delimiter, using only ,
             */
           
            yuiConfig_gallery.comboBase = 'http://' + Config.getStaticHost(1) + '/min/b=js/gallery&yuiconfig&f=';
            yuiConfig_gallery.root = ',';        	
        }  		
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
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.pagemaker_comboBase,
            root: 'js/create/',
            modules: {
                'pagemaker-base': {
                    path: 'base_aui.js',
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
            base: 'http://' + hostCfg.host + '/static/js/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
            root: 'static/js/',
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
	                requires: ['snappi-paginator','snappi-io'], 
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
            	// '960-reset-css': { 	// load manually in HTML HEAD
            		// path: 'reset.css',
            		// requires: [],
            		// type: 'css'
            	// },
    			// 'AIR-snappi-css': {		// load manually in HTML HEAD
    		        // path: 'AIR_snappi.css',
    		        // requires: ['960-reset-css', 'AIR-upload-ui-css'],		
    		        // type: 'css'
    			// },  
    			'AIR-upload-ui-css': {
    		        path: 'upload_ui.css',
    		        // requires: ['snappi-cake-css','old-snappi-css','snappi-menu-css'],
    		        requires: [],
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
        	SNAPPI.namespace('SNAPPI.STATE.hints');
            // SNAPPI.STATE = SNAPPI.STATE  || {}; // stores session data across XHR calls
        	SNAPPI.STATE = _Y.merge(PAGE.jsonData.STATE, SNAPPI.STATE, PAGE.jsonData.profile);
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
    SNAPPI.startListeners = function(listeners){
    	if (PAGE.jsonData && PAGE.jsonData.listeners) {
			listeners = _Y.merge(listeners, PAGE.jsonData.listeners);
			delete PAGE.jsonData.listeners;
		}
		for (var listen in listeners) {
			if (listeners[listen]!==false) SNAPPI.UIHelper.listeners[listen](listeners[listen]);
		} 
    }
    
    // LazyLoad.gallery();  // move to snappi and snappi-wide layout
    
    
})();
