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
    var util = SNAPPI.coreutil;
     
    /*
     * private methods
     */
    /*
     * CLOSURE. common schema parsing code
     */
    var _parseSchema = function(response, schemaParser, request){
//        LOG("getResponseJSON.success(), response=" + response);
        var count, parsedResponse;
        try {
            count = response.CastingCall.Auditions.Audition.length;
        } 
        catch (e) {
            count = 0;
        }
        /*
         * this.schemaParser.parse - an optional external parsing function which may be
         * applied to the response to get a reformatted parsedResponse.
         */
        if (schemaParser && schemaParser.parse && util.isFunction(schemaParser.parse)) {
            parsedResponse = schemaParser.parse.call(schemaParser, response);
			LOG("schemaParser: done");
        }
        else {
			LOG("warning: no schema parser");
            parsedResponse = response; // default if no parser 
        }
        /*
         * parsed response format
         */
        var o = {
        	response: {
	            request: request, 
	            parsedResponse: parsedResponse,
	            dsResponse: response,
	            count: count
	        }
        };
        return o;
    };    
    
    /**
     * OK
     * _apply_to_CastingCallAuditions - apply fnSuccess to parsedResponse 
     * 		- apply to parsedResponse 
     * optional: uses request.schemaParser to parse response
     * @param {Object} request
     * @param {Object} fnSuccess - the function to apply to the response
     * @param {Object} datasource
     * @param boolean - true for async DB call, default false
     * @return fnSuccess(o, request);
     */         
    var _apply_to_CastingCallAuditions = function(request, fnSuccess, datasource, async) {	
    	//set base_url in query 
        request.base_url = request.base_url || datasource.getConfigs().baseurl;
    	// set up callback for either async == true or false            
        if (async) {
            var callback  = {
                scope: this,
                success: function(o, params){
        			fnSuccess(o, request);
        		},
                failure: function(o, params){
	                LOG("ERROR: getParsedResponse, msg=" + o.response.error);
	            },
                arguments: {
                    request: request
                }
            }
        	return datasource.getParsedResponse(request, callback);
        } else {	// call directly
        	var o = datasource.getParsedResponse(request);
        	fnSuccess(o, request);
       }
    };
    
    /******************************************************************************
     * AIR DataSource for CastingCalls
     */
    var AIRCastingCallDataSource = function(cfg){
        this.load(cfg);
        this.init(cfg);
    };
    AIRCastingCallDataSource.prototype = {
    	load: function(cfg) {
    		LOG("------------------------------> datasource.load() ");
	        var defaultCfg = {
	                page: 1,
	                perpage: __CONFIG.datasource.perpage,  // was this.getConfigs().photosPerpage
	                baseurl: '',
	                schemaParser: null			// AIR schema parser?
	            };
	        /*
	         * TODO: we need to sync this._cfg with this.getConfigs() (from FLEX)
	         */
            this._cfg = util.merge(defaultCfg, cfg);    	
	        this.HOST = 'AIR';
            if (this.schemaParser && util.isObject(this.schemaParser)) {
                this.schemaParser.datasource = this;	// set back ref
            }	        
    	},
        init : function(cfg){
    		LOG("------------------------------> datasource.init() ");
    		cfg = cfg || {};
            //            this._cfg.perpage = this.getConfigs().photosPerpage || this._cfg.perpage;
			// manual override
//			var root = this.getBaseurls();
//			if(root.length>0){ //at first time there is no record in local_stores then result is empty array
//				LOG(root);
//				this.setConfig({baseurl: root[0]});
//			}
//            var lastCfg = this.getConfigs();
//            cfg.perpage = cfg.perpage || lastCfg.photosPerpage;
//            this._cfg = util.merge(this._cfg, cfg);
//            // initialize baseurl with last, or default, if available
//            this._cfg.baseurl = cfg.baseurl || lastCfg.baseurl || this.getBaseurls().shift();
//            LOG("setting baseurl=" + this._cfg.baseurl);
//            this.setBaseurl(this._cfg.baseurl);
            LOG("datasource> baseurl=" + this.getConfigs().baseurl);
            LOG('AIRCastingCallDataSource.init() complete');
        },
        getBaseurl : function(){
            return this.uri || '';
        },
        setBaseurl : function(url){
            this.setConfig({
                baseurl: url
            });
            this.uri = url;
        },            
        /*************************************************************************
         * datasource admin methods
         */
        setDsPerpage : function(n){
			this.setConfig({photosPerpage: n});
            this._cfg.photosPerpage = n;
        },
        
        getDsPerpage : function(){
            return this._cfg.photosPerpage;
        },
        
        /*
         * return count of items in baseurl or if baseurl='*' then total repository items count
         * params - accepts one param as string e.g. base_url = 'base_url/null' or base_url='*'
         * 		  								   if base_url==null then
         * 										   returns current base_url item count
         * 										   if base_url=='base_url' then
         * 										   returns specified base_url item count
         * 										   if base_url='*' then
         * 										   total repository count
         * return - count as number
         * */
        getItemCount : function(base_url){
            return htmlctrl.getItemCount(base_url);
        },         
        /*
         * return Array of baseurls from desktop DB
         */
        getBaseurls : function(){
        	try {
        		var baseurls = flexAPI_Datasource.getBaseurls();
        	} catch (e) {
        		baseurls = _JS_DATASOURCE.getBaseurls();
        	}
        	LOG(baseurls);
            return baseurls;
        },
        
        /*
         * set configs variables
         * params - accept one params as json of key value pair e.g. {perpage:10,..........}
         * return - no return value
         * */
        setConfig : function(json){
            json = json || {};
            try {
            	_flexAPI_UI.datasource.setConfig(json);
            } catch (e) {
            	LOG("EXCEPTION: tried _flexAPI_UI.datasource.setConfig();");
            }
        },
        /*
         * get configs variables
         * params - no params
         * return - return a json of key value pair e.g. {perpage:10,..........}
         * */
        getConfigs : function(){
            return flexAPI_Datasource.cfg;
        },
        /*
         * save login info
         * params - accept two params
         * 			1. username as string
         * 			2. password as string
         * return - return boolean true on success else false
         * */
        saveLoginInfo : function(username, password){
            return htmlctrl.saveLoginInfo(username, password);
        },
        /*
         * get login info
         * params - no params
         * return - return json object e.g. {username:'',password:''}
         * */
        getLoginInfo : function(){
            return htmlctrl.getLoginInfo();
        },
        setSessionId : function (sessionId) {
        	this.sessionId = sessionId;
        },
      
        /*************************************************************************
         * castingCall methods
         */
        
        /*
         * public methods to fetch castingCall response as async
         * params - accept two params first param is json object as cfg e.g. = {
         * 															 page:1,
         * 															 perpage:10,
         * 															 rating:1, //optional
         * 															 dateFrom:'2010:20:04 18:22:57', //optional
         * 															 dateTo:'2010:20:04 18:22:57', //optional
         * 															 tags : 'cars,red' 	 //optional
         * 															}
         * 			second param is callback json object e.g. = {
         *														success : onSuccess,
         *														failure : onFailure,
         *														arguments : {
         *																	cfg : cfg
         *																	}
         * 														}
         * @return CastingCallJSON  e.g. {
         *			"CastingCall": {
         *				"ID": 1271056739,
         *				"Auditions": {
         *					"Audition": [{
         *						"id": "snappi-audition-8639~snappi",
         *	 					"Photo": {
         *							"id": "snappi-audition-8639~snappi",
         *							"W": "3000",
         *							"H": "4000",
         *							"Fix": {
         *								"Crops": "",
         *								"Rating": "1.0",
         * 								"Rotate": "1",
         *								"Scrub": ""
         *							},
         *							"Img": {
         *								"Src": {
         *									"W": "3000",
         *									"H": "4000",
         *									"AutoRender": true,
         *									"Src": "Summer2009\/P1010195.JPG"
         *								}
         *							},
         *							"DateTaken": "2009-09-04 17:08:24",
         *							"TS": 1252109304,
         *							"ExifColorSpace": "1",
         *							"ExifFlash": 1,
         *							"ExifOrientation": "1"
         *						},
         *						"LayoutHint": {
         *							"FocusCenter": {
         *								"Scale": 4000,
         *								"X": 1500,
         *								"Y": 2000
         *							},
         *							"FocusVector": {
         *								"Direction": 0,
         *								"Magnitude": 0
         *							},
         *							"Rating": "",
         *							"Votes": 0
         *						},
         *						"IsCast": 0,
         *						"SubstitutionREF": "",
         *						"Tags": [],
         *						"Clusters": "",
         *						"Credits": ""
         *					},],
         *					"Total": "396",
         *					"Perpage": 75,
         *					"Pages": 6,
         *					"Page": "2",
         *					"Baseurl": "http:\/\/gallery.snaphappi.com\/svc\/ORIGINALS\/"
         *				}
         *			}
         *		};
         * 	  on failure response is json e.g. = { error : 'ERROR MESSAGE HERE' }
         */
        
        /**
         * @return callback.success(o) where
         * o.response = {
         * 	request,
         * 	parsedResponse,
         *  dsResponse,
         *  count
         * }
         */
        getParsedResponse : function(cfg, callback){
            //merge query with current configuration
            cfg = util.isObject(cfg) ? util.merge(this._cfg, cfg) : this._cfg;
            

            
            var schemaParser = cfg.schemaParser || this.schemaParser;
            if (callback && callback.success) {
            	// use async program flow
	            var self = this;
	            var plugin_SchemaParser = {
	                    success: function(response, args){
	            			var o = _parseSchema(response, schemaParser, args.cfg);
	            			callback.success(o, args.cfg);
	            		},
	                    failure: function(response, cfg){
	                        LOG("getResponseJSON.failure(), error=" + response.error);
	                        callback.failure(response, cfg);
	                    },
	                    scope: this,
	                    arguments: {
	                        cfg: cfg
	                    }
	                }	            
	            //air function call to fetch response json running in diff thread
	            setTimeout(function(){
	            	// get castingCall as JSON from local DB.
	                return self.getResponseJSON(cfg, plugin_SchemaParser);
	            }, 50);
	            return false;
            } else {
            	// ASYNC==FALSE, continue with response
            	var response = this.getResponseJSON(cfg);
         	
            	var o = _parseSchema(response, schemaParser, cfg)
            	return o;
            }	            
        },

        /**
         * getAuditions_all - fn(auditions) with parsed auditions from request
         * optional: uses request.schemaParser to parse response before invoking fn
         *   fn.call(context, auditions)
         * @param {Object} request
         * @param {Object} fn	- plugin function for _apply_to_CastingCallAuditions
         * @param {Object} datasource
         * @param boolean - true for async DB call, default false
         * @return fn.call(context, auditions) audition is an array
         */                
        getAuditions_all : function(request, fn, context, async) {	// OK
        	var plugin_All = function(o, arguments){
	            var auditions, response = o.response.parsedResponse;
	            if (response.CastingCall && response.CastingCall.Auditions && response.CastingCall.Auditions.Audition) {
	                if (response.CastingCall.Auditions.Audition.length) {
	                    auditions = response.CastingCall.Auditions.Audition;
	                    fn.call(context || this, auditions);
	                }
	            }
        	};
        	_apply_to_CastingCallAuditions(request, plugin_All, this, async);
        },
        /**
         * getAuditions_each - fn(audition) with EACH parsed audition from request
         * use schemaParser to parse callback response before invoking fn
         *  calls fn.call(context, audition) for each audition
         * @param {Object} request
         * @param {Object} fn
         * @param {Object} scope/context
         * @param boolean - true for async DB call, default false
         * 
         */        
        getAuditions_each : function(request, fn, context, async) {	// OK
        	var plugin_Each = function(o, arguments){
                var auditions, response = o.response.parsedResponse;
                if (response.CastingCall && response.CastingCall.Auditions && response.CastingCall.Auditions.Audition) {
                    if (response.CastingCall.Auditions.Audition.length) {
                        auditions = response.CastingCall.Auditions.Audition;
                        context = context || this;
                        for (var i in auditions) {
                        	fn.call(context, auditions[i]);
                        }
                    }
                }
            };
        	_apply_to_CastingCallAuditions(request, plugin_Each, this, async);
        	return;
        },        

        /**
         * get json castingCall from local DB
         * @params callback - callback object. if !callback then use sync program flow
         * @return object or boolean 
         * 		- json response object if callback == null, 
         * 		- otherwise boolean, true on callback.success
         */
        getResponseJSON : function(cfg, callback){	// OK
            //call to AIR to fetch the CastingCall json
            return _flexAPI_UI.datasource.getCastingCall(cfg, callback);
        },

        
        /*
         * get Image Source path by size or resized image according to input options
         * params : it takes three params
         * 			1. id = photo_id
         * 			2. size = 'tn/bs/bp/sq/bm'
         * 					where tn = 100px
         * 						  bs = 240px
         * 						  bp = 640px
         * 						  bm = 320px
         * 						  sq = 75px
         * 			3. options as json object e.g. = {
         * 											create : true/false,
         * 											autorotate : true/false,
         * 											rotate : [1|3|6|8],
         * 											callback : { //if create or autorotate then fired
         * 													success : function(){},
         * 													failure : function(){},
         * 													arguments : {},
         * 													scope : scope object of class
         * 													}
         * 											}
         * return : returns absolute url of the photo based on size e.g. file:///D:/downloads/1000pics/xyz.jpg
         * NOTE : if create==true or autorotate==true then callback function fired after complete of resized or rotate image
         * */

        
        getImgSrcBySize : function(id, size, options){	// OK
            // default values
            var cfg = {
                create: true,
                replace: false,
                autorotate: true,
                rotate: 1,
                callback: {
                    success: function(o){
                    },
                    failure: function(o){
                    }
                }
            };
            options = util.isObject(options) ? util.merge(cfg, options) : options;
            return _flexAPI_UI.getImgSrcBySize(id, size, options);
        },
        
   
        
        
        /***************************************************************************
         * upload photos
         */
        /**
         * XHR post to url to get url for uploadHost
         */
        setUploadHostFromServer : function(url, fnContinue){
        	url = url || __CONFIG.datasource.uploadHostLookup
            var callback = {
                success: function(e){
        			var uploadHost = e.responseText;
        			_flexAPI_UI.datasource.setConfig('uploadHost', uploadHost);
        			LOG("uploader.setDatasource() callback OK, uploadHost="+uploadHost);
        			fnContine.call(this);
                },
                failure: function(e){
                	LOG("**Error setUploadHostFromServer=" + e);
                }
            };
            alert("**************** WARNING: setUploadHostFromServer HAS NOT BEEN TESTED ********************");
            SNAPPI.io.post(url, callback);
        },
        /**
         * Upload a File to upload server its async process.
         * params - accept two params
         * @params uuid string - uuid/photo_id to upload
         * @params handlers UploadCallbackHandler object handler 
         * @params sessionId string - PHP SESSIONID
         */
        uploadFile : function(uuid, handlers, sessionId){
        	sessionId = sessionId || this.sessionId || SNAPPI.DATASOURCE.sessionId || SNAPPI.STATE.user.id;
LOG(">>>>>>>>>>   SESSIONID="+sessionId); 
            //if no callback handlers then default callback functions
            handlers = handlers ||
            {
                uploadStart_Callback: function(obj){
                },
                uploadError_Callback: function(f, msg){
                },
                uploadProgress_Callback: function(f, bytesLoaded, bytesTotal){
                },
                uploadSuccess_Callback: function(f, serverData, responseReceived){
                },
                uploadComplete_Callback: function(f){
                }
            };
            setTimeout(function(){
            	_flexAPI_UI.uploadFile(uuid, handlers, sessionId);
            }, 50);
        },        
        

        
        
        /*************************************************************************
         * imports photos into local DB 
         */
        
        /*
         * importPhotos()
         * import desktop photos into DB, accepts one base absolute path
         * it internally detects all photos baseurl and save them accordingly
         * params - accept two params
         * 				1. first as string of abosulte base path e.g. 'E:\downloads\AIRPHOTOS'
         * 				2. callback on success or failure of importPhotos queue {
         * 								success:function(e,params){
         * 									e as string = 'success'
         * 								},
         * 								failure:function(e,params){
         * 									e as string = 'error msg'
         * 								},
         * 								scope:,
         * 								arguments:{}
         * 								}
         * return - no value
         * */
        importPhotos : function(baseurl, callback){	// OK
            baseurl = baseurl || '';
            var defaultCallback = {	
            	// default callback
                success: function(o, baseurl){
	            },
	            failure: function(o, baseurl){
	            },
	            scope: this,
	            arguments: baseurl
	        }; 
            callback = util.merge(defaultCallback, callback);
            
            if (callback.progress) {
            	var done = false;
            	var callback2 = util.merge(callback);
            	// setup done closure
            	callback2.success = function(o, args) {
            		done = true;
            		callback.success.call(this, o, args);
            	};
            	callback2.failure = function(o, args) {
            		done = true;
            		callback.failure.call(this, o, args);
            	};
        		
        		var _wait = function () {
	        		setTimeout(function(){
	        			callback.progress.call(this);
	        			if (!done) {
	        				_wait.call(this);
	        			} 
	                }, 500);
        		}
        		_wait.call(this);
            } else callback2 = callback            
            setTimeout(function(){
                htmlctrl.importPhotos(baseurl, callback2);
            }, 50);

        },
        /*
         * to delete photos
         * params - accept one param
         * 		1. array of photos uuid/photo_id e.g. ['photo_id',....]
         * return - return boolean. true when deleted else false.
         **/
        deletePhoto : function(photos){
            photos = photos || [];
            return _flexAPI_UI.datasource.deletePhoto(photos);
        },
        /*
         * remove base path and their photos
         * params - accept one param as an array of base path e.g. ['E:\downloads\AIRPHOTOS',....]
         * return - bool on succes return true else false
         * */
        deleteBaseurl : function(baseurls){
            baseurls = baseurls || [];
            return _flexAPI_UI.datasource.deleteBaseurl(baseurls);
        },
        /**
         * get stats from import photo process 
         * @return - { new: , updated: existing:}
         */
        getImportProgress : function(){	// OK
            return _flexAPI_UI.getImportProgress();
        },
        cancelImport : function() {	// OK
        	var result = _flexAPI_UI.cancelImport();
        	if (result) {
        		LOG('cancel Import Photos');
        		LOG(this.getImportProgress());
        	}
        },
        
        /**************************************************************
         * managing & syncing photo attributes - not used by uploader 
         */
        
        /*
         * get photo by attribute
         * params - accept on param cfg as json object default is empty json. e.g. {
         * 													 photo_id :,
         * 													 rating : ,
         * 													 tags...............
         * 													}
         * return - array json of photos e.g. [{
         * 										rating : '',
         *                                      photo_id : '',
         * 									    and all fields of photos table as key and its value
         * 										}]
         * */
        getPhotosBy : function(cfg){
            cfg = cfg || {};
            return htmlctrl.getPhotosBy(cfg);
        },               
        /*
         * to update existing photo data
         * params - it accept one parameter as a json object e.g. = {
         * 													id : 'photo_id',//required
         * 													rating : 1,
         * 													tags : 'cars,red',
         * 													rotate : 1,
         * 													.......any field name which match the table column to update
         * 													}
         * return -  boolean true/false
         * if updated then return true otherwise return false
         * NOTE: you can see the error log at logs directory if errors occured
         * */
        updatePhotoProperties : function(json){
            return _flexAPI_UI.datasource.updatePhotoProperties(json);
        },
        /*
         * set post server url where post queue post the data
         * params - it accept one param as string url of server  e.g. http://localhost:8080/test/test.php
         * return - returns boolean if successfully set the url otherwise false
         * */
        setUpdateServerUrl : function(url){
            return htmlctrl.setUpdateServerUrl(url);
        },
        /*
         * get currently set post server url where post queue post the data
         * params - no params
         * return - it returns server url e.g. http://localhost:8080/test/test.php
         * */
        getUpdateServerUrl : function(){
            return htmlctrl.getUpdateServerUrl();
        },
        /*
         * post data to the server its async process when queue completes success response comes in callback->success function
         * params - accept two params first is photos array e.g. ['photoid','photoid']
         * 		  second is callback object e.g. cb = {
         * 											success : function(o){
         * 												o = success message
         * 											},
         * 											failure : function(o){
         * 												o = error message
         * 											}
         * 										}
         * NOTE : set post url first before posting data
         * */
        postData : function(photos, callback){
            setTimeout(function(){
                htmlctrl.postData(photos, callback);
            }, 20);
        },
        
        /*
         * get Stale Data means all records of photos where isStale = true
         * 	- syncs ENTIRE ROW with remoteDB
         * 	- must be called at least once after isStale=1, i.e. photo is uploaded
         *
         * params - no params
         * return -  array json of items e.g. [
         * 									{
         * 										photo_id : '',
         * 										upload_status : '',
         * 										rel_path : '',
         * 										isStale : '',
         * 										rest all photos table field columns
         * 									},.......]
         * */
        getStaleData : function(){
            return htmlctrl.getStaleData();
        },
        /*
         * start post data queue for all stale records and after success set isStale=false flag
         * 	Will POST ALL PROPERTIES for photo, including EXIF, to remote DB 
         * params - accept two params
         * 			1. callBackOnEveryPost bool default is false. true to fire callback on every post false to callback at the end
         * 			2. callback as object  e.g. {
         * 										scope : cb ref,
         * 										success : function(e,params){ e = success message},
         * 										failure : function(e,params){ e = failure message},
         * 										arguments : {} optional
         * 									}
         * 	post to this.setUpdateServerUrl()
         *
         * return - no value
         * */
        startSyncQueue : function(callBackOnEveryPost, cb){
            callBackOnEveryPost = callBackOnEveryPost || false;
            //if no callback then default callback functions
            cb = cb ||
            {
                success: function(e){
                },
                failure: function(e){
                }
            };
            setTimeout(function(){
                htmlctrl.startSyncQueue(callBackOnEveryPost, cb);
            }, 50);
        },

        
        
        /*
         * set url for syncFromServer
         * params -  accept one param
         * 			1. string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * return - no return value
         * */
        setSyncFromServerUrl : function(url){
            htmlctrl.setSyncFromServerUrl(url);
        },
        /*
         * get url for syncFromServer
         * params - no params
         * return - return string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * */
        getSyncFromServerUrl : function(){
            return htmlctrl.getSyncFromServerUrl();
        },
        /*
         * syncFromServer()
         *
         * ???: query server for any data changed since lastupdate, sync to desktop DB???
         *
         * send POST request to url with lastupdate timestamp or null and POST data as 'provider_key= & lastupdate=')
         * get all changed records as an array json in format
         * [{
         * 		photo_id:[uuid],
         * 		[key]: [value],
         * 		...
         * },[{},]
         *
         * calls updatePhotoProperties for each array value in callback to save values
         *
         * NOW updatePhotoProperties internally called
         * params - accept two params
         * 				1. lastupdate as a timestamp e.g. 2010-05-20 15:06:12
         * 				2. callback as json object e.g. {
         * 														scope : cb obj ref,
         * 														success : function(e,params){ e = array of json e.g. [{photo_id,changed fields}]},
         * 														failure : function(e,params){ e = error string },
         * 														arguments : {} if any
         * 														}
         * return - no return value
         * */
        syncFromServer : function(lastupdate, callback){
            callback = callback ||
            {
                success: function(e){
                },
                failure: function(e){
                }
            };
            setTimeout(function(){
                htmlctrl.syncFromServer(lastupdate, callback);
            }, 50);
        },
        
        
        
        /*
         * get last sync time as last update timestamp
         * params - no params
         * return - return lastupdate timestamp string format is YYYY-MM-DD JJ:NN:SS
         * */
        getLastSyncTime : function(){
            return htmlctrl.getLastSyncTime();
            
        },
        /*
         * set url for syncAndSetData
         * params -  accept one param
         * 			1. string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * return - no return value
         * */
        setSyncAndSetDataUrl : function(url){
            htmlctrl.setSyncAndSetDataUrl(url);
        },
		// alias
        setSyncToServerUrl : function(url){
            htmlctrl.setSyncAndSetDataUrl(url);
        },		
        /*
         * get url for syncAndSetData
         * params - no params
         * return - return string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * */
        getSyncAndSetDataUrl : function(){
            return htmlctrl.getSyncAndSetDataUrl();
        },
		// alias
        getSyncToServerUrl : function(){
            return htmlctrl.getSyncAndSetDataUrl();
        },		
        /*
         * syncs the individual attributes, first with remote DB, then local DB.
         * 		ONLY syncs properties included in json, not entire row.
         * 		- DOES NOT CHANGE isStale(),
         * 			i.e. does not assume entire ROW has been synced with remoteDB
         *
         * request to url to set data to the server and after success of (postparams as 'provider_key = & uuid= &data= ')
         * set data fires the updatePhotoProperties() internally to set changes locally
         * params - accept two params
         * 				2. json object - changed json object e.g. {id:,rating:,tags,......}
         * 				3. callback as json object e.g. {
         * 														scope : cb obj ref,
         * 														success : function(e,params){},
         * 														failure : function(e,params){},
         * 														arguments : {} if any
         * 														}
         * */
        syncAndSetData : function(json, cb){
            cb = cb ||
            {
                success: function(e){
                },
                failure: function(e){
                }
            };
            setTimeout(function(){
                htmlctrl.syncAndSetData(json, cb);
            }, 50);
        },
		// alias
		syncToServerThenLocal : function(json, cb){
			this.syncAndSetData(json, cb);
		}

    };
    // publish in SNAPPI namespace
    SNAPPI.AIR.CastingCallDataSource = AIRCastingCallDataSource;    
    
//    LOG(SNAPPI.AIR);
//    LOG("load complete: api_bridge.js : CastingCallDataSource");
}());

