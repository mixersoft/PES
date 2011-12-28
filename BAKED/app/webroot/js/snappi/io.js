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
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.IO = function(Y){
		if (_Y === null) _Y = Y;
		
		SNAPPI.IO = IO;
		SNAPPI.io = new IO();		// deprecate object, use Static class
	}
    
    /************************************************************************************
	 * singleton async io class for posting to DB
	 * 		SNAPPI.io = new IO();
	 */
	
    var IO = function(){}
    IO.prototype = {
        /**
         * call this method to write profile into metadata
         * postData: data user provided
         * callback: defined in caller function to process response
         */
        writeProfile : function(postData, callback, args){
            var jsonFlag = true;
            var data;
            for(var key in postData){
            	var JSON_SUFFIX = 'AsJSON';	
            	if(key.lastIndexOf(JSON_SUFFIX)>-1){
            		jsonFlag = true;
            	}else{
            		jsonFlag = false;
            	}
        	    keyArray = key.split(".");
        	    var cakeKey = 'data[' + keyArray.join('][') + ']';
        	    var data = {};
        	    if(jsonFlag == true){
        	    	data[cakeKey] = _Y.JSON.stringify(postData[key]);
        	    } else {
        	    	data[cakeKey] = postData[key];
        	    }
        	    this.post("/snappi/writeProfile/.json", data, callback, '');
        	}
        },
        
        /**
         * call this method to read profile from metadata
         * key: to tell cakephp which key you want to read from metadata.
         * callback: defined in caller function to process response
         */
        readProfile : function(key, callback, args){
            var ioCfg = {
                method: "GET",
            	headers: {
	                'Content-Type': 'application/json'
	            },                
                on: {
                    complete: function(id, o, args){
		            	if (o.statusText == 'OK') {
		            		var xhrMsg = _Y.JSON.parse(o.responseText);
			            	callback.complete(xhrMsg);
		            	}else{
		            		//TODO need to think about return what kind of error info
		            	}
                    },
                    failure: function(id, o, args){
                        //TODO need to think about return what kind of error info
                        callback.failure(id, o, args);
                    }
                },
                timeout: 6000,
                context: this
            };
            if (args != undefined) 
                ioCfg.arguments = args;
            var uri = "/snappi/readProfile/.json?key="+key;
            _Y.io(uri, ioCfg);
        },
        
        /**
         * call this method to write session into cakephp session component
         * postData: data user provided
         * callback: defined in caller function to process response
         */
        writeSession : function(postData, callback, args){
            var jsonFlag = true;
            var data = {};
            for(var key in postData){
            	var JSON_SUFFIX = 'AsJSON';	
            	if(key.lastIndexOf(JSON_SUFFIX)>-1){
            		jsonFlag = true;
            	}else{
            		jsonFlag = false;
            	}
        	    keyArray = key.split(".");
        	    var cakeKey = 'data[' + keyArray.join('][') + ']';
        	    
        	    if(jsonFlag == true){
        	    	data[cakeKey] = _Y.JSON.stringify(postData[key]);
        	    } else {
        	    	data[cakeKey] = postData[key];
        	    }
//        	    this.post("/snappi/writeSession/.json", data, callback, '');
        	}
            this.post("/snappi/writeSession/.json", data, callback, '');
        },
        
        savePreviewSize : function(size) {
			var callback = {
				complete : function(id, o, args) {
					var check;
				},
				failure : function(id, o, args) {
					var check;
				}
			};
			var postData = [];
			var photoSizeKeyName = 'profile.previewSize';
			postData[photoSizeKeyName] = size;
			SNAPPI.io.writeSession(postData, callback, '');
		},
        
        /**
         * call this method to read session from cakephp session component
         * key: to tell cakephp which key you want to read from metadata.
         * callback: defined in caller function to process response
         */
        readSession : function(key, callback, args){
            var ioCfg = {
                method: "GET",
            	headers: {
	                'Content-Type': 'application/json'
	            },                
                on: {
		            complete: function(id, o, args){
		            	if (o.statusText == 'OK') {
		            		var xhrMsg = _Y.JSON.parse(o.responseText);
			            	callback.complete(xhrMsg);
		            	}else{
		            		//TODO need to think about return what kind of error info
		            	}
                    },
                    failure: function(id, o, args){
                        //TODO need to think about return what kind of error info
                        callback.failure(id, o, args);
                    }
                },
                timeout: 6000,
                context: this
            };
            if (args != undefined) 
                ioCfg.arguments = args;
            var uri = "/snappi/readSession/.json?key="+key;
            _Y.io(uri, ioCfg);
        },
        
        post : function(uri, postData, callback, args, sync){	
            // POST to uri
            var v, post = [];
            // stringify post params
            for (var k in postData) {
            	v = (postData[k]===null) ? '' : postData[k];
                post.push(k + '=' + v);
            }
            var ioCfg = {
                method: "POST",
                data: post.join('&'),
                on: {
                    complete: function(id, o, args){
            			// process json response
		            	if (o.getResponseHeader && o.getResponseHeader('Content-Type') == 'application/json') {
//		            		o.responseJson = _Y.JSON.parse(o.responseText);
		            		o.responseJson = eval('('+o.responseText+')');
		            	}
		            	if (callback.success) callback.success.call(this, id, o, args);
		            	if (callback.complete) callback.complete.call(this, id, o, args);
            		},
                    failure: callback.failure ||
                    function(id, o, args){
                        var check;
                    }
                },
                timeout: 6000,
                context: this
            };
            if (args != undefined) 
                ioCfg.arguments = args;
            if (sync) ioCfg.sync = true;	// synch XHR call
            var response = _Y.io(uri, ioCfg);
            return sync ? response : null;
        },
        setNamedParams : function(uri, namedData) {
        	// deprecate
        	console.warn("DEPRECATE. use static method, SNAPPI.IO.setNamedParams");
        	return IO.setNamedParams(uri, namedData);

        },
        get : function(uri, callback, qsData, namedData, args, sync){
        	
            var ioCfg = {
                method: "GET",
                on: {
                    complete: function(id, o, args){
            			console.warn('io:complete');
	        			// process json response
		            	if (o.getResponseHeader && o.getResponseHeader('Content-Type') == 'application/json') {
//		            		o.responseJson = _Y.JSON.parse(o.responseText);
		            		o.responseJson = eval('('+o.responseText+')');
		            	}
	        			callback.complete.call(this, id, o, args);
	        		},
	        		success: function(id, o, args) {
	        			console.warn('io:success');
	        		},
                    failure: callback.failure ||
                    function(id, o, args){
                        var check;
                    }
                },
                timeout: 2000,
                context: this
            };
            if (args != undefined) 
                ioCfg.arguments = args;
            if (sync) ioCfg.sync = true;	// synch XHR call
            if (namedData) {
            	uri = IO.setNamedParams(uri, namedData);
            }            
            if (qsData) {
                var qs = [];
                // stringify qs params
                for (var i in qsData) {
                    qs.push(i + '=' + qsData[i]);
                }            
            	uri = uri + '?' + qs.join('&');
            }
            var response = _Y.io(uri, ioCfg);
            return sync ? response : null;
        }, 
    };
    
    
	/**
	 * @deprecated, not sure how this is better than IO.getIORequestCfg
	 * use A.IORequest for enhanced _Y.io XHR. adds loadingMask
	 * @param uri
	 * @param callback
	 * @param cfg {
	 * 		qs: {}, name value querystring params to add to url
	 * 		nameData: {}, name-value name params to add to url 
	 * 		args: [], args to return to callback,
	 * 		context: object
	 * automatically includes A.LoadingMask plugin
	 * 		loadingmask: _Y.Node || {
	 * 			target: _Y.Node target for loadingmask
	 * 			label: string to display, default loading...
	 * 			hideEvent: string, custom event string to hide mask
	 * 		 }
	 * these options from from A.io.request,
	 * 		dataType: string, [text, html, json, xml], default null 
	 * 		cache: boolean, default false. If false the current timestamp will be appended to the url to prevent cache   
	 * 		autoLoad: boolean, default true.
	 * } 
	 * @return A.IORequest
	 */
	IO.getIORequest = function(uri, callback, cfg){
		var ioCfg = IO.getIORequestCfg.call(this, uri, callback, cfg);
        // set loadingMask
        if (cfg.loadingMask) {
        	var target = (cfg.loadingMask instanceof _Y.Node) ? cfg.loadingMask : cfg.loadingMask.target;
        	if (target instanceof _Y.Node) {
        		if (!target.loadingmask) {
					// cleanup listeners
					if (!target.listen) target.listen = {};
					var hideEventString = cfg.loadingMask.hideEvent || 'snappi:hideLoadingMask';
					if (!target.listen[hideEventString]) {
						target.listen[hideEventString] = _Y.on(hideEventString, function(n){
							target.loadingmask.hide();
						});		
					} else {
						// check if eventString matches current listener
						console.warn("snappi.io: check if eventString matches current listener");
					}
					var loadingMaskCfg = {};
	        		if (cfg.loadingMask.label) {
	        			loadingMaskCfg.strings = {loading: cfg.loadingMask.label };
//		        			target.loadingmask.set('strings', {loading: cfg.loadingMask.label });  // BUG: doesn't work here
		        	} 
	        		target.plug(_Y.LoadingMask, loadingMaskCfg);
        		}
				if (ioCfg.autoLoad) target.loadingmask.show();
        	}
        }
        
        // uses A.IORequest
        var io = _Y.io.request(uri, ioCfg);
        return io;
    };    
    
	/**
	 * 
	 * preferred: use IO.pluginIO_RespondAsJson for JSON requests
	 * 
	 * use A.IORequest for enhanced _Y.io XHR. 
	 * @param uri
	 * @param callback
	 * @param cfg {
	 * 		qs: {}, name value querystring params to add to url
	 * 		nameData: {}, name-value nameData params to add to url 
	 * 		args: [], args to return to callback,
	 * 		context: object
	 * these options from from A.io.request,
	 * 		dataType: string, [text, html, json, xml], default null 
	 * 		cache: boolean, default false. If false the current timestamp will be appended to the url to prevent cache   
	 * 		autoLoad: boolean, default true.
	 * } 
	 * @return {} cfg for A.IORequest
	 */    
    IO.getIORequestCfg = function(uri, callback, cfg){
    	// qsData, namedData, args
		cfg = cfg || {};
		uri = uri || cfg.uri;
		callback = callback || cfg.on;
    	
        var ioCfg = {
            method: "GET",
            timeout: 3000,
            cache: false,
            on: {
                complete: function(e, id, o, args){
        			console.warn('io:complete');
        			var ioRequest = e.target;
        			if (!ioRequest.get('dataType')) {
        				// check responseHeader for dataType
        				if (o.getResponseHeader('Content-Type') == 'application/json') {
        					ioRequest.set('dataType', 'json');
		            	}
        			}	 
        		},
        		success: function(e, id, o, args) {
        			console.warn('io:success');
        			var ioRequest = e.target;
        			var context = ioRequest.get('context') || this;
        			// dataType is eval'd in success(), but not complete()
        			switch (ioRequest.get('dataType')) {
	    				case 'json':
	    					o.responseJson = ioRequest.get('responseData');
	    					if (typeof o.responseJson == "String") {
	    						console.error('Plugin.IO.ParseContent() failed to parse json');
	    					}
	    					if (callback.successJson){
	    						return callback.successJson.call(context, e, id, o, args); 
	    						// return callback.successJson.call(context, id, o, args); 
	    					} else return o.responseText;
	    					break;
	    				case 'xml':
	    				case 'html':	
	    				case 'text':
	    				default:
	    					if (callback.success){
	    						return callback.success.call(context, e, id, o, args); 
	    						// return callback.success.call(context, id, o, args); 
	    					} else return o.responseText;
	    				break;
	    			}	
        		},
                failure: function(e, id, o, args){
        			console.warn('io:failure');
        			if (callback.failure){
						return callback.failure.call(context, e, id, o, args); 
						// return callback.success.call(context, id, o, args); 
					} else return o.responseText;
                }
            }
        };
        
        // set uri
        ioCfg.uri = uri;
        
        if (cfg.arguments || cfg.args) 
        	ioCfg.arguments = cfg.arguments || cfg.args;
        var Y = SNAPPI.Y;
        if (cfg.qs) {
            var qs = [];
            // stringify qs params
            for (var i in cfg.qs) {
                qs.push(i + '=' + cfg.qs[i]);
            }            
//        	uri = uri + '?' + qs.join('&');
            ioCfg.data = qs.join('&');
        }
        if (cfg.nameData) {
        	ioCfg.uri = IO.setNamedParams(uri, cfg.nameData);
        }            
        ioCfg.context =  cfg.context || callback.context || this;
        ioCfg.on.context = ioCfg.context;
        
        if (cfg.parseContent) ioCfg.parseContent = cfg.parseContent;
        
        if (cfg.dataType) {
        	ioCfg.dataType = cfg.dataType;
        } else {
        	// automatically set json datatype for uri ending in .json
        	var found = uri.match(/.*\.(.*)$/i);
        	if (found) ioCfg.dataType = found[1];
        }
        
        // set autoLoad
        ioCfg.autoLoad = cfg.autoLoad === false ? false : true;
    	return ioCfg;
    };
    /**
     * adds cakephp named params to uri
     * @param uri	string base uri
     * @param namedData {} key-value pairs
     * 		value === null removes namedParam from string
     * @return string uri
     */
    IO.setNamedParams = function(uri, namedData) {
    	if (!namedData) return uri;
        var name = [];
        // stringify nameData params
        for (var i in namedData) {
            // update or append?
			var regexS = '(\/'+i+'[^:\/]*:)([^\/]*)';
			var regex = new RegExp(regexS);
			var match = regex.exec(uri);
			if (match) {
				if (namedData[i] === null) {
					// remove named param
					uri = uri.replace(match[0], '');
				} else {
					// update nameData param
					uri = uri.replace(match[0], match[1]+namedData[i]);
				}
			} else if (namedData[i] !== null) {
				// append nameData param
            	name.push(i + ':' + namedData[i]);
			}
        }            
        
        if (name.length) {
        	// append named params AFTER RequestHandler TYPE, if any
        	var requestHandler = uri.match(/^(.*)\/(\.\w*)$/);
	        if (requestHandler) {
	        	uri = requestHandler[1] + '/' + name.join('/') + '/' + requestHandler[2];
	        } else {
	        	uri = uri + '/' + name.join('/');
	        }
        }
    	return uri;
    };    
    IO.object2querystring = function(o) {
            var qs = [];
            // stringify qs params
            for (var i in o) {
                qs.push(i + '=' + o[i]);
            }            
            return qs.join('&');    	
    };
    IO.debug_ParseContent = function(plugin) {
    	plugin.io.afterHostMethod('insert', function(e){
    		console.warn("After Plugin.IO.ParseContent(), target="+plugin);
    	});
    };
    
    /**
     * wrap IORequest callbacks with code to parse Json response.
     * @param cfg
     * @return cfg
     */
    IO.pluginIO_RespondAsJson = function(cfg) {
    	cfg = cfg || {};
    	
    	var _callback = {};
    	if (cfg.on) {
	    	_callback.complete = cfg.on.complete;
	    	_callback.success = cfg.on.successJson || cfg.on.success;
	    	_callback.failure = cfg.on.failure;
    	}
    	var _json_callbacks = {
            complete: function(e, id, o, args){
				console.warn('IO.pluginIO_RespondAsJson() io:complete');
				SNAPPI.setPageLoading(false);
				var ioRequest = e.target;
				var context = ioRequest.get('context') || this;
				if (!ioRequest.get('dataType')) {
					// check responseHeader for dataType
					switch (o.getResponseHeader('Content-Type')) {
						case 'application/json': ioRequest.set('dataType', 'json'); break;
						case 'application/xml': ioRequest.set('dataType', 'xml'); break;
	            	}
				}
				if (_callback.complete) _callback.complete.call(context, e, id, o, args);
			},
			success: function(e, id, o, args) {
				console.warn('IO.pluginIO_RespondAsJson() io:success');
				var ioRequest = e.target;
				var context = ioRequest.get('context') || this;
				// dataType is eval'd in success(), but not complete()
				switch (ioRequest.get('dataType')) {
					case 'json':
						o.responseJson = ioRequest.get('responseData');
						if (o.responseJson && o.responseJson.success !== undefined) {
							if (o.responseJson.success && o.responseJson.success != 'false') {
								// successful XHR+JSON response
								if (_callback.success){
									var content = _callback.success.call(context, e, id, o, args);  
		            				if (content !== false ) ioRequest.setContent(content);
								}
								// if (_callback.success){
		    						// return _callback.success.call(context, e, id, o, args); 
		    					// } else return o.responseText;
							} else {
								// successful XHR request, failed JSON response
								if (_callback.failure){
									var content = _callback.failure.call(context, e, id, o, args);
									if (content !== false ) ioRequest.setContent(content);
								} else SNAPPI.flash.flashJsonResponse(o);
							}
							e.stopImmediatePropagation();	// stopPropagation to prevent extra IORequest.setContent()
						} else {
							if (typeof o.responseJson == "string") {
								console.error('Plugin.IO.ParseContent() failed to parse json');
							}
							if (_callback.failure){
								_callback.failure.call(context, e, id, o, args);
							} else SNAPPI.flash.flashJsonResponse(o);
						}
						break;
					case 'xml':
						if (_callback.success){
    						return _callback.success.call(context, e, id, o, args); 
    					} else return o.responseText;
						break;
					case 'html':	
					case 'text':	
						if (_callback.success){
    						return _callback.success.call(context, e, id, o, args); 
    					} else return o.responseText;
					default:
						console.error("Plugin.JsonIO: call to Plugin.JsonIO with no dataType set");
						break;
				}
			},
	        failure: function(e, id, o, args){
				console.warn('IO.pluginIO_RespondAsJson() io:failure');
				var ioRequest = e.target;
				var context = ioRequest.get('context') || this;				
				// timeout or no response
				if (_callback.failure) return _callback.failure.call(context, e, id, o, args);
				else return false;
	        }
	    };
    	cfg.on = _Y.merge(cfg.on, _json_callbacks);
    	
    	// add named params
    	if (cfg.nameData) {
    		if (cfg.uri) {
	    		cfg.uri = IO.setNamedParams(cfg.uri, cfg.nameData);
    		} else console.error('IO.pluginIO_RespondAsJson(): attempt to set named params without providing cfg.uri');
    	};
    	
    	// add querystring params, ok for both GET and POST
        if (cfg.qs) {
			cfg.data = IO.object2querystring(cfg.qs);
			delete cfg.qs;
        }    	
        // add Json dataType
    	if (!cfg.dataType) cfg.dataType = 'json';
    	// add context
    	cfg.context =  cfg.context || this;
    	cfg.on.context = cfg.context;
    	return cfg;
    };    


})();




















	
    
    

