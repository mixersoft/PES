/*
 * SNAPPI PageMker load
 */
(function(){
	var _Y = null;	// initialize by LazyLoad.use()
 	
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
    /*
     * init *GLOBAL* SNAPPI.PM as root for namespace
     */    
    var PM = namespace('SNAPPI.PM');
    PM.name = 'Snaphappi PageMaker';
	PM.namespace = namespace
    
    if (!SNAPPI.id) {
		SNAPPI.id = PM.name;
    }

    namespace('PAGE');
    PAGE.init = PAGE.init || [];
    
    
               
    var PageMakerPlugin = function(external_Y){
    	if (PageMakerPlugin.instance) return PageMakerPlugin.instance;	// singleton
    	this.external_Y = external_Y;
    	PageMakerPlugin.instance = this;
    }
    SNAPPI.PM.PageMakerPlugin = PageMakerPlugin;
    /*
     * STATIC properties & methods
     */	
    PageMakerPlugin.instance = null;
    PageMakerPlugin.loaded = false;
    /**
     * init player, attach to plugin instance
     */
    PageMakerPlugin.startPlayer = function() {
    	var o = PM.pageMakerPlugin;	// instance
    	if (!o.player) {
    		o.player = new PM.Player({
    			content: o.stage.body.get('parentNode'),
    			container: o.stage.body,
    			isPreview: true,
    			FOOTER_H: 20
    		});
    	}
    	try {
    		o.player.init();
    	} catch(e) {}
    };
    
    
    PageMakerPlugin.prototype = {
    	external_Y: null,
    	stage: null,
    	scene: {},
    	setStage: function(n) {
    		this.stage = n;
    		return this;
    	},
    	setScene: function(sceneCfg) {
    		this.scene = sceneCfg;
    		if (sceneCfg.stage) this.stage = sceneCfg.stage;
    		return this;
    	},
    	load: function(cfg){
    		if (!PageMakerPlugin.loaded) {
    			this._lazyLoad(cfg);
    		}
    		else {
    			_Y.fire('snappi-pm:afterPagemakerLoad', _Y);
    			this.external_Y.fire('snappi-pm:afterPagemakerLoad', _Y);
    			// fire('snappi-pm:afterPagemakerLoad', PM.Y);
    		}
    	},
    	_lazyLoad: function(cfg){
			cfg = cfg || {};	// closure for onlazyload
			var self = this;
			// supports WindowOptionClick, primary header menu, xhr init
			var modules_1 = [
				'node', 'event', 'event-custom',
				/*
				 * for datasource3.js.
				 * TODO: refactor to delay init of _queue until class init
				 */
				'async-queue',
			];
			var modules_2 = [
				'snappi-event-hover',
				'snappi-pm-main','snappi-pm-util','snappi-pm-catalog3','snappi-pm-node3',
				'snappi-pm-datasource3','snappi-pm-casting','snappi-pm-audition',
	    		'snappi-pm-arrangement','snappi-pm-role','snappi-pm-production',
	    		'snappi-pm-tryout','snappi-pm-performance3',
	    		'snappi-pm-play',
	    		'snappi-io', 'snappi-auditions'
			];
	
			var modules = modules_1.concat(modules_2);
			/*
			 * callback
			 */
			var onlazyload = function(Y, result){
				PageMakerPlugin.loaded = true;
				Y.fire('snappi-pm:afterPagemakerLoad', Y);
				self.external_Y.fire('snappi-pm:afterPagemakerLoad', Y);
			}
			LazyLoad.use(modules, onlazyload, null );
		},
    	getHost: function(){
    		var o = Config.getHostConfig();
    		return o.host;
    	}
    }		
    

    // define global namespaces
    
   

    
	/*
	 * protected
	 */
    
    /***********************************************************************************
     * Config - bootstrap config methods
     */
    
	var Config = function(){};    
	PM.Config = Config;	// make global
	var _CFG = {		// frequently used startup Config params 
			DEBUG : {	// default when hostname==git*
	    		snappi_comboBase: 'baked/app/webroot&',
	    		air_comboBase: 'app/air&',
	    		pagemaker_comboBase: 'app/pagemaker&',
	    		snappi_useCombo: 0,					// <-- TESTING SNAPPI useCombo
	    		pagemaker_useCombo: 0,
	    		alloy_useCombo: true,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.3.0',	// this is actually set in aui.js
	    		// yui_CDN == true => use "http://yui.yahooapis.com/combo?"
				// yui_CDN == false => use snaphappi hosted yui libs: "/combo/js?"
		    },
	    	PROD : {	// use for unix/server testing
	    		snappi_comboBase: 'app/webroot&',
	    		air_comboBase: 'app/air&',
	    		pagemaker_comboBase: 'app/pagemaker&',
	    		snappi_useCombo: 1,
	    		pagemaker_useCombo: 0,
	    		alloy_useCombo: true,
	    		yahoo_CDN: 0,
	    		YUI_VERSION: '3.3.0',
	    	}
	    }
	namespace('CFG');
	CFG = _CFG;
	
	Config.getYuiConfig = function(force){
		if (PM.yuiConfig && force !== true) {
			return PM.yuiConfig;
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
	    		'pagemaker-css': Config.addModule_pagemaker_CSS(hostCfg),
	    		jsLib: Config.addModule_jsLib(hostCfg),
	    	}
	    };
	    // update yuiConfig for yahoo CDN config
	    if (hostCfg.alloy_useCombo && hostCfg.yahoo_CDN == false) {
	    	// use hosted combo services
	    	yuiConfig.comboBase = 'http://' + hostCfg.host + '/combo/js?baseurl=svc/lib/yui_'+hostCfg.YUI_VERSION+'/yui/build&';
	    	yuiConfig.root = '/';
	    }
	    
	    PM.yuiConfig = yuiConfig;		// make global
	    return yuiConfig;
	};
   /**
    * LazyLoad Static Class
    */
	var LazyLoad = function(){}
	PM.LazyLoad = LazyLoad;
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
			if (PM.onYready) {
				for (var f in PM.onYready) {
					try {
						PM.onYready[f](Y);
						delete 	PM.onYready[f];
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
			PM.Y = _Y;
		} 
		LazyLoad.helpers.before_LazyLoad();
		
		// begin loading modules
		_Y.use.apply(_Y, modules);
	}


	// static methods
	/**
	 * getHost 
	 * - infers correct host config depending on startup mode [localhost|server|AIR|pagemaker]
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
                'snappi-datasource': {
                    path: 'datasource3.js',
                    requires: ['node', 'async-queue', 'io', 'datatype-xml']
                },
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
        		'snappi-tab': {
        			path: 'tabs.js',
        			requires:['node'],
        		},       		
        		'snappi-paginator': {
        			path: 'paginator_aui.js',
        			requires:['aui-io', 'aui-paginator']
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
            base: 'http://' + hostCfg.host + '/app/pagemaker/js/',
            // comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl=PAGEMAKER&',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.pagemaker_comboBase,
            root: 'js/',
            modules: {
                'pagemaker-base': {
                    path: 'create/base_aui.js',
                    requires: ['node', 'io', 'fleegix_xml']
                },
			    'snappi-pm-main': {
			        path: 'create/main3.js',
			        requires: ['snappi-pm-snappi-css','node','snappi-pm-catalog3','snappi-pm-performance3']
			    },
			    'snappi-pm-node3': {
			        path: 'create/node3.js',
			        requires: ['snappi-pm-util','node', 'event', 'substitute', 'stylesheet']
			    },
			    'snappi-pm-util': {
			        path: 'create/util.js',
			        requires: ['io-base','node']
			    },
			    'snappi-pm-datasource3': {
			        path: 'create/datasource3.js',
			        requires: ['snappi-datasource']
			    },
			    'snappi-pm-arrangement': {
			        path: 'create/arrangement.js',
			        requires: ['oop']
			    },
			    'snappi-pm-role': {
			        path: 'create/role.js',
			        requires: ['oop','snappi-sort']
			    },
			    'snappi-pm-production': {
			        path: 'create/production.js',
			        requires: ['node','snappi-pm-catalog3','snappi-pm-util']
			    },
			    'snappi-pm-audition': {
			        path: 'create/audition.js',
			        requires: ['snappi-sort','snappi-pm-util']
			    },
			    'snappi-pm-tryout': {
			        path: 'create/tryout.js',
			        requires: ['snappi-sortedhash','snappi-pm-audition']
			    },
			    'snappi-pm-catalog3': {
			        path: 'create/catalog3.js',
			        requires: ['snappi-sortedhash','snappi-pm-audition', 'snappi-pm-role' ]
			    },
			    'snappi-pm-casting': {
			        path: 'create/casting.js',
			        requires: ['node', 'snappi-pm-catalog3']
			    },
			    'snappi-pm-performance3': {
			        path: 'create/performance3.js',
			        requires: ['node','snappi-pm-tryout','snappi-pm-node3']
			    },
			    'snappi-pm-play': {
			        path: 'play/pageGallery.js',
			        requires: ["event-delegate", "node", "anim"]
	           },
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
	 * snappi pagemaker CSS module
	 * @param hostCfg
	 * @return
	 */ 
    /*
     *  NOTE: getting security violation if I try to load CSS from "/app/pagemaker/css" 
     *  load CSS from mx:HTML location file
     */    
    Config.addModule_pagemaker_CSS = function(hostCfg) {
    	hostCfg = hostCfg || Config.getHostConfig();	
	    var yuiConfig_pagemaker_CSS = {
            combine: false,
            // base: '/app/air/js/css/',
            base: 'http://' + hostCfg.host + '/app/pagemaker/css/',
			comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.pagemaker_comboBase,
	        root: 'css',			// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
            	'snappi-pm-snappi-css': {
	                path: 'snappi.css',
	                type: 'css'
	            }       		     	        
            }
        };    	
		return yuiConfig_pagemaker_CSS; 
	};	
	/**********************************************************************************
	 * end static Class Configure
	 **********************************************************************************/
	
	
	
	
})();
