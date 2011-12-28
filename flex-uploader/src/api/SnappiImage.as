package api
{
	import com.adobe.serialization.json.JSON;
	
	import flash.desktop.NativeProcess;
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.NativeProcessExitEvent;
	import flash.filesystem.File;
	import flash.net.URLRequest;
	import flash.utils.setTimeout;
	
	public class SnappiImage
	{
		private var ld:Loader = null;
		private var f:File = null;
		private var url:String='';
		private var options:Object = null;
		private var size:String = '';
		
		public function SnappiImage()
		{
		}
		
		/**
		 * Return a valid File transposed into the applicationStorageDirectory
		 * 		original filepath = baseurl + File.separator + relpath;
		 * @params baseurl String - withOUT trailing File.separator
		 * @params relpath String - basepath + filename + extension
		 * @size String
		 * @return File
		 * */
		public static function getImgPathBySize(baseurl:String, relpath:String, size:String):File{
			/*
			* TODO: use application storage directory for real deployment
			* */
//			var storage:File = File.applicationDirectory;
			var storage:File = File.applicationStorageDirectory;
			var storagePath:String = storage.nativePath + File.separator + 'images' + File.separator;
			
			var orig:File = new File(baseurl + File.separator + relpath);
			var resizedFile:File = new File(orig.parent.nativePath + File.separator + size + '~'+ orig.name);
			// win32 adjustment
			var resizedPath:String = resizedFile.nativePath.replace(/\:/ig,'drive');
			var relocatedFile:File = new File( storagePath + resizedPath);
			return relocatedFile;
		}
		
		// snappi.uploadFile > snappi.postUploadFile > UploadFile.startUpload
		public static var checkImgSrcBySize:Function = function (id:String,size:String):File{
			try{
				var params:Array = [{name:"@id",value:id}];
				var query:String = "SELECT base_url, rel_path FROM photos WHERE id=@id";
				var dt:Array = Config.sql.executeQueryParams(query,params); 
				// var dt:Array = Config.sql.execQuery(query);
				if(dt && dt.length){
					var baseurl:String = dt[0]['base_url'];
					var relpath:String = dt[0]['rel_path'];
					var resized:File = SnappiImage.getImgPathBySize(baseurl, relpath, size);
					if(resized.exists) {
						return resized;	
					}
				}	
			}catch(e:Error){
			}
			return null;
		}		
		/**
		* get Image Source path by size AND 
		* 		creates image (according to input options) on a different thread if it doesn't exist
		* @params photo_id uuid
		* @params size string = [bp|bm|bs|tn|sq]
		* 		sq = 75 x 75 px
		* 		tn = 100px
		* 		bs = 240px
		* 		bm = 320px
		* 		bp = 640px
		* @params options object = {
		* 		create : true/false, 		- create if file does not exist
		*		replace : true/false, 		- replace existing file, default=false 
		* 		autorotate : true/false,	
		* 		rotate : 2,	 				- additional rotate AFTER possible autorotate
		* 		callback : {
		* 			success : function(e){},
		* 			failure : function(e){},
		* 			arguments : {},
		* 			scope : scope object of class		
		* 			}
		* 		}					
		* 	} 
		* @return : returns absolute url of the photo based on size e.g. file:///D:/downloads/1000pics/xyz.jpg
		* */			
		public static var getImgSrcBySize:Function = function (id:String, size:String, options:Object):File{
			var f:File;
			
			// check if file by imageSize already exists, if so, return response directly
			f = SnappiImage.checkImgSrcBySize(id, size);
			if (f) return f;
			
			var callback:Object = null; 
			var params:Object = {}; 
			callback = options.callback || null;
			params = callback.arguments || {}; 
			try{
				var query:String = "SELECT base_url, rel_path, json_exif, rotate FROM photos WHERE id=@id";
				var params2:Array = [{name:"@id",value:id}];
				var dt:Array = Config.sql.executeQueryParams(query,params2); 
				//var dt:Array = Config.sql.execQuery(query);
				if(dt && dt.length){
					var baseurl:String = dt[0]['base_url'];
					var relpath:String = dt[0]['rel_path'];			
					var json_exif:Object = JSON.decode(dt[0]['json_exif'] || '{}');	
					json_exif.rotate = dt[0]['rotate'];	// pass rotate param to getCalculatedRotate()
					var path:String = baseurl + File.separator + relpath;
					var orig:File = new File(path);
//					orig = new File(Misc.normalizeFilePath(path));
					if(orig.exists){
						var resized:File = SnappiImage.getImgPathBySize(baseurl, relpath, size);
						if (Config.USE_IMAGEMAGICK_RESIZE) {
							// ImageMagick resize
							SnappiImage.IM_resizeOrRotateImage(orig, resized, size, options, json_exif);
							return resized;
						} else {
							// FLASH resize orig,resized,size,options
							SnappiImage.FLASH_prepareResizeOrRotate(orig, resized, size, options, json_exif);
						}
					}
				}else{
					throw new Error('Photo not found with id=' + id);
				}	
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-getImgSrcBySize');
				if(callback){
					callback.failure.call(callback.scope || Config.jsGlobal,e.message,params);
				}
			}
			return null;
		}
		/**
		 * @params orientation int - [ 1 | 3 | 6 | 8 ] exifOrientation, used for autoRotate
		 * @params rotate int - [ 1 | 3 | 6 | 8 ] manual override, after autoRotate
		 * @return int [ 1 | 3 | 6 | 8 ]
		 * */	
		public static var getCalculatedRotate:Function = function(orientation:int, rotate:int):int {
			orientation = orientation || 1;
			rotate = rotate || 1;
			if( rotate==1 || orientation==1 ){
				rotate = rotate * orientation;
			}else if((rotate+orientation)==6 || (rotate+orientation)==14 ){
				rotate = 1;
			}else if((rotate+orientation)==12 || (rotate+orientation)==16){
				rotate = 3;
			}else if((rotate+orientation)==9){
				rotate = 8;
			}else if((rotate+orientation)==11){
				rotate = 6;
			}
			return rotate;
		}
		
		// use NativeProcess Imagemagick to resize/rotate
		public static var IM_resizeOrRotateImage:Function = function(src:File, dest:File, size:String, options:Object, json_exif:Object):void{
			if (NativeProcess.isSupported){
				// file:///Users/snaphappi/Library/Preferences/snaphappi-uploader/Local%20Store/images/Volumes/SNAPPI2/folder%20with%20special%20char%20(%3B'&.=%5E%25$%23@!)%20test/sq~P1030448.JPG
				// var dest:File = new File(url);   // pass as File object, not String
				var callback:Object = options.callback;
				var args:Object = callback.arguments || {};
				
				var rotate:int = 1;
				var orientation:int = 1;
				if (options.autorotate) {
					// assume json.exifOrientation changed to 1 after autorotate	
					orientation = 1;
				} else {
					orientation = json_exif && json_exif.Orientation ? json_exif.Orientation : 1;
				}
				if (options.rotate) {
					rotate = options.rotate;	// manual setting takes priority	
				} else {		// then exif rotate
					rotate = json_exif && json_exif.rotate ? json_exif.rotate : 1;
				}
				options.rotate = SnappiImage.getCalculatedRotate(orientation, rotate);
				
				
				var onExit:Function = function(e:NativeProcessExitEvent):void{
					var context:Object = callback.scope || Config.jsGlobal;
					if (e.exitCode) {
						var errorMsg:String = "convert.exe process exited with exit code=" + e.exitCode;
						callback.failure.call(context, errorMsg, args);
						Config.logger.writeLog("Error", errorMsg + '-IM-resizeOrRotateImage');
					} else {
						// exit 0
						callback.success.call(context, dest, args);	
					}
				}
					
				var onIOError:Function = function(e:IOErrorEvent):void{
					var context:Object = callback.scope || Config.jsGlobal;
					callback.failure.call(context, e.toString(), args);
					Config.logger.writeLog("Error", e.toString() + '-IM-resizeOrRotateImage');
				}
					
				if ("use MAX_CONCURRENT_PROCESSES") {
					MagickUtils.enQueue(src, dest, size, options, onExit, onIOError);
				} else {
					// DO NOT USE QUEUE TO LIMIT convert processes
//					// using imageMagick, configure NativeProcess
//					var magik:MagickUtils = new MagickUtils(
//						src, dest, 
//						onExit,	onIOError, null, null	// callbacks 
//					);		
//					// run convert with NativeProcess
//					magik.convert(null, null, size, options);
				}
				
			} else {
				// use flash as fallback
				setTimeout(SnappiImage.FLASH_resizeOrRotateImage,50,src, dest,size,options);
			}
		}		
			
		/***********************************************************
		 * FLASH resize methods
		*/
		public static var FLASH_prepareResizeOrRotate:Function = function(src:File, dest:File, size:String, options:Object, json_exif:Object):void{
			// FLASH resize
			var destUrl:String = dest.url;
			var has2Create:Boolean = false;
			
			// var dest:File = new File(destUrl);
			var rotate:int = 1;
			var orientation:int = options.autorotate && json_exif && json_exif.Orientation ? json_exif.Orientation : 1;
			if (options.rotate) {
				rotate = options.rotate;	
			} else {
				rotate = json_exif && json_exif.rotate ? json_exif.rotate : 1;
			}
			options.rotate = SnappiImage.getCalculatedRotate(orientation, rotate);
			
			var callback:Object = options.callback || null; 
			var params:Object = callback.arguments || {}; 
			var context:Object = callback.scope || Config.jsGlobal;

			if (options.replace || (options.create && dest.exists == false)){
				setTimeout(SnappiImage.FLASH_resizeOrRotateImage, 50, src, dest, size, options);	
			} else {
				try { 	// assume Image already exists, callback.success()
					callback.success.call(context, destUrl, params);				
				} catch (e:Error) {
					Config.logger.writeLog("Error",e.message + '-FLASH-resizeImage');
					callback.failure.call(context, e.message,params);
				}
			}
		}
		
		public static var FLASH_resizeOrRotateImage:Function = function(src:File, dest:File, size:String, options:Object):void{
			// change to Static method with closures
			var destUrl:String = dest.url;
			var callback:Object = options.callback;
			var params:Object = callback.arguments || {};
			var context:Object = callback.scope || Config.jsGlobal;

			var ld:Loader = new Loader();
			// callback for async img load
			var FLASH_resizeOrRotateLoadedImage:Function = function (e:Event):void {
				e.target.removeEventListener(e.type, arguments.callee);
				try {
					// var dest:File = new File(destUrl);
					var bd:BitmapData = Bitmap( ld.content).bitmapData;
					var bd1:BitmapData = Misc.resizeIt(bd, Misc.getImgSize(size), options.rotate);
					Misc.saveJPG(bd1, dest, Misc.getJPGcompression());
					callback.success.call(context, destUrl, params);
				} catch (e:Error) {
					Config.logger.writeLog("Error",e.message + '-onLoadImage');
					callback.failure.call(context, e.message,params);
				}
			}
				
			try {
				ld.contentLoaderInfo.addEventListener(Event.COMPLETE, FLASH_resizeOrRotateLoadedImage);
				ld.load(new URLRequest(src.url));				
			} catch (e:Error) {
			 	Config.logger.writeLog("Error",e.message + '-FLASH-resizeImage');
				callback.failure.call(context, e.message,params);
			}
		}
	}
}
