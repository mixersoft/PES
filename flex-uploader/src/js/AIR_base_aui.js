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
var onload_complete = function(){
	// run after entire module has been loaded; convenience fn, to keep main at top of file. 
	var util = SNAPPI.coreutil,
		Config = SNAPPI.AIR.Config;
	/*
	 * bootstrap YUI, Alloy, and snaphappi javascript
	 */
	var hostCfg = Config.getHostConfig (
		{
			// snappi_useCombo: false,
			// pagemaker_useCombo: true,
			alloy_useCombo: true,
			// yui_CDN == true => use "http://yui.yahooapis.com/combo?"
			// yui_CDN == false => use "/combo/js?"
			yahoo_CDN: true,
		});
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
        // insertBefore: 'css-start',
		groups: {
    		alloy: Config.addModule_alloy(hostCfg),
    		snappi: Config.addModule_snappi(hostCfg),
    		gallery: Config.addModule_gallery(hostCfg),
    		AIR: Config.addModule_AIR(hostCfg),
    		AIR_CSS: Config.addModule_AIR_CSS(hostCfg),
    		// pagemaker: Config.addModule_pagemaker(hostCfg),
    		jsLib: Config.addModule_jsLib(hostCfg)
    	}
    };
    if (hostCfg.alloy_useCombo && hostCfg.yahoo_CDN == false) {
    	// use hosted combo services
    	yuiConfig.comboBase = 'http://' + hostCfg.host + '/combo/js?baseurl=svc/lib/yui_3.3.0/yui/build&';
    	yuiConfig.root = '/';
    }
 LOG(yuiConfig);     
    SNAPPI.yuiConfig = yuiConfig;		// make global	
    YUI(SNAPPI.yuiConfig).use(
    		/*
    		 * required
    		 */
    		'node', 'event', 'event-delegate', 'event-custom', "event-mouseenter",
    		'node-event-simulate',
    		/*
    		 * early load modules
    		 */
    		// 'AIR-firebug-1.2',
    		/*
    		 * snappi modules
    		 */
    		'snappi-sortedhash','snappi-io', 'snappi-io-helpers', 'snappi-thumbnail-helpers',
    		'snappi-paginator', 'snappi-menu-aui', 'snappi-ui-helpers', 
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
    		/*
    		 * callback
    		 */
    		function(Y, result) {
    		    if (!result.success) {
    		    	LOG('Load failure: ' + result.msg, 'warn', 'Example');  				
    				Y.log('Load failure: ' + result.msg, 'warn', 'Example');
    			}  
    			/*
    			 * Helper Functions
    			 */
    			Y.Node.prototype.dom = function() {
    				return Y.Node.getDOMNode(this);
    			};
    			Y.Node.prototype.ynode = function() {
    				return this;
    			};
    			HTMLElement.prototype.dom = function() {
    				return this;
    			};
    			HTMLElement.prototype.ynode = function() {
    				return Y.one(this);
    			};
    			
    			
    			SNAPPI.Y = Y;
    			LOG(" *********** base.js:  SNAPPI.Y = " + SNAPPI.Y.version);
    			LOG(SNAPPI.AIR);
    			try {
    				SNAPPI.AIR.Helpers.add_snappiHoverEvent(Y);
    			} catch (e) {}
    			// Y.use('snappi-dialog-aui', 
    			// // 'AIR-menuCfg',
					// /*
					 // * 
					 // */
					// function(Y, result){
	    			    // if (!result.success) {
	    					// Y.log('Load failure: ' + result.msg, 'warn', 'Example');
// LOG(">>>>>>>>  Load failure:  SNAPPI-DIALOG-AUI " + result.msg);    	    					
	    				// } else {
// LOG(">>>>>>>>  Load OK:  SNAPPI-DIALOG-AUI " + result.msg); 
// LOG(result); 	    					
// LOG(" >>>>>>>>>>>>>>>> " + SNAPPI.Helper);
// LOG(SNAPPI);
	    					// try {
	    					// } catch (e) {}
	    				// }
	    			// }	
    			// );
    			
    			/*********************************************************************************
    			 * domready init
    			 */
    			Y.on('domready', function(){
   					var node = SNAPPI.AIR.Helpers.init_GalleryLoadingMask();
    				flex_onYuiDomReady();
    				LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready BEGIN <<<<<<<<<<<<<");
    				_domready1(Y);		// find in init.js
    				_domready2(Y);
    				LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready COMPLETE <<<<<<<<<<<<<");
    			});
    		}
    );
};


(function(){   
    /******************************************************
     * Set up SNAPPI namespace
     */
    var namespace = function() {
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
	namespace('SNAPPI.AIR');
	SNAPPI.namespace = namespace;
	
	/*
	 * config constants
	 */
	__CONFIG = {
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
			end: null
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
    	if ( SNAPPI.coreutil.isArray(msg)) {
    		console.log('FLEX> '+msg);
    		console.log(serialize(msg));
//    	} else if (SNAPPI.coreutil.isObject(msg)) {
//    		 LOG('FLEX> '+msg);
//    		 LOG(msg);
    	} else LOG('FLEX> '+msg);
    };
})();

(function(){  
    /***********************************************************************************
     * Config - bootstrap config methods
     */
	var Config = function(){};    
	Config.prototype = {};
	ALLOY_VERSION='alloy-1.0.2';
	SNAPPI.AIR.Config = Config;	// make global
	SNAPPI.AIR.Config.CONSTANTS = __CONFIG;	// TODO: search/replace
	

	// static methods
	/**
	 * getHost - infers correct host config depending on startup mode [localhost|server|AIR]
	 */
	Config.getHostConfig = function(cfg) {
		cfg = cfg || {};
	    var defaultCfg, o = {};		
	    try {
	        // get host from AIR bootstrap
	        host = SNAPPI.isAIR ? SNAPPI.AIR.host : window.location.host;
	    } catch (e) {
	        host = window.location.host;	// hostname:port number
	    }
        //                console.log("host=" + host);
	    o.host = host;
	    o.isLocalhost = !(/snaphappi.com/.test(host)); // live vs dev site	
	    if (o.isLocalhost) {
	    	defaultCfg = {
	    		snappi_comboBase: 'baked/app/webroot&',
	    		snappi_useCombo: false,
	    		pagemaker_useCombo: true,
	    		alloy_useCombo: true,
	    	}
	    } else {
	    	defaultCfg = {
	    		snappi_comboBase: 'app/webroot&',
	    		snappi_useCombo: false,
	    		pagemaker_useCombo: true,
	    		alloy_useCombo: true,
	    	}
	    }
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
	 * snappi AIR uploader javascript module
	 * @param hostCfg
	 * @return
	 */	
	Config.addModule_AIR = function(hostCfg) {
		hostCfg = hostCfg || Config.getHostConfig();	
		var air_comboBase = 'app/air&';
	    var yuiConfig_AIR = {
	    	// combine: hostCfg.snappi_useCombo,
	        combine: false,		
	        base: '/app/air/js/',
	//        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
			comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+air_comboBase,
	        root: 'js/',			// base for combo loading, combo load uri = comboBase+root+[module-name]
	        modules: {
	            'AIR-helpers': {
	                path: 'helpers.js',
	                requires: [] 
			    },			    
	            'AIR-init': {
	                path: 'init.js',
	                requires: ['AIR-helpers', 'AIR-api-bridge', 'AIR-file-progress', 'AIR-upload-manager', 'AIR-upload-ui'] 
			    },		    
	            'AIR-file-progress': {
	                path: 'fileprogress.js',
	                requires: [ 'AIR-init']                 
			    },    	
		        'AIR-api-bridge': {
			        path: 'api_bridge.js',
			        requires: ['AIR-helpers']
			    },
	            'AIR-test-api': {
	                path: 'testapi.js',
	                requires: ['AIR-api-bridge']                           
		        },    	
	            'AIR-upload-manager': {
	                path: 'upload_manager.js',
	                requires: ['snappi-sortedhash', 'AIR-api-bridge', 'AIR-file-progress']
			    },		    
	            'AIR-upload-ui': {
	                path: 'upload_ui.js',
	                requires: ['AIR-upload-ui-css', 'AIR-snappi-css', 'AIR-api-bridge', 'AIR-file-progress']
			    },
		        'AIR-firebug-1.3': {	// not supported in AIR/webkit browser
		            path: 'debug/firebug-lite.1.3.2.js#startOpened',
		        },    	
		        'AIR-firebug-1.2': {
		            path: 'debug/firebug-lite-compressed.1.2.3.1.js',	            
		        },
	    		'AIR-menuCfg': {
	    			path: 'menucfg.js',
	    			requires:['snappi-menu', 'snappi-menuitem', 'node-event-simulate']
	    		},	        
	            'AIR-js-datasource': {
	                path: 'jsDatasource.js',
	                requires: []                           
		        },  	        
        		'AIR-menu-aui': {
        			path: 'AIR_menu_aui.js',
        			// BUG: requires A.Plugin.IO, found in "aui-io", but not available
        			requires:['aui-io', 'aui-aria', 'aui-overlay-context', 'aui-overlay-manager']
        		}, 		        
        		'AIR-dialog-aui': {
	    			path: 'dialog_aui.js',
	    			requires:['node', 'aui-aria', 'aui-dialog', 'aui-overlay-manager', 'dd-constrain']
	    		},	        
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
    	var air_comboBase = 'app/air&';
	    var yuiConfig_AIR_CSS = {
            combine: false,
            base: '/app/air/js/css/',
			comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+air_comboBase,
	        root: 'js/css',			// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
            	'960-reset-css': {
            		path: 'reset.css',
            		requires: [],
            		type: 'css'
            	},
    	    	'snappi-cake-css': {
    		        path: 'cake.generic.css',
    		        requires: [],
    		        type: 'css'
    			},	    	
    			'old-snappi-css': {
    		        path: 'snappi.css',
    		        requires: ['snappi-cake-css'],
    		        type: 'css'
    			},    	
    			'snappi-menu-css': {
    		        path: 'menu-skin.css',
    		        requires: ['snappi-cake-css','old-snappi-css'],
    		        type: 'css'
    			}, 		    
            	'AIR-upload-ui-css': {
    		        path: 'upload_ui.css',
    		        // requires: ['snappi-cake-css','old-snappi-css','snappi-menu-css'],
    		        requires: [],
    		        type: 'css'
    		    },
    			'AIR-snappi-css': {
    		        path: 'AIR_snappi.css',
    		        requires: ['960-reset-css', 'AIR-upload-ui-css'],		// load last
    		        type: 'css'
    			},       		     	        
            }
        };    	
		return yuiConfig_AIR_CSS; 
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
            base: '/js/snappi/',
            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
            root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
	    		'snappi-event-hover': {
	    			path: 'event_hover.js',
	    			requires:["event-synthetic"]
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
        			requires:['node', 'aui-aria', 'aui-dialog', 'aui-overlay-manager', 'dd-constrain']
        		},
        		'snappi-menu-aui': {
        			path: 'menu_aui.js',
        			// BUG: requires A.Plugin.IO, found in "aui-io", but not available
        			requires:['aui-io', 'aui-aria', 'aui-overlay-context', 'aui-overlay-manager']
        		},        		
        		'snappi-paginator': {
        			path: 'paginator_aui.js',
        			requires:['aui-io', 'aui-paginator']
        		},              		
        		'snappi-dialogbox': {
        			path: 'dialogbox.js',
        			requires:['node']
        		},        		
        		'snappi-dialogboxCfg': {
           			path: 'dialogboxcfg.js',	
           			requires:['node', 'snappi-dialogbox']
           		},
        		'snappi-menu': {
        			path: 'menu.js',
        			requires:['node']
        		},
        		'snappi-menuitem': {
        			path: 'menuitem.js',
        			requires:['node', 'substitute']
        		},
        		'snappi-menucfg': {
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
                    requires: ['node', 'substitute', 'stylesheet', 'event', 'overlay', 'gallery-util', 'snappi-rating', 'gallery-group', 'snappi-dragdrop', 'snappi-thumbnail-helpers', 'snappi-imageloader']
                    //'gallery-util' SNAPPI.util.hash(bindTo) may be deprecated 
                },
                'snappi-thumbnail-helpers': {
                    path: 'thumbnail-helpers.js',
                    requires: []
                },
                'snappi-gallery': {
                    path: 'gallery.js',
                    requires: ['node', 'event', 'event-key', 'snappi-event-hover', 'snappi-utils', 'snappi-rating', 
                               'snappi-dialog-aui', 'snappi-menu-aui', 'snappi-paginator', 'snappi-gallery-helpers', 'snappi-thumbnail-helpers'] // snappi-util -> SNAPPI.shotController(move)
                },                                       
                'snappi-gallery-helpers': {
                    path: 'gallery-helpers.js',
                    requires: []
                },
                'snappi-domJsBinder': {
                    path: 'domJsBinder.js',
                    requires: ['node', 'event-custom', 'io', 'gallery-datasource', 'gallery-auditions', 'snappi-sort', 'snappi-gallery']
                },
                'snappi-lightbox': {
                    path: 'lightbox.js',
                    requires: ['node', 'substitute', 'event', 'io', 'dd', 'dd-plugin', 'snappi-utils', 'snappi-sortedhash', 'snappi-gallery', 'snappi-dragdrop', 'snappi-domJsBinder', 'snappi-rating', 'pagemaker-base',
                               ]
                },
                'snappi-utils': {
                    path: 'utils.js',
                    requires: ['node', 'event-custom', 'io', 'substitute',
                               'aui-io',
                               'snappi-rating', 'snappi-gallery', 'snappi-lightbox']
                },
                'snappi-io': {
                    path: 'io.js',
                    requires: ['node', 'io', 'json', 'aui-io', 'aui-loading-mask']
                },  
                'snappi-io-helpers': {
                    path: 'io_helpers.js',
                    requires: ['async-queue', 'node', 'substitute', 'snappi-io']
                },           
                'snappi-session': {
                    path: 'helper_session.js',
                    requires: ['snappi-io']
                },
                'snappi-ui-helpers': {
                    path: 'ui-helpers.js',
                    requires: [],
                },                
                'snappi-filter': {
                    path: 'filter.js',
                    requires: ['node', 'snappi-rating']
                },
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
	            base: '/js/gallery/',
	            comboBase: 'http://' + hostCfg.host + '/combo/js?baseurl='+hostCfg.snappi_comboBase,
	            root: 'js/gallery/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
	            modules: {
	                'gallery-util': {
	                    path: 'util.js',
	                    requires: ['node']
	                },
	                'gallery-group': {
	                    path: 'groups3.js',
	                    requires: ['node', 'snappi-sortedhash', 'snappi-dragdrop']
	                },
	                'gallery-datasource': {
	                    path: 'datasource3.js',
	                    requires: ['node', 'async-queue', 'io', 'datatype-xml', 'gallery-util']
	                },
	                'gallery-auditions': {
	                    path: 'auditions.js',
	                    requires: ['node', 'gallery-util', 'gallery-group', 'snappi-sortedhash']
	                }
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
            base: '/app/pagemaker/js/create/',
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
            combine: false,
            base: '/js/lib/',
            comboBase: null,
            root: 'js/lib/',
            modules: {
                'fleegix_xml': {
                    path: 'xml.js'
    		    },	
    		    'datejs': {
    		    	path: 'datejs.js'
    	    	},
    	        'snappi-debug': {
    	        	path: 'debug.js'
                }
            }
        };
	    return yuiConfig_jsLib;
	}; 	
	/**********************************************************************************
	 * end static Class Configure
	 **********************************************************************************/
})();	
	


(function(){
	/***************************************************************************
	 * Util Class def
	 */
	var Util = function(){};
	Util.prototype = {
		TYPES: {
		    '[object Function]' : 'function',
		    '[object Array]' : 'array',
		    '[object ScriptBridgingArrayProxyObject]' : 'array', 	// for AIR flex/js bridge
		    '[object RegExp]' : 'regexp',
		    '[object Date]' : 'date',
		    '[object Error]' : 'error'
		}, 
		type : function(o) {
			var prototype = Object.prototype.toString.call(o);
			if (prototype === undefined) return o 
			else {
				return this.TYPES[prototype] || (prototype ? 'object' : 'null');
			}
		},
		isUndefined: function(o) {
		    return typeof o === 'undefined';
		},
		isFunction : function(o) {
		    return this.type(o) === 'function';
		},
		isBoolean : function(o) {
		    return typeof o === 'boolean';
		},
		isArray : function(o) {
		    return this.TYPES[Object.prototype.toString.call(o)]  === 'array';
		},
		isNumber : function(o) {
		    return typeof o === 'number'//  && isFinite(o);
		},
		isObject : function(o, failfn) {
		    var t = typeof o;
		    return (o && (t === 'object' ||
		        (!failfn && (t === 'function' || this.isFunction(o))))) || false;
		},
		isString : function(o) {
		    return typeof o === 'string';
		},
		isRegexp: function(o) {
		    return this.type(o) === 'regexp';
		},
		isDate : function(o) {
		    // return o instanceof Date;
		    return this.type(o) === 'date' && o.toString() !== 'Invalid Date'; // && !isNaN(o);
		},
		// merge properties of anonymous objects
		merge: function(a, b){
		    if (!this.isObject(a, true)){
		    	return false;
		    }
		    if (!this.isObject(b, true)){
		    	var b = a, a = {};
//		    	return this.copy(a);
		    }
		    for (var p in b) {
		    	if (b.hasOwnProperty(p)) a[p] = b[p];
		    }
		    return a;
		},
		// sort of a deep copy. 
		copy: function(o){
		    if (o == null || !this.isObject(o, false)) 
		        return o;
		    if (this.isArray(o)) {
		        var temp = [];
		        for (var i = 0; i < o.length; i++) {
		            // special code for dataElement.boundTo =[]
		            if (typeof(o[i]) == 'object' && !(o[i] instanceof HTMLElement)) 
		                temp.push(this.copy(o[i]));
		            else 
		                temp.push(o[i]);
		        }
		        return temp;
		    }
		    else 
		        if (this.isRegexp(o)) {
		            return o;
		        }
		    // object or function
		    var temp = {};
		    for (var key in o) 
		        temp[key] = this.copy(o[key]);
		    return temp;
		}	
	};
	SNAPPI.coreutil = new Util();
	/**
	 * end Util class
	 ******************************************************************************/
})();


onload_complete();


