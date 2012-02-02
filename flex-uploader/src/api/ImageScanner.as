package api
{
	import api.Config;
	import api.SqlHandler;
	
	import com.adobe.crypto.MD5;
	import com.adobe.serialization.json.JSON;
	
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.filesystem.File;
	import flash.net.URLRequest;
	import flash.utils.clearTimeout;
	import flash.utils.setTimeout;
	
	import jp.shichiseki.exif.*;
	
	import mx.utils.StringUtil;
	/*
	* ImageScanner class search images into the root folder as async queue
	*/
	public class ImageScanner
	{
		private var queuedFolders:Array;
		private var queuedFolderIndex:int;
		private var supported_pics:RegExp = /(\.jpg)$/i;
		private var curr_baseurl:String;
		private var sql:SqlHandler;
		private var scanTimer:uint;
		private var cb:Function;
		private var scope:Object;
		private var params:Object;
		private var exifLoader:ExifLoader;
		public var newImages:int = 0;
		public var updatedImages:int = 0;
		public var existingImages:int = 0;
		public var scannedImages:int = 0;
		public var isScanning:Boolean=false;
		private var fileOrFolders:Array = [];
		private var folders:Array = [];
		public var totalCount:Object = {};
		private static var timers:Object = {};
		private var fileOrFolderIndex:int = -1;
		private var subTimer:uint;
		
		/**************************************************************
		 * getAssetHash
		 *	- create a unique asset_hash to allow duplicate photo detection 
		 *  - check uniqueness across local desktop only 
		 * 
		 * NOTE: use SAME implementation as php_lib: getAssetHash()
		 * PHP exif_read_data() 		<-> extractExifInfo_shichiseki()
		 * [ExposureTime] => 10/600   	<->	0.016666666666666666
		 * [FNumber] => 33/10			<->	3.3
		 * [GPSLongitude] => Array		<->	45,34,17.19
                (
                    [0] => 45/1
                    [1] => 34/1
                    [2] => 1719/100
                )
		 * [GPSLongitude] => Array		<->	6,46,43.92
                (
                    [0] => 6/1
                    [1] => 46/1
                    [2] => 4392/100
                )
		 */
		public function getAssetHash(f:File,json_exif:Object):String{
			//if datetimeOriginal = null or exif is null, then
			var provider_key:String = Config.Datasource.cfg.provider_key;

			/**
			 *  create unique MD5 asset_hash:
			 * 		provider_key (desktop UUID) 
			 * 		+ filename
			 * either: 
			 * 		+ json_exif.DateTimeOriginal 
			 * 			+ json_exif.Make
			 * 			+ json_exif.Model
			 * 			+ json_exif.ISOSpeedRatings
			 * 			+ json_exif.ExposureTime
			 * 			+ json_exif.FNumber
			 * 			+ json_exif.ImageUniqueID
			 * 			+ json_exif.Latitude
			 * OR
			 * 		+ f.creationDate 
			 * 			+ file size
			 */ 
			var asset_hash:String = provider_key + f.name;
			if(StringUtil.trim(json_exif.DateTimeOriginal).length==0 || json_exif.xfaltuIsNull){
				asset_hash += Misc.convertDateStr(f.creationDate);
				asset_hash += f.size;
			}else{
				//Installed desktop's uuid, datetimeOriginal,  Make, Model,  ExposureTime or null,  shutterSpeedValue or null, ApertureValue or null
				var keys:Array = [
					'DateTimeOriginal', 
					'Make', 
					'Model', 
					'ISOSpeedRatings',
					'FNumber',
					'ExposureTime', 
					'ImageUniqueID',
					'GPSLongitude',
				];
				for (var i:String in keys){
					if (json_exif.hasOwnProperty(keys[i])) {
						asset_hash += this.serialize_exif(json_exif[keys[i]]);
					} else asset_hash += '~';
				}
			}
//trace(asset_hash);
			return applyMd5(asset_hash);
		}
		public function serialize_exif(value:Object=null):String {
			if (typeof(value)=='object' && value!==null) {
				var s:String = '';
				for (var i:String in value) {
					s += serialize_exif(value[i]);
				}
//			} else if (var matches:Array = value.match(/(\n*)\/(\n*)/g)) {
//				s = (matches[0]/matches[1]).toString();
			} else if (!value && value !== 0) {
				s = '~';
			} else if (typeof(value)=='number') {
				s = value.toString().substr(0,17);
			} else s = value.toString();
			return s;
		}
		
		public function applyMd5(source:String):String{
			return MD5.hash(source);
		}		
		
		
		
		
		
		public function ImageScanner(){
			this.isScanning = false;
			this.queuedFolders = new Array();	// array of Files, represents top level import Folders
			this.queuedFolderIndex = -1;
			this.curr_baseurl = ''; 
			this.sql = Config.sql;//;new SqlHandler('app:/db/','snaphappi.db3');
		}
		
		/**
		 * @nativePath folder String = File.nativePath
		 *	- Set "baseurl" folder in a queue to scan images
		 * 	- save to local_stores DB table
		 * */		
		public function queueFolder(nativePath:String):void{
			//search scan folder already not in process
			//then add to scan queue
			var folder:File = new File(nativePath);
			if(!this.isInScanQueue(folder)){
				//save root folder info at db 
				//after success add to queue to scan 
				if(this.saveLocalStore(nativePath)){
					this.queuedFolders.push(folder);
				}	
			}
		}
		
		
		/*
		// IMPORT FILES (NOT FOLDERS)
		private var files2Add:Object = {};
		private var baseurls:Array = new Array();
		public function setFiles(photos:Array):void{
			for(var i:int=0;i<photos.length;i++){
				var f:File = new File(photos[i]);
				var baseurl:String = this.getBaseUrl(f);
				if(f.isDirectory){
					 
				}else{
						
				}
			}
		}
		private function getBaseUrl(f:File):String{
			var baseurl:String = f.parent.url;
			//check it not in baseurls list then add it
			for(var j:int=0;j<baseurls.length;j++){
				
			}
			return baseurl;
		}
		*/
		//save root folder info at db
		public function saveLocalStore(base_url:String):Boolean{
			var flag:Boolean = true;
			try{
				var params : Array = [];
				params.push({name:"@base_url",value:base_url});
				var query:String = "SELECT * FROM local_stores WHERE base_url=@base_url";
				var dt:Array = Config.sql.executeQueryParams(query,params); 
				// var dt:Array = this.sql.execQuery(query);
				var alreadyExists:Boolean = (dt && dt.length);
				if(!alreadyExists){
					query = "INSERT INTO local_stores(base_url) VALUES (@base_url)"
					Config.sql.executeNonSQLParams(query,params);
				}
				flag = true;
			}catch(e:Error){
				flag = false;
				Config.logger.writeLog("Error",e.message);		
			}
			return flag;
		}
		public function isInScanQueue(folder:File):Boolean{
			return (this.queuedFolders.indexOf(folder,0)>=0);
		}
		public function startScan(cb:Function = null, scope:Object = null, params:Object = null):void{
			this.isScanning = true;
			this.cb = cb;
			this.scope = scope;
			this.params = params;
			this.queuedFolderIndex = 0;
			this.newImages = 0;
			this.updatedImages =0;
			this.existingImages = 0;
			this.scannedImages =0;		
			this.totalCount = {};
			this.folders = [];
			Config.jsUploadQueue.showImportProgress();
			this.scanTimer = setTimeout(this.doScan,10); //starts in new thread
		}
		public function cancelScan():Boolean{
			if (this.isScanning){
				try {
					this.isScanning = false;
					this.queuedFolderIndex = this.queuedFolders.length;	// stops a next Subfolder
					Config.UI.logImportProgress();
					return true;
				} catch (e:Error) {}
			} 
			return false;
		}		
		// for topLevel, queued folders only
		public function doScan():void{
//Config.jsGlobal.firebugLog("doScan");			
			clearTimeout(this.scanTimer);
			if(this.queuedFolderIndex<this.queuedFolders.length){
				var folder:File = this.queuedFolders[this.queuedFolderIndex];
				if(folder.exists){
					this.curr_baseurl = folder.nativePath;
					//Config.Uploader.root_folder.text = folder;
					this.updateLastSelected(this.curr_baseurl);
					var self:ImageScanner = this;
					this.prepareFolder(folder, false, function():void{
//						Config.jsGlobal.firebugLog(Config.Uploader.getImportProgress());
						self.nextFolder();	// process next queued folder
					} );
				}
			}else{
				this.isScanning = false;
				//finish all scanning
				if(typeof(this.cb)=='function'){
					// TODO: check this.queuedFolders, response should be array of scanned Folders
					var count:int = 0;
					for (var j:int = 0; j<this.queuedFolders.length; j++) {
						count += this.totalCount[this.queuedFolders[j].nativePath];	
					}
					
					var resp:Object = {
						success: true,
						message: 'scan folder complete',
						response: {
//							baseurl:this.params.baseurl,
							count: count,
							folders: this.queuedFolders
						}
					}
					this.cb.call(this.scope, resp, this.params);	// callback to UploaderUI.importPhotos()
				}
			}
		}
		
		
		public function updateLastSelected(base_url:String):void{
			try{
				var query:String = "update local_stores set last_selected=0 where 1=1";
				this.sql.execNonQuery(query);
				query = "update local_stores set last_selected=1 where base_url=@base_url";
				var params:Array = [{name:"@base_url",value:base_url}];
				Config.sql.executeNonSQLParams(query,params); 
				// this.sql.execNonQuery(query);	
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-while update lastSelected');
			}
		}
		// top level, queued folder only
		private function nextFolder():void{
			this.queuedFolderIndex++;
			this.scanTimer = setTimeout(this.doScan,1); //starts in new thread
		}
		
		

		public var getTotalCount:Function = function():int {
			var total:int = 0;
			for (var i:String in this.totalCount) {
				total += this.totalCount[i];
			}
			return total;
		}
			
		/**
		 * for top level of recursion, start with isCounting==false
		 * */
		public function prepareFolder(f:File, isCounting:Boolean, fnComplete:Function=null):void{
			if (this.isScanning = false ) {	// TODO: this line makes no sense
				if (fnComplete is Function) fnComplete();
				return;
			}
			if (f.exists == false || f.isDirectory == false ) {
				// throw an exception?
				return;
			}
			this.folders.push(f);	// this.folders == list of ALL scanned folders, including all subfolders
			
			var unsortedFileOrFolders:Array = f.getDirectoryListing();
			var subFolders:Array = [];
			var files:Array = [];
			var sortedFileOrFolders:Array = [];
			var alreadyCounted:Boolean = this.totalCount[f.nativePath];
			
			for (var i:int=0 ; i<unsortedFileOrFolders.length; i++) {
				var test:File = unsortedFileOrFolders[i];
				if (test.exists && test.isDirectory == true){
					subFolders.push(test); 
				}  else if (test.exists && test.isDirectory == false) {
					files.push(test);
				}
			}			
			sortedFileOrFolders = files.concat(subFolders);
			if (isCounting) {
				// add to totalCount first
				this.totalCount[f.nativePath] = files.length;
				
				// recursively iterate through all subFolders, if this is the first time
				for (var j:int=0 ; j<subFolders.length; j++) {
					this.prepareFolder(subFolders[j], true);
				}				
				// we should now have an initial value for totalCount for CURRENT baseurl ONLY;
				// TODO: we need to continue to count other baseurls
				Config.UI.logImportProgress();
				trace("total count for import="+this.getTotalCount());

			} else {	
				// isCounting == false => start importing
				this.totalCount[f.nativePath] = files.length; 	// refresh totalCount 
				if (alreadyCounted) {
					// counted, now start importing
					// reset local import array to this subFolder
					// sorted Files then Folders for current folder only
					ImageScanner.timers[f.nativePath] = setTimeout(this.importFilesThenFolders,10, sortedFileOrFolders, f, fnComplete); 
				} else {
					// at top level/first iteration ONLY
					isCounting = true;
					// recursively iterate through all subFolders of top level folder
					for (var k:int=0 ; k<subFolders.length; k++) {
						this.prepareFolder(subFolders[k], true);
					}					
					// after recursive count is done, start importing
					ImageScanner.timers[f.nativePath] = setTimeout(this.importFilesThenFolders,10, sortedFileOrFolders, f, fnComplete); 
				}
			}
		}
		
		// scan files in this.fileOrFolders, should be sorted Files first, then Folders, see prepareFolder()
		private function importFilesThenFolders(filesThenFolders:Array, parent:File, fnComplete:Function):void{
//Config.jsGlobal.firebugLog("importFilesThenFolders");			
			if (parent) {
				clearTimeout(ImageScanner.timers[parent.nativePath]);
				delete ImageScanner.timers[parent.nativePath];
			}
			if (this.isScanning = false ) {
				if (fnComplete is Function) fnComplete();
				return;
			}			
			if (filesThenFolders.length == 0) {
				Config.UI.logImportProgress();	// done with this folder
				if (fnComplete is Function) fnComplete();
				return;
			}
			
			var fileOrFolder:File = filesThenFolders.shift();
			if (filesThenFolders.length % 10 == 0) {
//Config.jsGlobal.firebugLog("importFilesThenFolders >>> logImportProgress");				
				Config.UI.logImportProgress();
				// TODO: fire event to import into uploadQueue, 
				// but what about batchId????
			} 
			
			if (fileOrFolder.exists == false){
				this.importFilesThenFolders(filesThenFolders, null, fnComplete);
			} if ( fileOrFolder.isDirectory == false){
				// import this photo
				this.scannedImages++;
				ImageScanner.timers[fileOrFolder.nativePath] = setTimeout(this.saveImages,10, fileOrFolder, filesThenFolders, fnComplete);
			} else {
				// after all files have been imported, process folders, isCounting==false
				var self:ImageScanner = this;
				this.prepareFolder(fileOrFolder, false, 
					// when the fileOrFolder has be completely imported, then do next Folder
					function():void{	// use closure
						self.importFilesThenFolders(filesThenFolders, null, fnComplete);  // next folder, put on timer?
					}
				);
			}	
		}		
		
		public function saveImages(f:File, filesThenFolders:Array, fnComplete:Function):void{
//Config.jsGlobal.firebugLog("saveImages:"+f.nativePath);			
			if (f) {
				clearTimeout(ImageScanner.timers[f.nativePath]);
				delete ImageScanner.timers[f.nativePath];
			}			
			if (this.isScanning = false ) {
				if (fnComplete is Function) fnComplete();
				return;
			}			
			if(this.supported_pics.test('.'+f.extension)){
				var self:ImageScanner = this;
				if (1) {
					if (!this.exifLoader) {
						this.exifLoader = new ExifLoader();
					}
					var exifLoader:ExifLoader = this.exifLoader;	// closure
					// shichiseki Exif parser
					var onComplete_ExifLoader:Function = function (e:Event):void {
						exifLoader.removeEventListener(Event.COMPLETE, onComplete_ExifLoader);
						exifLoader.removeEventListener(IOErrorEvent.IO_ERROR, onParsingErrors);
						var json_exif:Object = ImageScanner.extractExifInfo_shichiseki(exifLoader.exif);
						try {
							self.saveImageInfoToDB(f, json_exif);
						} catch (e:Error) {
//							var displayIFD:Function = function (ifd:IFD):void {
//								trace(" --- " + ifd.level + " --- ");
//								for (var entry:String in ifd) {
//									trace(entry + ": " + ifd[entry]);
//								}
//							}	
//							 log exif
//							if (exifLoader.exif.ifds.primary)
//								displayIFD(exifLoader.exif.ifds.primary);
//							if (exifLoader.exif.ifds.exif)
//								displayIFD(exifLoader.exif.ifds.exif);
//							if (exifLoader.exif.ifds.gps)
//								displayIFD(exifLoader.exif.ifds.gps);
//							if (exifLoader.exif.ifds.interoperability)
//								displayIFD(exifLoader.exif.ifds.interoperability);								
						}
						self.importFilesThenFolders(filesThenFolders, null, fnComplete); // put on timer?
					}
					var onParsingErrors:Function = function (e:Event):void{
						exifLoader.removeEventListener(Event.COMPLETE, onComplete_ExifLoader);
						exifLoader.removeEventListener(IOErrorEvent.IO_ERROR, onParsingErrors);
Config.jsGlobal.firebugLog("saveImages.IOErrorEvent:"+e.toString());								
						Config.logger.writeLog("Error", '-onExifParsingErrors_shichiseki');
						self.importFilesThenFolders(filesThenFolders, null, fnComplete);
					}	
					
					this.exifLoader.addEventListener(IOErrorEvent.IO_ERROR, onParsingErrors);
					this.exifLoader.addEventListener(Event.COMPLETE, onComplete_ExifLoader);
					
					try {
						this.exifLoader.load(new URLRequest(f.url));
					} catch (e:Error) {
						var check:String;
					}					
				} else {
					// nabiro Exif parser
//					var onParsingErrors:Function = function (e:Event):void{
//						var exif:Exif = (e.target as Exif);
//						exif.removeEventListener(Exif.PARSE_FAILED, onParsingErrors);
//						// TODO: shouldn't we import photo EVEN IF THERE IS NO EXIF? when does PARSE_FAILED fire?
//						Config.logger.writeLog("Error", '-onExifParsingErrors');
//						self.importFilesThenFolders(filesThenFolders, null, fnComplete);
//					}
//					var onExifDataReady:Function = function (e:Event):void{
//						var exif:Exif = (e.target as Exif);
//						exif.removeEventListener(Exif.DATA_READY, onExifDataReady);
//						// get json_exif, then save to DB
//						var json_exif:Object = ImageScanner.extractExifInfo_nabiro(exif);
//						try {
//							self.saveImageInfoToDB(f, json_exif);
//						} catch (e:Error) {
//							// cancelImport
//						}
//						self.importFilesThenFolders(filesThenFolders, null, fnComplete); // put on timer?
//					}
//					var exif:Exif = new Exif();
//					exif.addEventListener(Exif.PARSE_FAILED, onParsingErrors);
//					exif.addEventListener(Exif.DATA_READY, onExifDataReady);
//					try {
//						exif.load(new URLRequest(f.url));
//					} catch (e:Error){
//						Config.logger.writeLog("Error", '-saveImagesErrors');
//					}
				}				
			}else{
				// unsupported filetype
				this.importFilesThenFolders(filesThenFolders, null, fnComplete);
			}	
		}
		
		private var fileTimeout:uint;
		

		private function isImageAlreadyExists(base_url:String,rel_path:String,asset_hash:String):Boolean{
			var params : Array = [];
			var query:String = "select * from photos where rel_path=@rel_path and base_url=@base_url";
			params.push({name:"@rel_path",value:rel_path});
			params.push({name:"@base_url",value:base_url});
			var dt:Array = this.sql.executeQueryParams(query,params);  
			// var dt:Array = this.sql.execQuery(query);
			var isexists:Boolean = (dt && dt.length);
			if(!isexists){
				query = "select * from photos where asset_hash=@asset_hash";
				params = [{name:"@asset_hash",value:asset_hash}];
				dt = Config.sql.executeQueryParams(query,params); 
				// dt = this.sql.execQuery(query);
				isexists = (dt && dt.length);
			} 
			return isexists;
		}
		private function hasToUpdateImage(base_url:String,rel_path:String,asset_hash:String):Boolean{
			var params : Array = [];
			var query:String = "select * from photos where rel_path=@rel_path and base_url=@base_url";
			params.push({name:"@rel_path",value:rel_path});
			params.push({name:"@base_url",value:base_url});
			var dt:Array = this.sql.executeQueryParams(query,params); 
			var is2update:Boolean = false;
			if(dt && dt.length){
				var old_asset_hash:String = dt[0]["asset_hash"];
				is2update = (asset_hash!=old_asset_hash);
			}else{
				query = "select * from photos where asset_hash=@asset_hash";
				params = [{name:"@asset_hash",value:asset_hash}];
				dt = Config.sql.executeQueryParams(query,params); 
				// dt = this.sql.execQuery(query);
				is2update = (!(dt && dt.length));
			}
			return is2update;	
		}
		
		/*
		* uses jp.shichiseki.exif.* lib
		*/
		private static var extractExifInfo_shichiseki:Function = function (exif:Object):Object{
			var json_exif:Object = {
				ExifImageWidth : '',		// PixelXDimension
				ExifImageLength: '',		// PixelYDimension
				Orientation: '',
				DateTimeOriginal: '',
				Make: '',
				Model: '',
				ISOSpeedRatings: '',
				FNumber: '',
				ExposureTime: '',
				ImageUniqueID: '',
				GPSLongitude: '',
				Flash: '',
				ColorSpace: '',
				InterOperabilityIndex: '',
				InterOperabilityVersion: '',
				
				xfaltuIsNull : true
			};
			try{
				json_exif.Orientation = exif.ifds.primary.Orientation;
				json_exif.Make = exif.ifds.primary.Make;
				json_exif.Model = exif.ifds.primary.Model;
				json_exif.xfaltuIsNull = false;
				if (exif.ifds.exif) {
					json_exif.ExifImageWidth = exif.ifds.exif.PixelXDimension;
					json_exif.ExifImageLength = exif.ifds.exif.PixelYDimension;
					json_exif.DateTimeOriginal = exif.ifds.exif.DateTimeOriginal;
					json_exif.ISOSpeedRatings = exif.ifds.exif.ISOSpeedRatings;
					json_exif.FNumber = exif.ifds.exif.FNumber;
					json_exif.ExposureTime = exif.ifds.exif.ExposureTime;
					json_exif.ImageUniqueID = exif.ifds.exif.ImageUniqueID;
					json_exif.Flash = exif.ifds.exif.Flash;
					json_exif.ColorSpace = exif.ifds.exif.ColorSpace;
				}
				if (exif.ifds.gps) {
					json_exif.GPSLongitude = exif.ifds.gps.GPSLongitude;
				}
//				json_exif.InterOperabilityVersion = exif.ifds.interoperability.unknown;
				if (exif.ifds.interoperability){
					try {
						if (typeof exif.ifds.interoperability.UnknownTag_0x2 !== 'string'){
							for (var i:int=0; i<exif.ifds.interoperability.UnknownTag_0x2.length; i++){
								json_exif.InterOperabilityVersion += String.fromCharCode( exif.ifds.interoperability.UnknownTag_0x2[i] );	
							}
						} else json_exif.InterOperabilityVersion=exif.ifds.interoperability.UnknownTag_0x2;
					} catch (e:Error) {}
					json_exif.InterOperabilityIndex = exif.ifds.interoperability.InterOperabilityIndex;
				}
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-extractExifInfo_shichiseki');
			}
			return json_exif;
		}
			
		/*
		 * uses com.gnstudio.nabiro.utilities.exif.*
		 */
//		private static var extractExifInfo_nabiro:Function = function (exif:Exif):Object{
//			var json_exif:Object = {
//				ExifImageWidth : '',
//				ExifImageLength: '',
//				Orientation: '',
//				DateTimeOriginal: '',
//				Flash: '',
//				ColorSpace: '',
//				InterOperabilityIndex: '',
//				InterOperabilityVersion: '',
//				xfaltuIsNull : true
//			};
//			try{
//				var ifds:Array = exif.availableIFDs;
//				var limit:int = ifds.length;
//				var tagObjects:Array = [];
//				for(var i:int = 0; i < limit; i++){
//					var tags:Array = (ifds[i] as IFD).entries;
//					var tagsCount:int = tags.length;
//					for(var k:int = 0; k < tagsCount; k++){
//						if (tags[k].name =='MakerNote') continue;	// skip
//						if (tags[k].name =='PrintMode') continue;	// skip
//						var value:* = tags[k].rawValue;
//						if(typeof(value)=='string'){
//							var str:String = (value as String);
//							value = (str.charAt(str.length-1)<' ')?str.substr(0,str.length-1):str;
//						}
//						json_exif[tags[k].name] = value;
//						json_exif.xfaltuIsNull = false; //if any value then not is null
//					}
//				}			
//			}catch(e:Error){
//				Config.logger.writeLog("Error",e.message + '-extractExifInfo_nabiro');
//			}
//			return json_exif;
//		}
		private function saveImageInfoToDB(f:File, json_exif:Object):void{
			//get current image file

			//			var f:File = this.fileOrFolders[this.fileOrFolderIndex];
			var rel_path:String = f.nativePath.replace(this.curr_baseurl + File.separator,'');
			var width:String =  json_exif.ExifImageWidth;	
			var height:String = json_exif.ExifImageLength;
			json_exif.DateTimeOriginal = (json_exif.DateTimeOriginal.length)?json_exif.DateTimeOriginal.substr(0,19):'';//remove last bit here
			var date_taken:String = json_exif.DateTimeOriginal;
			var asset_hash:String = this.getAssetHash(f,json_exif);
			var uuid:String = this.genUUID();
			var rating:int = 0; //default rating
			var createddt:String = Misc.convertDateStr(new Date());
			var fx:String = Misc.decimal2binary(json_exif.Flash);
			// TODO: check bit 1 for isFlash
			var isFlash:Boolean = parseInt(fx.substr(fx.length-1),10)==1;
			var isRGB:int = (json_exif.ColorSpace!=1 || json_exif.ColorSpace=='')?0:1;
			//default rotate value is 1
			var rotate:int = 1;//json_exif.Orientation || 0;
			var query:String ="";
			var query1:String = "";
			var params : Array = [];
			if(this.isImageAlreadyExists(this.curr_baseurl,rel_path,asset_hash)){
				if(this.hasToUpdateImage(this.curr_baseurl,rel_path,asset_hash)){
					//Update query here to update image data
					query = "update photos set asset_hash=@asset_hash,isStale=@isStale where rel_path=@rel_path";
					params.push({name:"@asset_hash",value:asset_hash});
					params.push({name:"@isStale",value:true});
					params.push({name:"@rel_path",value:rel_path});
					//var photo_id:String = Config.Uploader.getPhotosBy({rel_path:rel_path})[0].photo_id;
					//look table
					//query1 = "update uploadQueues set isStale=true where photo_id="; 
					this.updatedImages++;
					//Config.Uploader.updated_info.text = "(" + this.updatedImages + ") Updated";
				}else{
					this.existingImages++;
					//Config.Uploader.existing_info.text = "(" + this.existingImages + ") Existing";
					//Only Logs Already Exists Images
					Config.logger.writeLog("Info","Already Exists Image = " + f.url);
				}
			}else{
				this.newImages++;
				//Config.Uploader.added_info.text = "(" + this.addedImages + ") Added";
				
				//some bug in reading datetime thus blank for now
				//json_exif.DateTime = '';
				var exif_str:String = '';
				if(!json_exif.xfaltuIsNull){
					delete json_exif.xfaltuIsNull;	
					exif_str =  JSON.encode(json_exif);
				}
				query = "INSERT INTO photos" + 
					   "(id,asset_hash,base_url,rel_path,width,height,date_taken,json_exif,rating,created,isFlash,isRGB,rotate,isStale)" +
					   " VALUES("+ 
					   "@uuid"+
					   ",@asset_hash"+
					   ",@baseurl"+
					   ",@rel_path"+
					   ",@width"+
					   ",@height"+
					   ",@date_taken"+
					   ",@exif_str"+
					   ",@rating"+
					   ",@createddt"+
					   ",@isFlash"+
					   ",@isRGB"+
					   ",@rotate"+
					   ",@isStale"+
					   ")";		
				params.push({name:"@uuid",value:uuid});	   		
				params.push({name:"@asset_hash",value:asset_hash});
				params.push({name:"@baseurl",value:this.curr_baseurl});
				params.push({name:"@rel_path",value:rel_path});
				params.push({name:"@width",value:width});
				params.push({name:"@height",value:height});
				params.push({name:"@date_taken",value:date_taken});
				params.push({name:"@exif_str",value:exif_str});
				params.push({name:"@rating",value:rating});
				params.push({name:"@createddt",value:createddt});
				params.push({name:"@isFlash",value:isFlash});
				params.push({name:"@isRGB",value:isRGB});
				params.push({name:"@rotate",value:rotate});
				params.push({name:"@isStale",value:false}); 
			}
			if(query.length){
				try{				   
					this.sql.executeNonSQLParams(query,params);
				}catch(e:Error){
					Config.logger.writeLog("Error",e.message + '-saveImageToDb');
				}
			}
		}
		public function genUUID():String{
			return UUID.genUUID();
		}

	}
}