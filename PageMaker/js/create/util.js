/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 *
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 * configure SNAPPI.PM.util namespace as use singleton pattern
 */
(function(){
    /*
     * shorthand
     */
    var _Y = null;
	var Plugin = null;
	var PM = SNAPPI.namespace('SNAPPI.PM');
	// Yready init
	PM.onYready.Util = function(Y){
		if (_Y === null) _Y = Y;
	}
    if (!SNAPPI.PM.util) {
        // load once
        SNAPPI.PM.util = {
            /*
             * CONSTANTS
             */
            NATIVE_PAGE_GALLERY_H: 800 - 82, // minus
            // this.stage.header.get('offsetHeight')
            /*
             * Methods
             */
            getCropSpec: function(oCrop, bPreview){
                var scale = 1;
                // // autorender.resize is the same as long as bp~ is used as
                // the input file
                // if (bPreview) {
                // scale = 640 / oCrop.maxDim;
                // }
                var spec = Math.round(oCrop.x * scale) + ',' + Math.round(oCrop.y * scale) + ',' + Math.round(oCrop.w * scale) + ',' + Math.round(oCrop.h * scale);
                spec += '-' + Math.round(oCrop.maxDim * scale);
                return spec;
            },
            parseSrcString: function(src){
                var i = src.lastIndexOf('/');
                var name = {
                    dirname: '',
                    size: '',
                    filename: '',
                    crop: ''
                };
                name.dirname = src.substring(0, i + 1);
                var parts = src.substring(i + 1).split('~');
                switch (parts.length) {
                    case 3:
                        name.size = parts[0];
                        name.filename = parts[1];
                        name.crop = parts[2];
                        break;
                    case 2:
                        if (parts[0].length == 2) {
                            name.size = parts[0];
                            name.filename = parts[1];
                        }
                        else {
                            name.filename = parts[0];
                            name.crop = parts[1];
                        }
                        break;
                    case 1:
                        name.filename = parts[0];
                        break;
                    default:
                        name.filename = src.substring(i + 1);
                        break;
                }
                return name;
            },
            // do exif orientation math between exifOrientation, and subsequent rotate
            orientationLookup: {
                1: {
                    1: 1,
                    3: 3,
                    6: 6,
                    8: 8
                },
                8: {
                    1: 8,
                    3: 6,
                    6: 1,
                    8: 3
                },
                6: {
                    1: 6,
                    3: 8,
                    6: 3,
                    8: 1
                },
                3: {
                    1: 3,
                    3: 1,
                    6: 6,
                    8: 8
                }
            },
            orientationSum: function(orientation, rotate){
                return this.orientationLookup[orientation][rotate];
            },
            rotateDimensions: function(dimOrPoint, orientation){
                if (orientation <= 3) { // exifOrient = 1,3
                    return dimOrPoint;
                } else {
                	var flipped = {};
                	if (dimOrPoint.h) {
                        flipped.w = dimOrPoint.h,
                        flipped.h = dimOrPoint.w
                	} 
                	if (dimOrPoint.X) {
                		flipped.X = dimOrPoint.Y,
						flipped.Y = dimOrPoint.X
						if (dimOrPoint.Scale) flipped.Scale = dimOrPoint.Scale;
                	}
                    return flipped;
                }
            },
            scale2Preview: function(dimOrPoint){
                var scaled = {};
            	if (dimOrPoint.h) {
            		scale = 640/Math.max(dimOrPoint.w, dimOrPoint.h);
                    scaled.h = dimOrPoint.h * scale;
                    scaled.w = dimOrPoint.w * scale;
            	};
            	if (dimOrPoint.X) {
            		scale = 640/Math.max(dimOrPoint.Scale, dimOrPoint.X, dimOrPoint.Y);
            		scaled.X = dimOrPoint.X * scale;
            		scaled.Y = dimOrPoint.Y * scale;
            		scaled.Scale = 640;
            	};
                return scaled;         	
            },
            addCropSpec: function(src, strCropRect, size){
                // strip size prefix before adding prefix
                size = size || "br";
                var crop = strCropRect ? "~" + strCropRect + ".jpg" : "";
                var parts = SNAPPI.PM.util.parseSrcString(src);
                var base = parts.dirname + (size ? size + '~' : '') + parts.filename;
                return base + crop;
            },
            removeCropSpec: function(src, size){
                size = size || "br";
                var i = src.lastIndexOf('/');
                var base = src.substring(0, i + 1);
                return base + size + "~" + this.getBasename(src);
            },
            getBasename: function(src){
                var i = src.lastIndexOf('/');
                var basename = src.substring(i + 1);
                var parts = basename.split('~');
                switch (parts.length) {
                    case 3:
                        return parts[1];
                    case 2:
                        return (parts[0].length == 2 ? parts[1] : parts[0]);
                    case 1:
                    default:
                        return basename;
                }
                
            },
            saveStory: function(cfg){
                var postData;
                if (cfg.content) {
                	postData = {
                    		"data[content]" : encodeURIComponent(cfg.content),
                    		"data[dest]" : encodeURIComponent(cfg.filename),
                        };
                } else {
                    /*
                     * copy file tmp > filename on Server
                     */
                	postData = {
                    		"data[src]" : encodeURIComponent(tmpfile),
                    		"data[dest]" : encodeURIComponent(filename),
                    };
                }
                if (cfg.key) postData["data[key]"]= encodeURIComponent(cfg.key);
                if (cfg.uuid) postData["data[Collection][id]"]= encodeURIComponent(cfg.uuid);
                postData = SNAPPI.IO.object2querystring(postData);
                // var uri = "/pagemaker/save_page/.json";
                var uri = "/stories/save_page/.json";
                var callbacks = {
                	successJson : function(e, id, o, args) {
                		_Y.fire('snappi:save-story-complete', resp, args);
                		var resp = o.responseJson;
                		if (args.success) args.success(resp, args);
                		return false;
                	},
                    complete: function(status, resp, arguments){
                        if (resp.statusText == "OK" || resp.statusText == "CREATED") {
console.log("saveStory complete: statusText="+resp.statusText) ;                       	
                            // if (cfg.success) cfg.success(arguments);
                        }
                    },
                    failure: function(o){
                        var check;
                    }
                };
                var loadingNode = cfg.loadingNode;
                if (loadingNode.io == undefined) {
	                var ioCfg = SNAPPI.IO.pluginIO_RespondAsJson({
						uri: uri ,
						parseContent:false,
						autoLoad: false,
						method: 'POST',
						data: postData,
						dataType: 'json',
						context: cfg.context || this,	
						arguments: cfg, 
						on: callbacks,
					});
					// set loadingmask to parent
					loadingNode.plug(_Y.LoadingMask, {
						strings: {loading:'One moment...'}, 	// BUG: A.LoadingMask
						target: loadingNode,
					});    			
					loadingNode.loadingmask._conf.data.value['target'] = loadingNode;
					loadingNode.loadingmask.overlayMask._conf.data.value['target'] = loadingNode.loadingmask._conf.data.value['target'];
					loadingNode.loadingmask.set('zIndex', 10);
					loadingNode.loadingmask.overlayMask.set('zIndex', 10);			
		            loadingNode.plug(_Y.Plugin.IO, ioCfg );
				} else {
					loadingNode.io.set('data', postData);
					loadingNode.io.set('context', cfg.context || this);
					loadingNode.io.set('uri', uri);
					loadingNode.io.set('arguments', cfg);
		        }
				loadingNode.loadingmask.refreshMask();
				loadingNode.loadingmask.show();	
				loadingNode.io.start();
            },
            saveToPageGallery: function(cfg){	// deprecated
                var postData, filename = (cfg.filename) ? cfg.filename : '123';
                if (cfg.content) {
                	postData = {
                    		"data[content]" : encodeURIComponent(cfg.content),
                    		"data[dest]" : encodeURIComponent(filename)
                        };
                } else {
                    /*
                     * copy file tmp > filename on Server
                     */
                	postData = {
                    		"data[src]" : encodeURIComponent(tmpfile),
                    		"data[dest]" : encodeURIComponent(filename)
                    };
                }
                var sUrl = "/gallery/save_page";
                var callback = {
                    complete: function(status, resp, arguments){
                        if (resp.statusText == "OK" || resp.statusText == "CREATED") {
                            if (cfg.success) 
                                cfg.success(arguments);
                        }
                    },
                    failure: function(o){
                        var check;
                    }
                };
                SNAPPI.io.post(sUrl, postData, callback, {src: sUrl});
            },
            // DEPRECATE. save directly from stage.body
            createStaticPageGallery: function(cfg){
                var parent = (cfg && cfg.parent) ? cfg.parent : null;
                var tmpfile = (cfg && cfg.tmpfile) ? cfg.tmpfile : null;
                var filename = (cfg && cfg.filename) ? cfg.filename : 'tmp';
                var replace = (cfg && cfg.replace) ? cfg.replace : false;
                
                var postData = {"data[content]": encodeURIComponent(parent.unscaled_pageGallery)};
                if (replace) 
                    postData['data[reset]'] = 1;
                
                var sUrl = "/gallery/page_gallery/" + filename;
                
                var callback = {
                    complete: function(status, resp, arguments){
                        var src = arguments.src;
                        try {
                        	if (PAGE.jsonData.controller.here == '/my/pagemaker') {
                        	}
                        } catch (e) {
                        }
                    },
                    failure: function(o){
                        var check;
                    }
                };
                
                SNAPPI.io.post(sUrl, postData, callback, {src: sUrl});
                return sUrl;
            },
            addSubdomain: function(src) {
            	try {
            		var host = SNAPPI.PM.Config.getStaticHost(src);
	            	if (src.indexOf('/') !== 0) host += '/'; 
	            	return 'http://'+host+src;
            	} catch(e){}
            	return src;
            },
        };
    }
})();
