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



(function(){
	var util = SNAPPI.coreutil;
    SNAPPI.namespace('SNAPPI.yuiConfig');
    SNAPPI.yuiConfig.yui = { // GLOBAL
        base: "http://yui.yahooapis.com/combo?3.3.0/build/", //  local='/svc/yuilib/yui3/'; // or,
        timeout: 10000,
        loadOptional: false,
        combine: 1,
        filter: "MIN",
        allowRollup: true,
//		filter: "DEBUG",
//        insertBefore: 'css-start',
		groups: {}
    };
    
    var host = SNAPPI.AIR.host;
    var localhost = !(/snaphappi.com/.test(host)); // live vs dev site	
    var combo_baseurl = (localhost ? 'baked/' : '') + 'app/webroot&';
    var useCombo = false && !localhost;
    
    SNAPPI.yuiConfig.snappi = {
        combine: useCombo,
        base: '/js/snappi/',
        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
        root: 'js/snappi/',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        modules: {
            'snappi-sort': {
                path: 'sort.js',
                requires: ['node']
            },
            'snappi-sortedhash': {
                path: 'sortedhash.js',
                requires: ['node', 'snappi-sort']
            },
            'snappi-io': {
                path: 'io.js',
                requires: ['node', 'io', 'json']
            },
            'snappi-io-helpers': {
                path: 'io_helpers.js',
                requires: ['async-queue', 'node', 'substitute', 'snappi-io']
            },      
            'snappi-thumbnail-helpers': {
                path: 'thumbnail-helpers.js',
                requires: []
            },                
    		'snappi-menu': {
    			path: 'menu.js',
    			requires:['node']
    		},
    		'snappi-menuitem': {
    			path: 'menuitem.js',
    			requires:['node', 'substitute', 'snappi-menu']
    		},  
    		'snappi-zoom': {
				path: 'zoom.js',
				requires:['node']
			}   		
        }
    };
    SNAPPI.yuiConfig.gallery = {
        combine: useCombo,
        base: '/',
        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
        root: '',						// base for combo loading, combo load uri = comboBase+root+[module-name]
        modules: {
            'gallery-data-element': {
                path: '/js/gallery/dataelement.js',
                requires: ['node', 'yui2-container']
            }	
        }
    };	
    SNAPPI.yuiConfig.AIR = {
        combine: false,
        base: '/app/air/js/',
//        comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
        root: 'app/air/js/',			// base for combo loading, combo load uri = comboBase+root+[module-name]
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
                requires: ['AIR-upload-ui-css', 'AIR-api-bridge', 'AIR-file-progress']
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
	        }  	        
	        
        }
    };   
 
    /*
     *  NOTE: getting security violation if I try to load CSS from "/app/air/js/css" 
     *  load CSS from mx:HTML location file
     */    
    SNAPPI.yuiConfig.AIR_css = {
            combine: false,
            base: '/css/',
//            comboBase: 'http://' + host + '/combo/js?baseurl='+combo_baseurl,
            root: '/',			// base for combo loading, combo load uri = comboBase+root+[module-name]
            modules: {
    	    	'snappi-cake-css': {
    		        path: 'cake.generic.css',
    		        requires: [],
    		        type: 'css'
    			},	    	
    			'snappi-css': {
    		        path: 'snappi.css',
    		        requires: ['snappi-cake-css'],
    		        type: 'css'
    			},    	
    			'snappi-menu-css': {
    		        path: 'menu-skin.css',
    		        requires: ['snappi-cake-css','snappi-css'],
    		        type: 'css'
    			}, 		    
            	'AIR-upload-ui-css': {
    		        path: 'upload_ui.css',
    		        requires: ['snappi-cake-css','snappi-css','snappi-menu-css'],
    		        type: 'css'
    		    } 	        
            }
        };       
    SNAPPI.yuiConfig.yui.groups.snappi = SNAPPI.yuiConfig.snappi;
    SNAPPI.yuiConfig.yui.groups.gallery2 = SNAPPI.yuiConfig.gallery;
    SNAPPI.yuiConfig.yui.groups.AIR = SNAPPI.yuiConfig.AIR;
    SNAPPI.yuiConfig.yui.groups.AIR_css = SNAPPI.yuiConfig.AIR_css;
	
    SNAPPI.useMore = function() {
    	var last = arguments.length-1;
    	var fnContinue = arguments[last];
    	arguments[last] = function(Y, result){
		    if (!result.success) {
		    	LOG('SNAPPI.useMore failure: ' + result.msg, 'warn', 'Example');  				
				Y.log(' SNAPPI.useMore failure: ' + result.msg, 'warn', 'Example');
			} 
		    fnContinue.call(this);
    	};
    	LOG(arguments)
//    	SNAPPI.Y.use.apply(this, arguments);
    	YUI(SNAPPI.yuiConfig.yui).use.apply(this, arguments);
    }
	
    YUI(SNAPPI.yuiConfig.yui).use(
    		/*
    		 * required
    		 */
    		'node', 'event', 'event-delegate', 'event-custom', "event-mouseenter",
    		'node-event-simulate',
    		/*
    		 * snappi modules
    		 */
    		'snappi-sortedhash','snappi-io',	// 'snappi-io-helpers',
    		/*
    		 * air modules - bootstrap only. add additional modules after init 
    		 */
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
    			
    			Y.use('AIR-menuCfg',
					/*
					 * 
					 */
					function(Y, result){
	    			    if (!result.success) {
	    					Y.log('Load failure: ' + result.msg, 'warn', 'Example');
	    				} else {
	    					try {
	    						SNAPPI.AIR.MenuCfg.listenToUploaderRoll();
	    					} catch (e) {}
	    				}
	    			}	
    			);
    			
    			/*********************************************************************************
    			 * domready init
    			 */
    			Y.on('domready', function(){
    				flex_onYuiDomReady();
    				LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready BEGIN <<<<<<<<<<<<<");
    				_domready1(Y);
    				_domready2(Y);
    				LOG(">>>>>>>>>>>>>>>>>>>>>>>>  YUI/domready COMPLETE <<<<<<<<<<<<<");
    			});
    		}
    );
	LOG("load complete: base.js");	
})();



