package api
{
	import flash.desktop.*;
	import flash.display.NativeWindow;
	import flash.display.Stage;
	import flash.events.Event;
	import flash.events.NativeDragEvent;
	import flash.events.NativeWindowBoundsEvent;
	import flash.filesystem.File;
	import flash.geom.Rectangle;
	import flash.system.Capabilities;
	
	import mx.containers.Canvas;
	import mx.events.FlexEvent;
	import mx.events.ResizeEvent;
	import mx.utils.StringUtil;
	
	import spark.components.WindowedApplication;
	
	
	// javascript reference: flexAPI_UI, or _flexAPI_UI
	// Flex global: Config.UI
	public class UploaderUI
	{
		public var datasource:UploaderDatasource;
		private var dropTarget:Canvas;
		private var photoScanner:ImageScanner;
		
		public function isUploading():Boolean {
			return UploadFile.isUploading();
		}
		
		public function UploaderUI(datasource:UploaderDatasource, dropTarget:Canvas)
		{
			this.datasource = datasource;	
			this.dropTarget = dropTarget;
//			this.setUploadQueuePerpage = this._setUploadQueuePerpage;
		}
	
		/**
		 * App/Window methods
		 * */
		public function centerApp(evt:FlexEvent):void{
			var window:NativeWindow = (evt.target as WindowedApplication).stage.nativeWindow;
			try {
				var winsize:Array = this.datasource.cfg.winsize || [960,700];
			} catch (e:Error) {
				winsize = [960,700];
			}
			window.width = winsize[0];
			window.height = winsize[1];
			
			try {
				var winloc:Array = this.datasource.cfg.winlocation || [];
			} catch (e:Error) {
				winloc = [];
			}
			if(winloc.length==0){
				winloc.push((Capabilities.screenResolutionX - window.width) / 2);
				winloc.push((Capabilities.screenResolutionY - window.height) / 2);
			}
			window.x = winloc[0];
			window.y = winloc[1];
			
			window.orderToFront();
		}
		
		public function onResize(evt:ResizeEvent):void{
			var win:Stage = (evt.currentTarget as WindowedApplication).stage;
			//save it to config
			var winsize:Array = [win.stageWidth,win.stageHeight];
			var ret:Boolean = this.datasource.saveConfig("winsize", winsize);
		}
		
		public function onMove(evt:NativeWindowBoundsEvent):void{
			var win:NativeWindow = evt.target as NativeWindow;
			if(evt.type==NativeWindowBoundsEvent.MOVE){
				//save it to config
				var x:Number = evt.afterBounds.x;
				var y:Number = evt.afterBounds.y;
				if(x==-32000){ //it is internally used when minimized window
					x = evt.beforeBounds.x;
				}
				if(y==-32000){
					y = evt.beforeBounds.y;
				}
				
				var winlocation:Array = [x,y];
				this.datasource.saveConfig("winlocation",winlocation);
			}
		}		
		
		/**************************************************
		 * DragDrop
		 * */
		/*
		* start/stop native drag & drop functionality
		* params - accept one param
		* 		1. allowed as boolean if true then start else stop drag & drop
		* */
		public function nativeDDAllowed(allowed:Boolean):void{
			if(allowed){
				this.initDrapNDrop();
			}else{
				this.removeDrapNDrop();
			}	
		}
		
		public function setDropTarget(selector:String=null):void{
			selector = selector || '#drop-target';
			try {
				var domUploader:Object = Config.jsGlobal.SNAPPI.Y.one(selector);
				this.dropTarget.x = domUploader.getX();
				this.dropTarget.y = domUploader.getY();
				this.dropTarget.width = domUploader.get('offsetWidth');
				this.dropTarget.height = domUploader.get('offsetHeight');
//				Config.jsGlobal.firebugLog("dropTarget >>> ");				
//				Config.jsGlobal.firebugLog("X="+this.dropTarget.x+", Y="+this.dropTarget.y+", W="+this.dropTarget.width+", H="+this.dropTarget.height);				
			} catch (e:Error) {
			}						
		}
		
		//Drag-and-drop listeners
		private function initDrapNDrop():void{
			Config.Uploader.addEventListener(NativeDragEvent.NATIVE_DRAG_ENTER,onDragIn);
			Config.Uploader.addEventListener(NativeDragEvent.NATIVE_DRAG_OVER,onDragOver);
			Config.Uploader.addEventListener(NativeDragEvent.NATIVE_DRAG_DROP,onDrop);
			Config.Uploader.addEventListener(NativeDragEvent.NATIVE_DRAG_EXIT,onDragExit);					
		}
		private function removeDrapNDrop():void{
			Config.Uploader.removeEventListener(NativeDragEvent.NATIVE_DRAG_ENTER,onDragIn);
			Config.Uploader.removeEventListener(NativeDragEvent.NATIVE_DRAG_OVER,onDragOver);
			Config.Uploader.removeEventListener(NativeDragEvent.NATIVE_DRAG_DROP,onDrop);
			Config.Uploader.removeEventListener(NativeDragEvent.NATIVE_DRAG_EXIT,onDragExit);					
		}
		
		private function onDragIn(event:NativeDragEvent):void{
			event.preventDefault();
			this.dropTarget.visible = true;
			this.dropTarget.includeInLayout= true;
			var transferable:Clipboard = event.clipboard;
			if(transferable.hasFormat(ClipboardFormats.FILE_LIST_FORMAT)){
				var dropfiles:Array = transferable.getData(ClipboardFormats.FILE_LIST_FORMAT,ClipboardTransferMode.ORIGINAL_PREFERRED) as Array;
				var isDirectory:Boolean=false;
				for (var i:int = 0; i<dropfiles.length; i++){
					if (dropfiles[i].isDirectory) {
						isDirectory = true;
						break;
					};
				}		
				if(isDirectory){
					NativeDragManager.acceptDragDrop(this.dropTarget);				                   	
					NativeDragManager.dropAction = NativeDragActions.COPY;
				}else{
					NativeDragManager.dropAction = NativeDragActions.NONE;
				}
			}else{
				NativeDragManager.dropAction = NativeDragActions.NONE;
			}     
		}
		private function onDragOver(event:NativeDragEvent):void{
			event.preventDefault();
			var transferable:Clipboard = event.clipboard;
			if(transferable.hasFormat(ClipboardFormats.FILE_LIST_FORMAT)){
				var dropfiles:Array = transferable.getData(ClipboardFormats.FILE_LIST_FORMAT,ClipboardTransferMode.ORIGINAL_PREFERRED) as Array;
				var isDirectory:Boolean=false;
				for (var i:int = 0; i<dropfiles.length; i++){
					if (dropfiles[i].isDirectory) {
						isDirectory = true;
						break;
					};
				}				
				if(isDirectory){
					NativeDragManager.dropAction = NativeDragActions.COPY;
					try {
						Config.jsUploadQueue.showDropTarget();
					} catch (e:Error) {
						var j:int;
					}
				}else{
					NativeDragManager.dropAction = NativeDragActions.NONE;
				}	
			}else{
				NativeDragManager.dropAction = NativeDragActions.NONE;
			}
		}
		private function onDrop(event:NativeDragEvent):void{
			event.preventDefault();
			Config.jsUI = Config.jsGlobal.SNAPPI.AIR.uploadQueue;
			var transferable:Clipboard = event.clipboard;
			var dropfiles:Array = transferable.getData(ClipboardFormats.FILE_LIST_FORMAT,ClipboardTransferMode.ORIGINAL_PREFERRED) as Array;
			for (var i:int = 0; i<dropfiles.length; i++){
				if(dropfiles[i].isDirectory){
					// TODO: ONLY SUPPORTS FOLDERS RIGHT NOW
					//this.addPhotos(dropfiles[0].nativePath,null);
					Config.jsUI.onDrop.call(Config.jsUI, dropfiles[i].nativePath);
				} else {
					// we are NOT importing files yet
					continue;
				}
			}
		}
		private function onDragExit(event:NativeDragEvent):void{
			event.preventDefault();
			this.dropTarget.visible = false;
			this.dropTarget.includeInLayout= false;
			try {
				Config.jsUploadQueue.showDropTarget(false);
			} catch (e:Error) {
				var j:int;
			}			
		}		
		
		
		
		
		/*******************************************************
		 * Add Folder/Import
		 * */
		
		
		/**
		 * use FLEX folder component to browse Directory for root folder
		 * */
		public function selectRootFolder():void{
			var directory:File = File.documentsDirectory; //Default Browse Path is DocumentsDirectory
			try
			{
				directory.browseForDirectory("Add a Folder of Photos");
				directory.addEventListener(Event.SELECT, this.directorySelected);
			}
			catch (error:Error)
			{
				Config.logger.writeLog('Error',error.message);
			}
		}
		
		//On Browse Select Event Handler
		private function directorySelected(event:Event):void 
		{
			var directory:File = event.target as File;
			//			    root_folder.text = directory.nativePath;
			this.importPhotos(directory.nativePath, {
					arguments:{
						baseurl:directory.nativePath
					}
			});		    
		}	
		
		
		/**
		* Callback function, execute after finish of scanfolders queue
		*	- on complete, start the updateServerUrl() queue ??? 
		* */
		public function onScanFoldersComplete(resp:Object, params:Object=null):void{
			this.photoScanner.isScanning = false;
			// add to uploadQueue
			try {
				var baseurl:String = params ? params.baseurl : '';
				var uploader:Object = Config.jsGlobal.SNAPPI.AIR.uploadQueue;
				if (baseurl) uploader.onImportComplete(baseurl);
			} catch (e:Error) {
				trace("Error: onScanFoldersComplete");
			}
		}		
		
		/**
		 * 	RIGHT NOW, ONLY SUPPORT IMPORTING FOLDERS 
		 * from drop method
		 */
		public function importPhotos(nativePath:String, callback:Object=null):void{
			// check if nativePath = file or folder
			this.photoScanner = this.photoScanner || new ImageScanner();	// init imgscanner on importPhoto
			var scanner:Object = this.photoScanner;
			scanner.queueFolder(nativePath);
			if(!scanner.isScanning){
				// NOTE: should we still queue callback.success when the scanner is ALREADY scanning????
				var _callbackWrapper:Function = function(resp:Object, params:Object):void{
					this.onScanFoldersComplete(resp, params);
					if(callback){
						try {
						var context:Object = callback.scope ? callback.scope : this;
						var fn:Function = resp.success ? callback.success : callback.failure;
						fn.call(context, resp.message, callback.arguments || {});
						} catch (e:Error){}
					}
				}				
				var params:Object = callback ? callback.arguments : null;
				scanner.startScan(_callbackWrapper, this, params);
			}
		}
		
		public function cancelImport():Boolean{
			return this.photoScanner.cancelScan();
		}	
		
		
		public function getImportProgress(flag:String=''):Object{
			var ret:Object;
			try{
				var scanner:Object = this.photoScanner;
				ret = {
					'total': scanner.getTotalCount(),
						'scanned': scanner.scannedImages,
						'added':scanner.newImages, 
						'updated': scanner.updatedImages, 
						'existing': scanner.existingImages,
						'done': !scanner.isScanning
				};
			}catch(e:Error){
				ret = {
					'scanned': null,
					'added':null, 
					'updated': null, 
					'existing': null, 
					'done':!scanner.isScanning
				};
			}
			return ret;
		}	
		
		public function logImportProgress(flag:String=''):void {
			var progress:Object = Config.UI.getImportProgress();
			var output:String = '';
			output += "Scanning Photos: Total="+progress.total;
			output += ", % done="+Math.round(progress.scanned/progress.total*100);
			output += ", added="+progress.added;
			output += ", updated="+progress.updated;
			output += ", existing="+progress.existing;
			Config.jsGlobal.firebugLog(output);
			try {
				Config.jsUploadQueue.view_setImportTotalProgress();
			} catch (e:Error) {
				var i:int;
			}
		}
		
		/*************************************************************
		 * Uploader view and paging methods
		 * */
		public function setUploadQueuePerpage (uploadQueuesPerpage:Number):void {
			this.datasource.saveConfig('uploadQueuesPerpage',uploadQueuesPerpage + '');
		}
		
		/**
		* set batchId of Uploader
		* @params batchId string  - batchId (as timestamp) or null for all
		* return void
		*/
		public function setBatchId(batchid:String):void{
			//update batch_id in configs;
			this.datasource.cfg.batch_id = batchid;
			this.datasource.saveConfig('batch_id',batchid);
		}		
		
		public function getUploadQueuePerpage():int {
			var count:int = parseInt(this.datasource.cfg.uploadQueuesPerpage || '10',10);
			return count;
		}
		
		public function getConfigs():Object {	// deprecate
			return this.datasource.cfg;
		}
		
		public function getCountByStatus(status:String='all', batch_id:String='null', baseurl:String='null', op:String='='):int {
			var count:int = 0;
			try{
				//				SELECT count(*) as tot_items 
				//				FROM uploadQueues as uq ,photos as p 
				//				WHERE  p.id=uq.photo_id
				//				and p.base_url='C:\Users\michael\Pictures\importTest\Oregon'				
				var query:String = "SELECT count(*) as tot_items " +
					"FROM uploadQueues as uq, photos as p " +
					"WHERE  p.id=uq.photo_id ";
				if (batch_id == 'null')batch_id = this.datasource.cfg.batch_id || '';
				if (batch_id) query += " AND batch_id='" + batch_id + "'"; 
				if (baseurl && baseurl!=='null') query += " AND p.base_url='" + baseurl + "'"; 
				
				if(status!='all'){
					query = query + " AND status" + op + "'" + status + "'";
				}	
				var dt:Array = Config.sql.execQuery(query);
				if(dt && dt.length){
					count = dt[0]["tot_items"];
				}
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-getCountByStatus');
			}
			return count;
		}
		
		
		public function getPageItems(page:int,status:String='all',batch_id:String='',baseurl:String=''):Array{
			var pageitems:Array = [];
			try{
				var batch_id:String = this.datasource.cfg.batch_id || '';
				var perpage:int = this.getUploadQueuePerpage();
				// page should be 1-based, but somewhere there is a 0 bug
				var xpage:int = Math.max(page-1, 0);	
				xpage = perpage * xpage;
				var query:String = "SELECT uq.id,uq.photo_id,uq.batch_id,uq.status,p.rel_path,p.rating,p.tags" +
					" FROM uploadQueues as uq ,photos as p " + 
					" WHERE p.id=uq.photo_id";
				if (batch_id) query += " AND batch_id='" + batch_id + "'"; 
				if (baseurl) query += " AND p.base_url='" + baseurl + "'"; 
				if(StringUtil.trim(status).length>0 && status!='all'){
					query += " AND status='" + status + "'";
				}				   
				query += " ORDER BY uq.id LIMIT " + xpage + "," + perpage;
				var dt:Array = Config.sql.execQuery(query);
				dt = dt || [];
				pageitems = dt;
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-getPageItems');
			}
			Config.logger.writeJson("getPageItems",pageitems);
			return pageitems;
		}
		
		public function getItemsByStatus(status:String,batch_id:String='',op:String='='):Array{
			status = status || 'all';
			var arr:Array = [];
			try{
				var batch_id:String = this.datasource.cfg.batch_id || '';
				var query:String = "SELECT * FROM uploadQueues WHERE 1=1";
				if (batch_id) query += " AND batch_id='" + batch_id + "'"; 
				if(status!='all'){
					query += " AND status" + op + "'" + status + "'";
				}	
				var dt:Array = Config.sql.execQuery(query);
				if(dt && dt.length){
					arr = dt;
				}
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-getCurrentUploadStatus');
			}
			return arr;
		}
		
		public function getImgSrcBySize(id:String,size:String,options:Object):String{
			return SnappiImage.getImgSrcBySize(id, size, options);
		}
		public function checkImgSrcBySize(id:String,size:String):String{
			return SnappiImage.checkImgSrcBySize(id, size);
		}		
		
		
		/*******************************************************************************
		 * Add/Remove from upload queue
		 * */
		/*
		* To add photos to upload queue
		* It accept object of uuid or audition objects as an input and see the current
		* upload session if any active session found then append photos to it otherwise
		* start new upload session and initialize new batch_id to it
		* and then add photos to that upload session.
		* params - accept 1) array of photo_ids e.g. photos = ['photo_id','photo_id']
		*		2) batchId, usually unixtime as string
		* return - no of photos added
		* */			
		public function addToUploadQueue(photos:Array,batch_id:String):int{
			var tot_added:int=0;
			try{
				if(StringUtil.trim(batch_id).length==0){
					throw new Error("batch not found");
				}
				for(var i:int=0;i<photos.length;i++){
					var uuid:String;
					try {
						uuid = photos[i+''].id;
					} catch (e:Error) {
						uuid = photos[i+''];
					}
					// use LEFT JOIN on uploadQueues to prevent duplicate key
					var query:String = "SELECT p.id, p.asset_hash, uq.photo_id " +
						" FROM photos p " +
						" LEFT JOIN uploadQueues uq ON uq.photo_id=p.id " +
						" WHERE uq.photo_id IS NULL AND p.id='" + uuid + "'";
					var dt:Array = Config.sql.execQuery(query);
					if(dt && dt.length){
						var photo_id:String = dt[0]['id'];
						var asset_hash:String = dt[0]['asset_hash'];
						query = "INSERT INTO uploadQueues(photo_id,batch_id,status,updated_on,asset_hash) " + 
							" values("+
							"'" + photo_id +  "'" +
							",'" + batch_id +  "'" +
							",'pending'" + 
							",''" +
							",'" + asset_hash + "'" + 
							")";
						Config.sql.execNonQuery(query);
						tot_added++;		
					}	
				}		
				if(tot_added>0){
					//update batch_id in configs;
					//Config.Datasource.saveConfig('batch_id',batch_id);
				}
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-addToUploadQueue');
			}
			return tot_added;
		}
		
		/*
		* remove photos from upload queue 
		* params - accept one param as any array of photo_ids to remove e.g. photos = ['photo_id','photo_id']
		* return - no of photos removed from queue
		* */
		public function removeFromUploadQueue(photos:Array):int{
			var tot_removed:int=0;
			try{
				var query:String = "SELECT count(id) AS tot_avail FROM uploadQueues" + 
					" WHERE status!='done' AND updated_on=''" + 
					" AND photo_id in('" + photos.join("','") + "')";
				var dt:Array = Config.sql.execQuery(query);
				var tot_avail:int= (dt && dt.length)?dt[0]['tot_avail'] : 0;
				if(tot_avail>0){
					query = "DELETE FROM uploadQueues " +
						" WHERE status!='done' AND updated_on=''" + 
						" AND photo_id in('" + photos.join("','") + "')";
					Config.sql.execNonQuery(query);
					tot_removed = dt.length;
				}
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-removeFromUploadQueue');
			}
			return tot_removed;
		}
		
		/*
		* set status in upload queue
		* @params batchid, baseurl
		*/
		public function setUploadQueueStatus(newStatus:String='pending', oldStatus:String='failure', batchId:String=null, baseurl:String='null'):Boolean{
			try{
				// still to be done
				var query:String = "UPDATE uploadQueues	SET status='"+newStatus+"' WHERE 1=1 ";
				if (oldStatus) query += " AND status='"+oldStatus+"'";
				if (baseurl && baseurl!=='null') query += " AND uploadQueues.photo_id IN (SELECT id FROM photos WHERE base_url='"+baseurl+"')";
				if (batchId) query += " AND batch_id = '"+batchId+"'";				
				var dt:Array = Config.sql.execNonQuery(query);
			}catch(e:Error){
				return false;
				Config.logger.writeLog("Error",e.message + '-setUploadQueueStatus');
			}
			return true;
		}		
		/*************************************************************
		 * Upload File methods
		 * */		
		
		public function setUploadFilePOSTurl(host:String):void{
			this.datasource.saveConfig('uploadHost',host);
		}
		public function getUploadFilePOSTurl():String{
			return this.datasource.cfg.uploadHost;
		}		
		
		public function uploadFile(photo_id:String, handlers:Object, sessionId:String):void{
			var uploadCfg:Object = {
				photo_id : photo_id,
				handlers : handlers,
				sessionId: sessionId
			};				
			var furl:String = SnappiImage.checkImgSrcBySize(photo_id,'bp');
			if (furl) {
				// upload file exists, just post upload directly
				this.postUploadFile(furl, uploadCfg);
			} else {
				// create upload file async, THEN post upload from callback
				var resizeImg_Callback:Object = {
					resInCb : true,
					create : true,
					replace : false,
					autorotate : true,
					rotate : 1,
					callback : null		// uploadFile_Callback
				};
				var uploadFile_Callback:Object = {
					success : this.postUploadFile,
					failure : this.onImgFailure,
					scope : this,
					arguments : uploadCfg
				}
				resizeImg_Callback.callback = uploadFile_Callback;	
				// upload preview size
				furl = SnappiImage.getImgSrcBySize(photo_id,'bp',resizeImg_Callback);
				// start FileProgress while waiting for bp~uploadFile
			}
		}		
		
		private function postUploadFile(furl:String, params:Object):void{
			var handlers:Object = params.handlers;
			var sessionKey:String = params.sessionId ? params.sessionId : 'PHPSESSID=' + UUID.genUUID();
			try{
				var query:String = "select p.*, uq.batch_id from photos p JOIN uploadQueues uq on uq.photo_id = p.id where p.id='" + params.photo_id + "'";
				var asset:Array = Config.sql.execQuery(query);
				var json_exif:String = '';
				if(asset && asset.length){
					json_exif = asset[0]['json_exif'];
				}
				/*
				*	send ALL postdata for Asset to avoid sync 
				*/
				// TODO: rename to provider_account_id
				var provider_key:String = this.datasource.cfg.provider_key;
				var postparams:String = sessionKey
					+ '&data[isAIR]=1'  
					+ '&data[ProviderAccount][id]=' + provider_key 
					+ '&data[ProviderAccount][provider_name]=' + 'desktop'
					+ '&data[ProviderAccount][provider_version]=' + 'v1.0'
					+ '&data[ProviderAccount][provider_key]=' + provider_key 
					+ '&data[ProviderAccount][baseurl]=' +asset[0]['base_url'] 
					+ '&data[Asset][id]=' + asset[0]['id']						
					+ '&data[Asset][asset_hash]=' + asset[0]['asset_hash']
					+ '&data[Asset][batchId]=' + asset[0]['batch_id']
					+ '&data[Asset][rel_path]=' + asset[0]['rel_path']
					+ '&data[Asset][width]=' + asset[0]['width'] 
					+ '&data[Asset][height]=' + asset[0]['height'] 
					+ "&data[Asset][json_exif]=" + escape(json_exif);
				var filePostName:String = 'Filedata';
				// get express Upload groups
				var gids:String = Config.SNAPPI.AIR.XhrHelper.getExpressUploads();
				if (gids) postparams += "&data[groupIds]=" + gids;
				var f:File = new File(furl);
				// var uq:UploadFile = new UploadFile(f,postparams,handlers,filePostName);
				var uq:UploadFile = UploadFile.getUploadFile(f,postparams,handlers,filePostName);
				uq.startUpload();
			}catch(e:Error){
				handlers.uploadError_Callback.call(handlers,null, e.message);
			}	
		}
		private function onImgFailure(e:String,params:Object):void{
			try{
				var handlers:Object = params.handlers;
				var post_id:String = params.photo_id;
				handlers.uploadError_Callback.call(handlers,null, e);
			}catch(e:Error){
				handlers.uploadError_Callback.call(handlers,null, e);
			}	
		}

	}
}