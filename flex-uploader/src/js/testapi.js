//Test.getImgSrcBySize('34C2FCE6-8578-4379-A554-2BFFA5F753FF','bp',{autorotate:true,rotate : 6,callback : {success:function(o){LOG('success = ' + o);},failure:function(o){LOG('failure = ' + o);}}})
(function(){
	var Y = SNAPPI.Y;
    /* 
     * Class Test to test SNAPPI.AIR.Test/BRIDGE of AIR
     *
     */
    Test = {
        conn: null, //this variable is used to store the class object of SNAPPI.AIR.CastingCallDataSource
        uploadQueue: null, //this variable is used to store the object of SNAPPI.AIR.Test_UploadQueue
        /* 
         * To check air is running
         * return Boolean
         */
        isAir: function(){
            return (SNAPPI && SNAPPI.isAIR);
        },
        
        /*
         * To Get CastingCall response json
         * it never return value cause it is Async in nature
         * to fetch its response callback needed
         * e.g. var callback = {
         *		success : onSuccess,
         *		failure : onFailure,
         *		arguments : {
         *			cfg : cfg
         *		}
         *	}
         * When request fires if no errors then the response comes in onSuccess function of callback
         * else if errors in AIR app while running request then onFailure function fires and error response comes in
         * onFailure function
         1. If Success Response = CastingCallJSON  e.g. {
         "CastingCall": {
         "ID": 1271056739,
         "Auditions": {
         "Audition": [{
         "id": "snappi-audition-8639~snappi",
         "Photo": {
         "id": "snappi-audition-8639~snappi",
         "W": "3000",
         "H": "4000",
         "Fix": {
         "Crops": "",
         "Rating": "1.0",
         "Rotate": "1",
         "Scrub": ""
         },
         "Img": {
         "Src": {
         "W": "3000",
         "H": "4000",
         "AutoRender": true,
         "Src": "Summer2009\/P1010195.JPG"
         }
         },
         "DateTaken": "2009-09-04 17:08:24",
         "TS": 1252109304,
         "ExifColorSpace": "1",
         "ExifFlash": 1,
         "ExifOrientation": "1"
         },
         "LayoutHint": {
         "FocusCenter": {
         "Scale": 4000,
         "X": 1500,
         "Y": 2000
         },
         "FocusVector": {
         "Direction": 0,
         "Magnitude": 0
         },
         "Rating": "",
         "Votes": 0
         },
         "IsCast": 0,
         "SubstitutionREF": "",
         "Tags": [],
         "Clusters": "",
         "Credits": ""
         },],
         "Total": "396",
         "Perpage": 75,
         "Pages": 6,
         "Page": "2",
         "Baseurl": "http:\/\/gallery.snaphappi.com\/svc\/ORIGINALS\/"
         }
         }
         }
         * 2. if Failure ResponseJSON = {
         * 								error : 'ERROR MESSAGE HERE'
         * 							}
         *
         *
         *  Note - : it takes one param as object e.g. qs = {
         * 											page : 1,
         * 											perpage : 10,
         * 											rating : 1, //optional
         * 											dateFrom : '2010:20:04 18:22:57', //optional
         * 											dateTo : '2010:20:04 18:22:57', //optional
         * 											tags : 'cars,red' //optional
         * 											}
         */
        CastingCallRequest: function(qs, fnContinue){
            var callback = {
                success: this.onSuccess,
                scope: this,
                failure: this.onFailure,
                arguments: {
                    cfg: qs,
                    fnContinue: fnContinue
                }
            }
            this.ds.getParsedResponse(qs, callback);
        },
        /*
         *  Callback success handler of CastingCallRequest
         *  Here CastingCallJson comes in first param and in second param arguments comes which sends while sending request
         *
         * */
        onSuccess: function(e, arguments){
            var response = e;//e.response.parsedResponse.results;
            if (response.CastingCall && response.CastingCall.Auditions && response.CastingCall.Auditions.Audition) {
                if (response.CastingCall.Auditions.Audition.length) {
                    LOG('Audition[0].src=' + response.CastingCall.Auditions.Audition[0].Photo.Img.Src.Src);
                    LOG('Audition[0].id=' + response.CastingCall.Auditions.Audition[0].Photo.id);
                    
                    LOG('Audition[0].DateTaken=' + response.CastingCall.Auditions.Audition[0].Photo.DateTaken);
                    
                    Test.auditions = response.CastingCall.Auditions.Audition;
                    LOG(Test.auditions.length + " rows returned.");
                    arguments.fnContinue(Test.auditions);
                }
                
            }
            else {
                LOG('No Records Found');
            }
        },
        /* 
         * Callback failure handler of CastingCallRequest
         * Here error response comes in first param and in second param arguments comes which sends while sending request
         *
         * */
        onFailure: function(e, params){
            LOG("ERROR: getParsedResponse, msg=" + e.response.error);
        },
        /*
         * to fetch all stored root folder's
         * params : it take no params
         * return Array of base_url as simple string e.g. arr = ['D:\downloads','D:\downloads\1000pics']
         * */
        getBaseurls: function(){
            return this.ds.getBaseurls();
        },
        /*
         * used to get the currently set baseurl
         *
         * */
        getBaseurl: function(){
            return this.ds.getConfigs().baseurl;
        },
        /*
         * used to set the baseurl
         *
         * */
        setBaseurl: function(baseurl){
            this.ds.setConfig({
                baseurl: baseurl
            }
            LOG('Test.setBaseurl(), result=' + this.ds.getConfigs().baseurl);
        },
        /*
         * it returns count of items in baseurl or if baseurl='*' then total repository items count
         * params - accepts one param as string e.g. base_url = 'base_url/null' or base_url='*'
         * 		  								   if base_url==null then
         * 										   returns current base_url item count
         * 										   if base_url=='base_url' then
         * 										   returns specified base_url item count
         * 										   if base_url='*' then
         * 										   total repository count
         * return - count as number
         * */
        count: function(baseurl){
            baseurl = baseurl || null;
            return this.ds.getItemCount(baseurl);
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
         * 						  sq = 75 x 75 px
         * 			3. options as json object e.g. = {
         * 											create : true/false,
         * 											autorotate : true/false,
         * 											rotate : 2,
         * 											callback : {
         * 													success : function(){},
         * 													failure : function(){},
         * 													arguments : {},
         * 													scope : scope object of class
         * 													}
         * 											}
         * return : returns absolute url of the photo based on size e.g. file:///D:/downloads/1000pics/xyz.jpg
         * NOTE : if create==true or autorotate==true then callback function fired after complete of resized or rotate image
         * */
        getImgSrcBySize: function(id, size, options){
            var path = this.ds.getImgSrcBySize(id, size, options);
            LOG(path);
            return path;
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
        updatePhotoProperties: function(json){
            var success = this.ds.updatePhotoProperties(json);
            if (success) {
                LOG("Photo Updated Successfully");
            }
            else {
                LOG("Photo Not Updated. Please check the error log in logs directory located in installed app directory");
            }
            return success;
        },
        /*
         * to delete photos
         * params - accept one param
         * 		1. array of photos uuid/photo_id e.g. ['photo_id',....]
         * return - bool true when deleted else false.
         * */
        deletePhoto: function(photos){
            var isDeleted = _flexAPI_Datasource.deletePhoto(photos);
            LOG('isDeleted = ', isDeleted);
        },
        /*
         * set post url where to post photo data
         * params - it accept one param as string url of server  e.g. http://localhost:8080/test/test.php
         * return - returns boolean if successfully set the url otherwise false
         * */
        setUpdateServerUrl: function(url){
            var flag = this.ds.setUpdateServerUrl(url);
            if (flag) {
                LOG('Test.setUpdateServerUrl(),result = ', this.ds.getUpdateServerUrl());
            }
        },
        /*
         * get currently set post server url where post queue post the data
         * params - no params
         * return - it returns server url e.g. http://localhost:8080/test/test.php
         * */
        getUpdateServerUrl: function(){
            return this.ds.getUpdateServerUrl();
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
        postData: function(photos, callback){
            this.ds.postData(photos, callback);
        },
        /*
         * To add photos to upload queue
         * It accept two params 
         * 		1. photo_id Array as an input and see the current
         * 		2. batch_id optional default is active batch
         * upload session if any active session found then append photos to it otherwise
         * start new upload session and initialize new batch_id to it
         * and then add photos to that upload session.
         * params - accept one paramater as an array of photo_ids e.g. photos = ['photo_id','photo_id']
         * return - no of photos added
         * */
        addToUploadQueue: function(photos,batch_id){
            return _flexAPI_UI.addToUploadQueue(photos,batch_id);
        },
        /*
         * remove photos from upload queue
         * params - accept one param as any array of photo_ids to remove e.g. photos = ['photo_id','photo_id']
         * return - no of photos removed from queue
         * */
        removeFromUploadQueue: function(photos){
            return _flexAPI_UI.removeFromUploadQueue(photos);
        },
        /*
         * clear all record from upload queue based on status 
         * params - accept two params 
         * 			1. status as string e.g. pending/error/cancelled/all
         * 			2. batch_id optional default is currect active batch
         * return - bool - true/false
         * */
        clear: function(status,batch_id){
            return this.uploadQueue.clear(status,batch_id);
        },
        /*
         * used to startQueue
         * params - no params
         * return - bool true/false
         * */
        startQueue: function(){
            return this.uploadQueue.startQueue();
        },
        /*
         * pause currently started queue
         * params - no params
         * return - bool true/false
         * */
        pauseQueue: function(){
            return this.uploadQueue.pauseQueue();
        },
        /*
         * to get photos from current active upload batch
         * based on status e.g. pending/error/done/all. default status is 'all'
         * params - accept one param as a string e.g. status = pending/error/done/all
         * return - array of json of photos e.g. [
         * 										  {
         * 											photo_id : 'photo_id',
         * 											batch_id : 'batch_id',
         * 											status : 'pending/error/done',
         * 											}
         * 										 ]
         *
         * */
        getCurrentUploadStatus: function(status){
            return this.uploadQueue.getCurrentUploadStatus(status);
        },
        /*
         * sends error(not uploaded due to some reason) while uploading set back to running queue
         * params - no param
         * return - bool return/false
         * */
        retryUpload: function(){
            return this.uploadQueue.retryUpload();
        },
        /*
         * it requests to getUploadHost.php to fetch the upload server url which tells where to upload
         * photos. it is aync process and gives the url in callback response
         * params - it accept two params first is url string e.g. http://localhost:8080/getUploadHost.php second is callback json e.g. {
         * 													 success : function(e){},
         * 													 failure : function(e){},
         * 													 arguments : {}, //optional
         * 													 scope : ref object // optional
         * 													}
         * */
        setUploadHostFromServer: function(url, cb){
            this.ds.setUploadHostFromServer(url, cb);
        },
        /*
         * set upload host server for current upload queue
         * params - it accept one string as a host e.g. http://localhost:8080/test/upload.php
         * return - no return value
         * */
        setUploadFilePOSTurl: function(host){
            this.uploadQueue.setUploadFilePOSTurl(host);
        },
        /*
         * get upload host server of currently set upload queue
         * params - no params
         * return - return string as a host e.g. http://localhost:8080/test/upload.php or empty string if not set
         * */
        getUploadHostOfQueue: function(){
            return this.uploadQueue.getUploadHostOfQueue();
        },
        /*
         * set batch_id for upload queue
         * params - accept one param as string batch_id e.g. ABC99EUI09DSKJKS
         * return - no return value
         * */
        setBatchId: function(batch_id){
            this.uploadQueue.setBatchId(batch_id);
        },
        /*
         * set upload status e.g. error/cancelled/done
         * params - accept three params
         * 					1. uuid/photo_id as string
         * 					2. status as string e.g. error/cancelled/done. When status=done then it also true the isStale flag
         * 					3. batch_id as string - optional default is null. if null then pick active batch_id otherwise given
         * return - boolean if status updated then return true otherwise false
         * */
        setUploadStatus: function(uuid, status, batch_id){
            var flag = _flexAPI_UI.datasource.setUploadStatus(uuid, status, batch_id);
            if (flag) {
                LOG('Upload status set Successfully');
            }
            else {
                LOG('Upload status NOT set');
            }
            return flag;
        },
        /*
         * get all page items according to page and status and batch_id
         * params - accept three params
         * 				1. page as number
         * 				2. status as string - optional default is all e.g. error/cancelled/done/all
         * 				3. batch_id as string - optional default is null. if null then pick active batch_id otherwise given
         * return - json array of items e.g. [
         * 									{
         * 										id : '',
         * 										photo_id : '',
         * 										batch_id : '',
         * 										status : '',
         * 										rel_path : '',
         * 										rating : '',
         * 										tags : ''
         * 									},.......]
         * */
        getPageItems: function(page, status, batch_id){
            var items = this.uploadQueue.getPageItems(page, status, batch_id);
            LOG("Total Page Items ", items.length, "Of Page", page);
            return items;
        },
        /*
         * get all batch ids
         * params - no params
         * return - json of json e.g. {
         * 							  open : ['batch_id','batch_id','batch_id',...],
         * 							  closed : ['batch_id','batch_id','batch_id',...]
         * 							}
         * */
        getBatchIdsFromDB: function(){
            var batchids = this.uploadQueue.getBatchIdsFromDB();
            LOG("Total Open ", batchids.open.length, "Total Closed ", batchids.closed.length);
            return batchids;
        },
        /*
         * set items per page for a upload queue
         * params - accept one param as a number e.g. uploadQueuesPerpage = 10,20,35...
         * return - no return value
         * */
        setUploadQueuesPerpage: function(uploadQueuesPerpage){
            this.uploadQueue.setPerpage(uploadQueuesPerpage);
        },
        /*
         * get items per page for a upload queue
         * params - no params
         * return - as a number e.g. uploadQueuesPerpage = 10,20,35...
         * */
        getPerpage: function(){
            return this.uploadQueue.getPerpage();
        },
        
        /*
         * get items by status
         * params - accept three params
         * 			1. status as string e.g. pending/error/cancelled/done/all. default status is 'all'
         * 			2. batch_id as string optional.
         * 			3. operator as string e.g. =,!= default is =
         * return -  array json of items e.g. [
         * 									{
         * 										id : '',
         * 										photo_id : '',
         * 										batch_id : '',
         * 										status : '',
         * 										rel_path : '',
         * 										rating : '',
         * 										tags : ''
         * 									},.......]
         * */
        getItemsByStatus: function(status, batch_id, op){
            var items = this.uploadQueue.getItemsByStatus(status, batch_id, op);
            LOG("Total Items = ", items.length);
            return items;
        },
        /*
         * get total items count by status
         * params - accept three params
         * 			1. status as string e.g. pending/error/cancelled/done/all. default status is 'all'
         * 			2. batch_id as string optional.
         * 			3. operator as string e.g. =,!= default is =
         * return - return number as total count
         * */
        getCountByStatus: function(status, batch_id, op){
            var count = this.uploadQueue.getCountByStatus(status, batch_id, op);
            LOG("getCountByStatus = ", count);
            return count;
        },
        /*
         * Upload a File to upload server its async process.
         * params - accept two params
         * 			1. uuid/photo_id to upload
         * 			2. handler object to handle upload success/failure/progress etc. e.g. {
         * 																		scope : cb object reference
         * 																		uploadStart_Callback : function(obj){
         * 																		},
         * 																		uploadError_Callback : function(f, msg){
         * 																		},
         * 																		uploadProgress_Callback : function(f, bytesLoaded, bytesTotal){
         * 																		},
         *																		uploadSuccess_Callback : function(f, serverData, responseReceived){
         * 																		},
         *	 																	uploadComplete_Callback : function(f){
         * 																		}
         * 																	}
         * */
        uploadFile: function(uuid, handlers){
        	_flexAPI_UI.uploadFile(uuid, handlers);
        },
        /*
         * get Stale Data means all records of photos where isStale = true
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
        getStaleData: function(){
            var staledata = this.ds.getStaleData();
            LOG('getStaleData() count=', staledata.length);
            return staledata;
        },
        /*
         * start post data queue for all stale records and after succes false isStale flag 
         * params - accept two params
         * 			1. callBackOnEveryPost bool default is false. true to fire callback on every post false to callback at the end
         * 			2. callback as object  e.g. {
         * 										scope : cb ref,
         * 										success : function(e,params){ e = success message},
         * 										failure : function(e,params){ e = failure message},
         * 										arguments : {} optional
         * 									}
         * return - no value
         * */
        startSyncQueue: function(callBackOnEveryPost, cb){
            this.ds.startSyncQueue(callBackOnEveryPost, cb);
        },
        /*
         * get photo by fields
         * params - accept on param cfg as json object e.g. {
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
        getPhotosBy: function(cfg){
            var photos = this.ds.getPhotosBy(cfg);
            LOG("getPhotosBy count=", photos.length);
            return photos;
        },
        /*
         * set url for syncFromServer
         * params -  accept one param
         * 			1. string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * return - no return value
         * */
        setSyncFromServerUrl: function(url){
            this.ds.setSyncFromServerUrl(url);
        },
        /*
         * get url for syncFromServer
         * params - no params
         * return - return string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * */
        getSyncFromServerUrl: function(){
            var url = this.ds.getSyncFromServerUrl();
            LOG('getSyncFromServerUrl = ', url);
        },
        /* NOW updatePhotoProperties internally called
         * request to a url with lastupdate timestamp if any else null and
         * get all changed records as an array json in which the changed columns comes photo_id is must in this array json
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
        syncFromServer: function(lastupdate, callback){
            this.ds.syncFromServer(lastupdate, callback);
        },
        /*
         * get last sync time as last update timestamp
         * params - no params
         * return - return lastupdate timestamp string format is YYYY-MM-DD JJ:NN:SS
         * */
        getLastSyncTime: function(){
            var lastsynctime = this.ds.getLastSyncTime();
            LOG("getLastSyncTime() = ", lastsynctime);
            return lastsynctime;
        },
        /*
         * set url for syncAndSetData
         * params -  accept one param
         * 			1. string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * return - no return value
         * */
        setSyncAndSetDataUrl: function(url){
            this.ds.setSyncAndSetDataUrl(url);
        },
        /*
         * get url for syncAndSetData
         * params - no params
         * return - return string as url e.g. http://localhost:8080/testsite/syncstatus.php
         * */
        getSyncAndSetDataUrl: function(){
            var url = this.ds.getSyncAndSetDataUrl();
            LOG('getSyncAndSetDataUrl = ', url);
            return url;
        },
        /*
         * request to url to set data to the server and after success of
         * set data fires the updatePhotoProperties() internally to set changes locally
         * params - accept two params
         * 				1. json object - changed json object e.g. {id:,rating:,tags,......}
         * 				2. callback as json object e.g. {
         * 														scope : cb obj ref,
         * 														success : function(e,params){e = success message},
         * 														failure : function(e,params){e = failure message},
         * 														arguments : {} if any
         * 														}
         * */
        syncAndSetData: function(json, cb){
            this.ds.syncAndSetData(json, cb);
        },
        /*
         * set configs variables
         * params - accept one params as json of key value pair e.g. {perpage:10,..........}
         * return - no return value
         * */
        setConfigs: function(json){
            this.ds.setConfigs(json);
        },
        /*
         * get configs variables
         * params - no params
         * return - return a json of key value pair e.g. {perpage:10,..........}
         * */
        getConfigs: function(){
            var cfg = this.ds.getConfigs();
            LOG("getConfigs = ", cfg);
            return cfg;
        },
        /*
         * save login info
         * params - accept two params
         * 			1. username as string
         * 			2. password as string
         * return - return boolean true on success else false
         * */
        saveLoginInfo: function(username, password){
            var flag = this.ds.saveLoginInfo(username, password);
            LOG("saveLoginInfo success=", flag);
            return flag;
        },
        /*
         * get login info
         * params - no params
         * return - return json object e.g. {username:'',password:''}
         * */
        getLoginInfo: function(){
            var obj = this.ds.getLoginInfo();
            LOG("getLoginInfo username,password", obj.username, obj.password);
            return obj;
        },
        /*
         * add photos it accept one base absolute path
         * it internally detects all photos baseurl and save them accordingly
         * params - accept two params
         * 				1. first as string of abosulte base path e.g. 'E:\downloads\AIRPHOTOS'
         * 				2. callback on success or failure of addphotos queue {
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
        addPhotos: function(baseurl, cb){
            this.ds.addPhotos(baseurl, cb);
        },
        /*
         * remove base path and their photos
         * params - accept one param as an array of base path e.g. ['E:\downloads\AIRPHOTOS',....]
         * return - bool on succes return true else false
         * */
        deleteBaseurl: function(baseurls){
            var flag = _flexAPI_Datasource.deleteBaseurl(baseurls);
            LOG("deleteBaseurl success = ", flag);
            return flag;
        },
        
        /*
         * get Scanned Images Number i.e. how much total images added existing updated in a queue.
         * params - accept one param
         * 			1. flag as string added//existing/updated no default value
         * return - number as total number of added//existing/updated
         * */
        getImportProgress: function(flag){
            LOG(this.ds.getImportProgress());
        },
        /*
         * start/stop native drag & drop functionality
         * params - accept one param
         * 		1. allowed as boolean if true then start else stop drag & drop
         * */
        nativeDDAllowed : function(allowed){
        	this.ds.nativeDDAllowed(allowed);
        },
        /*
         * This function is used to initiate/start testing
         * it makes SNAPPI.AIR.CastingCallDataSource class object
         * and all the testing function used it
         * */
        init: function(datasource, dsCfg){
            if (!this.isAir()) {
                alert('TEST FAILED: NOT IN AIR MODE');
                return;
            } 
            LOG('IN AIR MODE');
            this.ds = datasource || new SNAPPI.AIR.CastingCallDataSource();
            if (!dsCfg) {
	            /*
	             * set datasource for uploader
	             */
	            var baseurl = 'http://git:88/air/flex-uploader/lib/_php/';
	            var uploadHost = {
	            		// this is where the files are uploaded to
	            		local: baseurl + "upload.php",
	            		remote: "http://js.demo.snaphappi.com/svc/DEV/snappi-air/upload.php"
	            };
	            var dsCfg = {
	            		uploadHostLookup: baseurl + 'getUploadHost.php', 
	            		uploadHost: uploadHost.local,
	            		updateServer: '/snappi/debugPost',	// post url
	//            		updateServer: baseurl + 'test.php',
	            		syncServer: baseurl + 'syncstatus.php',
	            		setSyncAndSetDataUrl: baseurl + 'set_syncstatus.php',
	            };
            }
            var done = Test.setDatasource(dsCfg);                        
            this.uploadQueue = new SNAPPI.AIR.Flex_UploadAPI();
        },
        /*
         * inspect results after successful request
         */
        inspectAuditions: function(auditions){
            LOG("  >>>>    in check Auditions");
            var photo = auditions[0].Photo;
            for (var p in photo) {
                LOG("  >>>>    photo." + p + "=" + photo[p]);
            }
            var fix = auditions[0].Photo.Fix;
            for (var p in fix) {
                LOG("  >>>>    photo.Fix." + p + "=" + photo.Fix[p]);
            }
            
            
            /*
             * testing updatePhotoProperties()
             */
            LOG(">>>>>>>>>>>>  Testing updatePhotoProperties   <<<<<<<<<<<<<");
            for (var i = 0; i < 2; i++) {
                var photo = auditions[i];
                LOG('i=' + i + ', Audition.id=' + photo.id);
                LOG('Audition.Photo.DateTaken=' + photo.Photo.DateTaken);
                Test.updatePhotoProperties({
                    id: photo.id,
                    rating: i % 5 + 1
                });
            }
            
            /*
             * testing postData
             *
             * using this function in my cakephp controller.
             * 	function debugPost() {
             $this->autoRender=false;
             $this->log($this->data, LOG_DEBUG);
             echo "check logs for posted data";
             return;
             }
             *
             *
             */
            LOG(">>>>>>>>>>>>  Testing postData <<<<<<<<<<<<<");
            //Test.setUpdateServerUrl("http://gallery:88/snappi/debugPost");
            LOG(">>>>>>>>>>>> postUrl=" + Test.getUpdateServerUrl());
            var photoids = [auditions[0].id, auditions[1].id];
            var postCallback = {
                arguments: {
                    ids: photoids
                },
                success: function(e, arguments){
                    LOG(">>>>>>>>>>>> SUCCESS postData ids=" + arguments.ids);
                    LOG(">>>>>>>>>>>> SUCCESS postData msg=" + e);
                },
                failure: function(e, arguments){
                    LOG(">>>>>>>>>>>> FAILURE postData msg=" + e);
                }
            }
            Test.postData(photoids, postCallback);
            LOG(">>>>>>>>>>>> post submitted, ids=" + photoids);
            
            
            // 35016F35-656B-4DD1-8DCC-38563F2E2510 == "will not rotate.jpg"
            /*
             * testing getImgSrcBySize
             */
            for (var i in auditions) {
                //								LOG("******** .getImgSrcBySize() src="+auditions[i].Photo.Img.Src.Src);
                if (/will not rotate/.test(auditions[i].Photo.Img.Src.Src)) {
                    LOG("******** testing .getImgSrcBySize() for replace=true, rotate=6, id=" + auditions[i].id);
                    var callback = {
                        success: function(src, arguments){
                            LOG("******** testing .getImgSrcBySize() SUCCESS! check rotate for id=" + arguments.audition.id + " src=" + src);
                        },
                        failure: function(src, arguments){
                            LOG("******** testing .getImgSrcBySize() FAILURE! check rotate for id=" + arguments.audition.id + " src=" + src);
                        },
                        arguments: {
                            audition: auditions[i]
                        }
                    };
                    var result = Test.getImgSrcBySize(auditions[i].id, 'bm', {
                        replace: false,
                        rotate: 8,
                        callback: callback
                    });
                    break;
                }
            }
            
        },
        testUploadQueue: function(auditions){
            /*
             * testing addToUploadQueue
             * */
            photoids = [];
            for (var i in auditions) {
                photoids.push(auditions[i].id);
            }
            //before adding photos to upload queue set batch_id first
            var ts = Math.round(new Date().getTime() / 1000);
            Test.setBatchId('xx11ddffgghhhh22sdsk2');
            if (Test.addToUploadQueue(photoids)) {
                LOG('*** Photos Added To Upload Queue');
                var cb = {
                    success: function(e){
                        LOG("Upload Host = " + e);
                        Test.setUploadFilePOSTurl(e);
                        Test.startQueue();
                    },
                    failure: function(e){
                        LOG("**Error setUploadHostFromServer=" + e);
                    }
                };
                Test.setUploadHostFromServer(Test.dsCfg.uploadHostLookup, cb);
            }
            
        },
        /*
         * these are the tests that will be run
         */
        runTestSuite: function(auditions){
        
            LOG("*****************  beginning test suite  ***********************");
            
            LOG("Test.count(null), result=" + Test.count());
            LOG("Test.count('" + Test.ds.getConfigs().baseurl + "'), result=" + Test.count(Test.ds.getConfigs().baseurl));
            LOG("Test.count('*'), result=" + Test.count('*'));
            
            Test.inspectAuditions(auditions);
            Test.testUploadQueue(auditions);
            
            LOG("*****************  END test suite  ***********************");
        },
        
        setDatasource : function(cfg, fnContinue) { // OK
			var ds = this.ds;
			var isXHR = 0;
			LOG(cfg);
			this.dsCfg = cfg;
			if (isXHR && "xhr lookup") {
				/*
				 * get POST url to upload to image server dynamically, uses XHR
				 */
				var callback = {
					success : function(uploadHost, args) {
						LOG("Upload Host = " + uploadHost);
						// setUploadFilePOSTurl == setConfigs(uploadHost)
					// args.uploadQueue.setUploadFilePOSTurl(uploadHost);
					args.datasource.setConfigs( {
						uploadHost : cfg.uploadHost
					});
					LOG("uploader.setDatasource() callback OK");
					fnContine.call(this);
				},
				failure : function(e) {
					LOG("**Error setUploadHostFromServer=" + e);
				},
				scope : this,
				arguments : {
					datasource : ds,
					uploadQueue : this.flexUploadAPI
				}
				};
				ds.setUploadHostFromServer(cfg.uploadHostLookup, callback);
			} else {
				/*
				 * or just set POST url to upload to images directly
				 */
				ds.setConfigs( {
					uploadHost : cfg.uploadHost
				});
			}
			ds.setUpdateServerUrl(cfg.updateServer);
			ds.setSyncFromServerUrl(cfg.syncServer);
			ds.setSyncAndSetDataUrl(cfg.setSyncAndSetDataUrl);
			if (!isXHR) {
				LOG("uploader.setDatasource() OK");
				return true;
			} else {
				LOG("uploader.setDatasource() XHR request");
				return false;
			}
		},                
        go: function(datasource){
            var Test = SNAPPI.AIR.Test;
            
            Test.setConfigs({
                photosPerpage: 36,
				uploadQueuesPerpage: 10
            });
            
			var defaultCfg = Test.getConfigs();
			Test.ds.setDsPerpage(defaultCfg.photosPerpage);
			Test.uploadQueue.setPerpage(defaultCfg.uploadQueuesPerpage);
			LOG('>>> Test default config');
			LOG('     DsPagesize => '+defaultCfg.photosPerpage+'=='+Test.ds.getDsPerpage());
			LOG('     UQPagesize => '+defaultCfg.uploadQueuesPerpage+'=='+Test.uploadQueue.getPerpage());
 
                
                var roots = Test.getBaseurls();
                if (1==2 && roots.length == 0) {
                
                    
                    /*******************************
                 * 
                 * // import photos
                 */
				var callback = {
                    success: function(e){
						alert('addPhotos success');
                        LOG("******************   AFTER IMPORT PHOTOS **************************");
                        var roots = Test.getBaseurls();
                        LOG(roots);
                        Test.setBaseurl(roots[0]);
                        
                        
                        LOG("********************************************   Test.ds.getConfigs().baseurl, result=" + Test.ds.getConfigs().baseurl);
                        
                        Test.CastingCallRequest({
                            page: 1,
                            perpage: 20
                        }, Test.runTestSuite);
                    }, 
					failure: function(e) {
						alert('addPhotos failure');
						LOG(e);
					}
                }
                
                Test.addPhotos('U:\\TEMP\\photos\\rotate', callback);
            }
            
            
           // LOG(roots);
           // Test.setBaseurl(roots[0]);
            
                                 
            LOG("********************************************   Test.getBaseurl(), result=" + Test.getBaseurl());
            
            
            Test.CastingCallRequest({
                page: 1,
                perpage: 20
            }, Test.runTestSuite);
            
            
            
        }
        
    };
    
    /*
     * publish in global namespace
     */
    SNAPPI.AIR.Test = Test;
    LOG("SNAPPI.AIR.Test loaded");
            
}())
