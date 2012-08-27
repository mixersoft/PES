package api
{
	import api.Config;
	
	import flash.events.DataEvent;
	import flash.events.Event;
	import flash.events.HTTPStatusEvent;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.events.SecurityErrorEvent;
	import flash.events.TimerEvent;
	import flash.filesystem.File;
	import flash.net.URLRequest;
	import flash.net.URLVariables;
	import flash.net.URLRequestHeader;
	import flash.net.navigateToURL;
	import flash.utils.Timer;
	import com.adobe.serialization.json.JSON;
	
	public class UploadFile
	{
		
		private var file:File;
		private var handlers:Object = null;
		private var postparams:Object = null;
		private var assumeSuccessTimeout:Number = 0;
		private var filePostName:String = 'file';
		private var status:String = '';
		private var done:Boolean = false;
		private var serverDataTimer:Timer = null;
		private var assumeSuccessTimer:Timer = null;
		private var httpSuccess:Array = [];
		
		private static var UploadFileManager:Array = [];
		private static var uploadsSinceLastGC:int = 0;
		public static function isUploading():Boolean {
			// check if all uploads are done
			var allDone:Boolean = true;
			var uf:UploadFile = null;
			for (var i:int = 0; i<UploadFile.UploadFileManager.length; i++){
				uf = UploadFile.UploadFileManager[i];
				allDone = allDone && (uf.done || (!uf.done && uf.status == 'queued'));
			}			
			return !allDone;
		}
		
		public static var getUploadFile:Function = function (file:File, postparams:Object, handlers:Object, filePostName:String='file'):UploadFile {
			var uf:UploadFile = null;
			for (var i:int = 0; i<UploadFile.UploadFileManager.length; i++){
				uf = UploadFile.UploadFileManager[i];
				if (uf.done===true && uf.status != 'queued') {
					break;
				}
			}
			if (uf && uf.done===true) {	// reuse slot
				uf.file = file;
				uf.postparams = postparams;
				uf.handlers = handlers;
				uf.filePostName = filePostName;
				uf.status = 'queued';		
				uf.done = false;
			} else uf = new UploadFile(file,postparams,handlers,filePostName); 
			// check if we should run gc
			if (++UploadFile.uploadsSinceLastGC > Config.UPLOAD_GC_LIMIT) {
				Misc.startGCCycle();
				UploadFile.uploadsSinceLastGC = 0;
			};
			return uf;
		};
		
		
		public function UploadFile(file:File, postparams:Object, handlers:Object, filePostName:String='file')
		{
			this.file = file;
			this.postparams = postparams;
			this.handlers = handlers;
			this.filePostName = filePostName;
			this.status = 'queued';
			this.done = false;
			UploadFile.UploadFileManager.push(this);
		}
		/*
		* called from UploaderUI.postUploadFile()
		*/
		public function startUpload():void{
			try{
				if(this.file.exists){
					this.file.addEventListener(Event.OPEN, this.Open_Handler);
					this.file.addEventListener(Event.CANCEL, this.Cancel_Handler);					
					this.file.addEventListener(ProgressEvent.PROGRESS, this.FileProgress_Handler);
					this.file.addEventListener(IOErrorEvent.IO_ERROR, this.IOError_Handler);
					this.file.addEventListener(SecurityErrorEvent.SECURITY_ERROR, this.SecurityError_Handler);
					this.file.addEventListener(HTTPStatusEvent.HTTP_STATUS, this.HTTPError_Handler);
					this.file.addEventListener(Event.COMPLETE, this.Complete_Handler);
					this.file.addEventListener(DataEvent.UPLOAD_COMPLETE_DATA, this.ServerData_Handler);
					var req:URLRequest = new URLRequest(Config.UI.getUploadFilePOSTurl());
//Config.jsGlobal.firebugLog(req.url);
//Config.jsGlobal.firebugLog(this.postparams);
					req.method = 'POST';
					var postData:URLVariables = new URLVariables();
//					var postQueryString:String = 'CAKEPHP='+this.postparams['CAKEPHP'];
					var postQueryString:String = postQueryString='';					
					for (var p:String in this.postparams) {
						postData[p] = this.postparams[p];
						postQueryString += '&'+p+'='+this.postparams[p];
					}
					req.data = postQueryString;

					req.manageCookies = true;
					this.status = 'uploading';
//					UploadFile.isUploading = true;
					
//					var sid:String = Config.SNAPPI.AIR.XhrHelper.getCookie('CAKEPHP');
//					Config.SNAPPI.AIR.XhrHelper.setCookies({'CAKEPHP':Config.SNAPPI.DATASOURCE.sessionId});
//					sid = Config.SNAPPI.AIR.XhrHelper.getCookie('CAKEPHP');
					
					// a normal POST keeps the correct Session
//					navigateToURL(req);
					this.file.upload(req, this.filePostName, false);
					
					this.handlers.uploadStart_Callback.call(this.handlers,this);
				}else{
					throw new Error("File not exists");
				}	
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message+'-FileUpload');
				this.handlers.uploadError_Callback.call(this.handlers,this.file, e.message);
			}
		}
		private function Open_Handler(event:Event):void {
			this.handlers.uploadProgress_Callback.call(this.handlers,this.file, 0, this.file.size);
		}
		
		private function FileProgress_Handler(event:ProgressEvent):void {
			// On early than Mac OS X 10.3 bytesLoaded is always -1, convert this to zero. Do bytesTotal for good measure.
			//  http://livedocs.adobe.com/flex/3/langref/flash/net/FileReference.html#event:progress
			var bytesLoaded:Number = event.bytesLoaded < 0 ? 0 : event.bytesLoaded;
			var bytesTotal:Number = event.bytesTotal < 0 ? 0 : event.bytesTotal;
			
			// Because Flash never fires a complete event if the server doesn't respond after 30 seconds or on Macs if there
			// is no content in the response we'll set a timer and assume that the upload is successful after the defined amount of
			// time.  If the timeout is zero then we won't use the timer.
			if (bytesLoaded === bytesTotal && bytesTotal > 0 && this.assumeSuccessTimeout > 0) {
				if (this.assumeSuccessTimer !== null) {
					this.assumeSuccessTimer.stop();
					this.assumeSuccessTimer = null;
				}
				
				this.assumeSuccessTimer = new Timer(this.assumeSuccessTimeout * 1000, 1);
				this.assumeSuccessTimer.addEventListener(TimerEvent.TIMER_COMPLETE, AssumeSuccessTimer_Handler);
				this.assumeSuccessTimer.start();
			}
			this.handlers.uploadProgress_Callback.call(this.handlers, this.file, bytesLoaded, bytesTotal);
		}
		
		private function AssumeSuccessTimer_Handler(event:TimerEvent):void {
			this.UploadSuccess(this.file, "", false);
		}

		private function Complete_Handler(event:Event):void {
			/* Because we can't do COMPLETE or DATA events (we have to do both) we can't
			 * just call uploadSuccess from the complete handler, we have to wait for
			 * the Data event which may never come. However, testing shows it always comes
			 * within a couple milliseconds if it is going to come so the solution is:
			 * 
			 * Set a timer in the COMPLETE event (which always fires) and if DATA is fired
			 * it will stop the timer and call uploadComplete
			 * 
			 * If the timer expires then DATA won't be fired and we call uploadComplete
			 * */
			
			// Set the timer
			if (this.serverDataTimer != null) {
				this.serverDataTimer.stop();
				this.serverDataTimer = null;
			}
			
			this.serverDataTimer = new Timer(100, 1);
			//var self:SWFUpload = this;
			this.serverDataTimer.addEventListener(TimerEvent.TIMER, this.ServerDataTimer_Handler);
			this.serverDataTimer.start();
		}
		private function ServerDataTimer_Handler(event:TimerEvent):void {
			this.UploadSuccess(this.file, "");
		}
		
		private function ServerData_Handler(event:DataEvent):void {
			this.UploadSuccess(this.file, event.data);
		}
		
		private function UploadSuccess(file:File, serverData:String, responseReceived:Boolean = true):void {
			if (this.serverDataTimer !== null) {
				this.serverDataTimer.stop();
				this.serverDataTimer = null;
			}
			if (this.assumeSuccessTimer !== null) {
				this.assumeSuccessTimer.stop();
				this.assumeSuccessTimer = null;
			}
			try {
				var response:Object = JSON.decode(serverData);		// error
			} catch (e:Error) {
				if (!response) {
					Config.jsGlobal.firebugLog("JSONParse Error, check Configure::read(debug) == 0");		
					response = {success:false, message:'JSONParse Error, check Configure::read(debug)==0'};
				}
				response.success = false;
Config.jsGlobal.firebugLog("JSONParse Error, raw="+serverData);				
			}
			responseReceived = response.success == true;
			if (response.success == false) {
				this.status = 'error';
				this.handlers.uploadError_Callback.call(this.handlers,file, response.message);
			} else {
				this.status = 'success';
				//ExternalCall.UploadSuccess(this.uploadSuccess_Callback, file.ToJavaScriptObject(), serverData, responseReceived);
				this.handlers.uploadSuccess_Callback.call(this.handlers,file, serverData, responseReceived);
			}

			this.UploadComplete(false);
			
		}

		private function HTTPError_Handler(event:HTTPStatusEvent):void {
			var isSuccessStatus:Boolean = false;
			for (var i:Number = 0; i < this.httpSuccess.length; i++) {
				if (this.httpSuccess[i] === event.status) {
					isSuccessStatus = true;
					break;
				}
			}
			if (isSuccessStatus) {
				var serverDataEvent:DataEvent = new DataEvent(DataEvent.UPLOAD_COMPLETE_DATA, event.bubbles, event.cancelable, "");
				this.ServerData_Handler(serverDataEvent);
			} else {
				status = "error";
				//ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_HTTP_ERROR, this.current_file_item.ToJavaScriptObject(), event.status.toString());
				this.handlers.uploadError_Callback.call(this.handlers,this.file, event.status.toString());
				this.UploadComplete(true); 	// An IO Error is also called so we don't want to complete the upload yet.
			}
		}
		
		// Note: Flash Player does not support Uploads that require authentication. Attempting this will trigger an
		// IO Error or it will prompt for a username and password and may crash the browser (FireFox/Opera)
		private function IOError_Handler(event:IOErrorEvent):void {
			// Only trigger an IO Error event if we haven't already done an HTTP error
			if (this.status != "error") {
				this.status = "error";
				//ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_IO_ERROR, this.current_file_item.ToJavaScriptObject(), event.text);
				this.handlers.uploadError_Callback.call(this.handlers,this.file, event.text);
			}
			this.UploadComplete(true);
		}

		private function SecurityError_Handler(event:SecurityErrorEvent):void {
			this.status = "error";
			//ExternalCall.UploadError(this.uploadError_Callback, this.ERROR_CODE_SECURITY_ERROR, this.current_file_item.ToJavaScriptObject(), event.text);
			this.handlers.uploadError_Callback.call(this.handlers,this.file, event.text);
			this.UploadComplete(true);
		}		
		// Completes the file upload by deleting it's reference, advancing the pointer.
		// Once this event fires a new upload can be started.
		private function UploadComplete(eligible_for_requeue:Boolean):void {
			//var jsFileObj:Object = this.current_file_item.ToJavaScriptObject();
			this.removeFileReferenceEventListeners();

			/*if (!eligible_for_requeue || this.requeueOnError == false) {
				this.current_file_item.file_reference = null;
				this.queued_uploads--;
			} else if (this.requeueOnError == true) {
				this.current_file_item.file_status = FileItem.FILE_STATUS_QUEUED;
				this.file_queue.unshift(this.current_file_item);
			}*/
			

			//this.current_file_item = null;
			
			//this.Debug("Event: uploadComplete : Upload cycle complete.");
			//ExternalCall.UploadComplete(this.uploadComplete_Callback, jsFileObj);
			this.handlers.uploadComplete_Callback.call(this.handlers,this.file);
			this.done = true;
			this.status = this.status=='queued' ? null : this.status;
			
			var isUploading:Boolean = UploadFile.isUploading();
			if ( isUploading == false) {
				// fire JS event
				Config.jsGlobal.SNAPPI.AIR.UploadManager.fire_upload_status_changed(isUploading);
			}
			try {
				if (Config.jsGlobal.SNAPPI.AIR.UploadManager.DELETE_UPLOADED_IMAGES_FROM_APP_STORAGE) {
					this.file.deleteFile();
				}	
			}catch(e:Error){}
		}
		private function removeFileReferenceEventListeners():void {
			if (this.file!= null) {
				this.file.removeEventListener(Event.OPEN, this.Open_Handler);
				this.file.removeEventListener(Event.CANCEL, this.Cancel_Handler);
				this.file.removeEventListener(ProgressEvent.PROGRESS, this.FileProgress_Handler);
				this.file.removeEventListener(IOErrorEvent.IO_ERROR, this.IOError_Handler);
				this.file.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, this.SecurityError_Handler);
				this.file.removeEventListener(HTTPStatusEvent.HTTP_STATUS, this.HTTPError_Handler);
				this.file.removeEventListener(DataEvent.UPLOAD_COMPLETE_DATA, this.ServerData_Handler);
			}
		}
		public function Cancel_Handler():void {
			var pause:Boolean = (this.status == "pause") 
			var msg:String = pause ? "File Upload Paused." : "File Upload Cancelled.";
			this.handlers.uploadError_Callback.call(this.handlers, this.file, msg);
			this.UploadComplete(pause);			
		}
		public function cancel(pause:Boolean=false):void{
			try{
				if(this.file){
					this.status = pause ? "pause" : "cancelling";
					this.file.cancel();		// cancel this.file.upload()
Config.jsGlobal.firebugLog("WARNING: Cancelling active upload manually. Event.CANCEL not caught");	
					/*
					* TODO: cancel handler is not fired. why?
					*/
					this.Cancel_Handler();
				}
			}catch(e:Error){
				this.handlers.uploadError_Callback.call(this.handlers, this.file, "File Upload Cancelled.");
			}	
		}
		
	}
}