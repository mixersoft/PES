// ActionScript file
/*
*	from  http://pranjan.com/?tag=nativeprocessstartupinfo
*/
//package pravin.magick
package api
{
	import api.ConvertOptions;
	
	import flash.desktop.NativeProcess;
	import flash.desktop.NativeProcessStartupInfo;
	import flash.display.Bitmap;
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.NativeProcessExitEvent;
	import flash.events.ProgressEvent;
	import flash.filesystem.File;
	import flash.net.URLRequest;

	
	public class MagickUtils
	{
		
		public function MagickUtils()
		{
			// static Class library
		}
		
		private static var convertExe:File;
		private static var workingDir:File;
		
		public static var MAX_CONCURRENT_PROCESSES:int = 4;
		
		private static var activeQueue:Vector.<ConvertOptions> = new Vector.<ConvertOptions>();
		private static var readyQueue:Vector.<ConvertOptions> = new Vector.<ConvertOptions>();
		
		/**
		 * set path and workingDir for imagemagick by platform
		 * */
		public static var init:Function = function():void {
			switch (Config.OS) {
				case 'Mac': 
					convertExe = File.applicationDirectory.resolvePath("ImageMagick/mac/x86_64/bin/convert");
					workingDir = File.applicationDirectory.resolvePath("ImageMagick/mac");
					break;
				case 'Win':
				default: 
					convertExe = File.applicationDirectory.resolvePath("ImageMagick/win/convert.exe");
					workingDir = File.applicationDirectory.resolvePath("ImageMagick/win");
				break;
			}
		}
		
		private static var deQueue:Function = function(o:ConvertOptions):void {
			var i:int = activeQueue.indexOf(o);
			if (i !== -1) {
				MagickUtils.activeQueue.splice(i, 1);
				MagickUtils.doNextQueue();
			}
		}
		public static var enQueue:Function = function(srcFile:File, destFile:File, 
													  size:String, options:Object, 
													  onExit:Function, onIOError:Function):void {
			var o:ConvertOptions = new ConvertOptions(srcFile, destFile, size, options, onExit, onIOError);
			MagickUtils.readyQueue.push(o);
			MagickUtils.doNextQueue();
		}
			
			
			/**********************************************************************
			 * 
			 * next = {srcFile:File, destFile:File, size:String, options:Object, onExit:Function, onIOError:Function }
			 * 
			 * */
		private static var doNextQueue:Function = function():void {
			var next:ConvertOptions = MagickUtils.readyQueue.shift();
			if (next && MagickUtils.createParentDirectory(next.destFile)) {
				// keep reference to callback functions
				var options:Object = next.options;
				var srcFilename:String = next.srcFile.nativePath;
				var destFilename:String = next.destFile.nativePath;
				//			trace("srcFilename: " + srcFilename);
				//			trace("destFilename: " + destFilename);				
				// build convert params
				if (options.create || (next.destFile.exists && options.replace)){
					// get ImageSize asynchronously, complete function in closure
					MagickUtils.getImageSize(srcFilename, 
						function(srcDim:Object):void {
							var convertOptions:String = MagickUtils.getConvertOptions(next.size, srcDim, options.autorotate, options.rotate);
							// get arguments for NativeProcess
							convertOptions = convertOptions.replace(':srcFilename', srcFilename);
							convertOptions = convertOptions.replace(':destFilename', destFilename);
							//					trace("convert>" + convertOptions);
							var processArgs:Vector.<String> = new Vector.<String>();
							var parts:Array = convertOptions.split(',');
							for (var i:int =0; i<parts.length; i++) {
								processArgs.push(parts[i]);
							}
							// create/config NativeProcessStartupInfo
							var nativeProcessStartupInfo:NativeProcessStartupInfo = new NativeProcessStartupInfo();
							nativeProcessStartupInfo.arguments = processArgs;
							nativeProcessStartupInfo.executable = MagickUtils.convertExe;
							nativeProcessStartupInfo.workingDirectory =  MagickUtils.workingDir;
							
							var process:NativeProcess = new NativeProcess();
							var plugin_OnExit:Function = function(e:NativeProcessExitEvent):void{
								process.removeEventListener(NativeProcessExitEvent.EXIT, plugin_OnExit);
								process.removeEventListener(IOErrorEvent.STANDARD_OUTPUT_IO_ERROR, plugin_onIOError);
								process.removeEventListener(IOErrorEvent.STANDARD_ERROR_IO_ERROR, plugin_onIOError);
//								process.removeEventListener(ProgressEvent.STANDARD_OUTPUT_DATA, MagickUtils.onOutputData );
//								process.removeEventListener(ProgressEvent.STANDARD_ERROR_DATA, MagickUtils.onErrorData );								
								MagickUtils.deQueue(next);		// calls doNextQueue()
								next.onExit(e);
							}
							var plugin_onIOError:Function = function(e:IOErrorEvent):void{
								process.removeEventListener(NativeProcessExitEvent.EXIT, plugin_OnExit);
								process.removeEventListener(IOErrorEvent.STANDARD_OUTPUT_IO_ERROR, plugin_onIOError);
								process.removeEventListener(IOErrorEvent.STANDARD_ERROR_IO_ERROR, plugin_onIOError);
								MagickUtils.deQueue(next);		// calls doNextQueue()
								next.onIOError(e);
							}
							
							// start NativeProcess
							process.addEventListener(NativeProcessExitEvent.EXIT, plugin_OnExit);
							process.addEventListener(IOErrorEvent.STANDARD_OUTPUT_IO_ERROR, plugin_onIOError);
							process.addEventListener(IOErrorEvent.STANDARD_ERROR_IO_ERROR, plugin_onIOError);
//							process.addEventListener(ProgressEvent.STANDARD_OUTPUT_DATA, MagickUtils.onOutputData );
//							process.addEventListener(ProgressEvent.STANDARD_ERROR_DATA, MagickUtils.onErrorData );
							
							// queue, then start
							MagickUtils.activeQueue.push(next);
							process.start(nativeProcessStartupInfo);
						}
					);
				} 
			}				
		}
		
		public static var queue_NativeProcessStartupInfo:Function = function(srcFile:File, destFile:File, 
					 size:String, options:Object,
					 onExit:Function, 
					 onIOError:Function	
			):void {

		}

		/**
		 * get imagesize from file ASYNC
		 * */
		public static var getImageSize:Function = function (srcFile:String, fnSuccess:Function):void {
			var ld:Loader = new Loader();
			var onload:Function = function(e:Event):void{
				var bd:BitmapData = Bitmap(ld.content).bitmapData;
				var dim:Object = {W:bd.width, H:bd.height};
				fnSuccess(dim);
				ld.contentLoaderInfo.removeEventListener(e.type, onload );
			};
			ld.contentLoaderInfo.addEventListener(Event.COMPLETE, onload);
			var f:File = new File(srcFile);
			ld.load(new URLRequest(f.url));				
		}			
		public static var createParentDirectory:Function = function(destFile:File):Boolean {
			try {
				if (!destFile.parent.exists) destFile.parent.createDirectory();
				return true;
			} catch (e:Error) {
				trace('ERROR creating directory, file='+destFile.url);
			}
			return false;			
		};
		public static var getConvertOptions:Function = function (size:String, srcDim:Object, autorotate:Boolean, rotate:int):String {
			var convertOptions:String;
			var auto_orient:String = autorotate ? '-auto-orient,' : '';
			rotate = rotate || 1;
			var quality:int = Config.JPG_COMPRESSION;
			var destSize:int;
			var destHeight:int;
			var destWidth:int;
			
			switch (size.toLowerCase()){
				case 'bp':
					destSize= 640;
					break;				
				case 'sq':
					destSize= 75;
					break;
				case 'tn':
					destSize= 120;
					break;
				case 'bs':
					destSize= 240;
					break;
				case 'bm':
					destSize= 320;
					break;
			}
			var format:Number = srcDim.W/srcDim.H;
			if (format < 1) {
				destHeight = Math.min(destSize, srcDim.H);
				destWidth = Math.round(destHeight * format);
			} else {
				destWidth = Math.min(destSize, srcDim.W);
				destHeight = Math.round(destWidth * format);
			}
			var width2x:int, width4x:int, height2x:int;
			if (size.toLowerCase()=='sq') {
				destWidth = Math.max(destHeight, destWidth);
				width4x = destWidth*4;
				// SQUARE thumbnails
				convertOptions = '-define,jpeg:size='+width4x+'x'+width4x+',:srcFilename,'+auto_orient+'-thumbnail,'+destWidth+'x'+destWidth+'^,-gravity,center,-crop,'+destWidth+'x'+destWidth+'+0+0,:destFilename';
			} else {
				width2x = destWidth*2;
				width4x = destWidth*4;
				height2x = destHeight*2;
				if (Math.max(destWidth, destHeight) < 360) {
					// make it a "thumbnail", proportional resize, remove exif data, etc.
					convertOptions = '-define,jpeg:size='+width2x+'x'+height2x+',:srcFilename,-thumbnail,'+destWidth+'x'+destHeight+','+auto_orient+':destFilename';
				} else {
					// just crop and/or resize, but preserve exif, default is Lanczos filter
					//			$resize_command = "$path_to_convert -scale {$width}x{$height}! -filter QUADRATIC "   ;
					convertOptions = ':srcFilename,-resize,'+destWidth+'x'+destHeight+','+auto_orient+'-quality,'+quality+',:destFilename'; // convert help
				}
			}
			return convertOptions;
		}
			
			
			/**************************************************************
			 * Static listeners
			 * */
		
		public static var onExitDebug:Function =  function(e:NativeProcessExitEvent, onExit:Function):void{
			if (e.exitCode) {
				var errorMsg:String = "convert.exe process exited with exit code=" + e.exitCode;
				onExit(e);	// from closure
			} else { 	// exit 0
				onExit(e);	
			}
		}
		public static var onOutputData:Function = function (event:ProgressEvent, process:NativeProcess):void
		{
			trace("Got: ", process.standardOutput.readUTFBytes(process.standardOutput.bytesAvailable));
		}
		
		public static var onErrorData:Function = function (event:ProgressEvent, process:NativeProcess):void
		{
			trace("ERROR -", process.standardError.readUTFBytes(process.standardError.bytesAvailable));
		}	
			
		
		
		/*Explicitely declared constructor.*/
		private var srcFolderOrFile:File;
		private var destFolderOrFile:File;
		private var process:NativeProcess;			
//		public function MagickUtils(srcFolderOrFile:File, destFolderOrFile:File, 
//									onExit:Function, 
//									onIOError:Function,									
//									onOutputData:Function,
//									onErrorData:Function		)
//		{
//			this.srcFolderOrFile = srcFolderOrFile;
//			this.destFolderOrFile = destFolderOrFile;
//			this.process = new NativeProcess();
//			onOutputData = onOutputData || function(event:ProgressEvent):void{
//				MagickUtils.onOutputData(event, process);
//			}
//			onErrorData = onErrorData || function(event:ProgressEvent):void{
//				MagickUtils.onErrorData(event, process);
//			} ;
//			//			this.process.addEventListener(NativeProcessExitEvent.EXIT, function(e):void{
//			//				MagickUtils.onExitDebug(e, onExit);
//			//			});
//			this.process.addEventListener(NativeProcessExitEvent.EXIT, onExit);
//			this.process.addEventListener(ProgressEvent.STANDARD_OUTPUT_DATA, onOutputData );
//			this.process.addEventListener(ProgressEvent.STANDARD_ERROR_DATA, onErrorData );
//			this.process.addEventListener(IOErrorEvent.STANDARD_OUTPUT_IO_ERROR, onIOError);
//			this.process.addEventListener(IOErrorEvent.STANDARD_ERROR_IO_ERROR, onIOError);
//		}
//			
//		public function convert(srcFilename:String, destFilename:String, size:String, options:Object):void
//		{	
//			var srcFile:File = srcFolderOrFile.isDirectory == false ? srcFolderOrFile : srcFolderOrFile.resolvePath(srcFilename);
//			srcFilename = srcFile.nativePath;
//			var destFile:File = destFolderOrFile.isDirectory == false ? destFolderOrFile : destFolderOrFile.resolvePath(destFilename);
//			MagickUtils.createParentDirectory(destFile);
//			destFilename = destFile.nativePath;
////			trace("srcFilename: " + srcFilename);
////			trace("destFilename: " + destFilename);
//			var nativeProcessStartupInfo:NativeProcessStartupInfo = new NativeProcessStartupInfo();
//			nativeProcessStartupInfo.executable = MagickUtils.convertExe;
//			nativeProcessStartupInfo.workingDirectory =  MagickUtils.workingDir;
//
//			// build convert params
//			if (options.create){
//				var replace:Boolean = options.replace || false;
//				if (replace===false && destFile.exists) {
//					return;		// do NOT create new image
//				} 
//				var _process:NativeProcess = this.process;
//				// get ImageSize asynchronously, complete function in closure
//				MagickUtils.getImageSize(srcFilename, function(srcDim:Object):void {
//					var convertOptions:String = MagickUtils.getConvertOptions(size, srcDim, options.autorotate, options.rotate);
//					// get arguments for NativeProcess
//					convertOptions = convertOptions.replace(':srcFilename', srcFilename);
//					convertOptions = convertOptions.replace(':destFilename', destFilename);
////					trace("convert>" + convertOptions);
//					var processArgs:Vector.<String> = new Vector.<String>();
//					var parts:Array = convertOptions.split(',');
//					for (var i:int =0; i<parts.length; i++) {
//						processArgs.push(parts[i]);
//					}
//					nativeProcessStartupInfo.arguments = processArgs;
//					_process.start(nativeProcessStartupInfo);
//				});
//			}
//		}		
		
		
		
		
		/*******************************************************************************
		 * test method
		 * */
		public static var testConvert:Function = function():void { 
			var test:Function = function(convertOptions:String):void{
				var cmd:String = convertOptions;
				var srcFilename:String = 'C:\\USERS\\michael\\Pictures\\importTest\\DC\\P1040011.JPG';
				var destFilename:String = 'W:\\www-git\\flex-uploader\\bin-debug\\images\\C_\\USERS\\michael\\Pictures\\importTest\\DC\\sq~P1040011.JPG';
				trace("srcFilename: " + srcFilename);
				trace("destFilename: " + destFilename);
				var process:NativeProcess = new NativeProcess();
				process.addEventListener(ProgressEvent.STANDARD_OUTPUT_DATA, function(event:ProgressEvent):void{
					trace(cmd);
					MagickUtils.onOutputData(event, process);
				} );
				process.addEventListener(ProgressEvent.STANDARD_ERROR_DATA, function(event:ProgressEvent):void{
					trace(cmd);
					MagickUtils.onErrorData(event, process);
				} );
				process.addEventListener(NativeProcessExitEvent.EXIT, function(e:NativeProcessExitEvent):void{
					trace(cmd + ", exit=" + e.exitCode);
				} );
				var onIOError:Function = function(e:IOErrorEvent):void{
					e.target;
				}
				process.addEventListener(IOErrorEvent.STANDARD_OUTPUT_IO_ERROR, onIOError);
				process.addEventListener(IOErrorEvent.STANDARD_ERROR_IO_ERROR, onIOError);				
				var nativeProcessStartupInfo:NativeProcessStartupInfo = new NativeProcessStartupInfo();
				nativeProcessStartupInfo.executable = File.applicationDirectory.resolvePath("ImageMagick/convert.exe");
				nativeProcessStartupInfo.workingDirectory =  File.applicationDirectory.resolvePath("ImageMagick");
				
				trace("convert:" + convertOptions);
				convertOptions = convertOptions.replace(':srcFilename', srcFilename);
				convertOptions = convertOptions.replace(':destFilename', destFilename);
				var processArgs:Vector.<String> = new Vector.<String>();
				var parts:Array = convertOptions.split(',');
				for (var i:int =0; i<parts.length; i++) {
					processArgs.push(parts[i]);
				}
				nativeProcessStartupInfo.arguments = processArgs;
				process.start(nativeProcessStartupInfo);						
			} 
			var convertOptions:String;
			convertOptions = ':srcFilename,-resize,75x112,:destFilename';
		}		
	}
}	
