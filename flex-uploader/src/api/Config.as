package api
{
	import flash.desktop.NativeApplication;
	import flash.filesystem.File;
	import flash.system.Capabilities;
	
	import mx.core.FlexGlobals;

	public class Config
	{
		
		public function Config()
		{
		}
		/**
		 * set Config static vars
		 * */		
		public static var init:Function = function():void {
			var os:String = flash.system.Capabilities.os.substr(0, 3);
			switch (os) {
				case 'Win':
				case 'Mac':
					Config.OS = os;
					break;
				default:
					Config.OS = 'unix';
					break;
			}
			Config.nativeApp = NativeApplication.nativeApplication;
			Config.descriptor = Config.nativeApp.applicationDescriptor;
			var ns:Namespace = Config.descriptor.namespace();
			Config.version = Config.descriptor.ns::versionNumber
			trace("Version " + descriptor.ns::versionNumber);
		}
		
		// static reference to Uploader application, snaphappi.mxml
		public static var nativeApp:NativeApplication; 
		public static var descriptor:XML;
		public static var version:String;
		public static var Uploader:Object = FlexGlobals.topLevelApplication;
		public static var sql:SqlHandler;
		public static var logger:Logger = null;
		public static var DEBUG:Boolean = false;
		public static var appRoot:File;			// applicationStorage directory, adjusted for DEBUG 
		public static var OS:String; 
		public static var HOST:String;
		
		// flex API, exposed to javascript
		public static var UI:UploaderUI;
		public static var Datasource:UploaderDatasource;
		
		// javascript API, exposed to flex
		public static var jsGlobal:Object = null;
		public static var jsUI:Object;			// object SNAPPI.AIR.uploadQueue
		public static var jsUploadQueue:Object;	// class SNAPPI.AIR.UploadQueue
		public static var SNAPPI:Object = null;
		

		/**
		 * add all config constants here
		 */
		// UploaderUI
		public static var JPG_COMPRESSION:Number = 80;
		public static var USE_IMAGEMAGICK_RESIZE:Boolean = true;
		public static var MAX_CONCURRENT_UPLOADS:int = 1;
		
		// MagickUtils, imagemagick
		public static var MAX_CONCURRENT_PROCESSES:int = 4;
		
		// run Misc.startGCCycle after this many uploads
		public static var UPLOAD_GC_LIMIT:int = 9;
	}
}
