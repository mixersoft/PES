package api
{
	import mx.core.FlexGlobals;

	public class Config
	{
		
		public function Config()
		{
		}
		// static reference to Uploader application, snaphappi.mxml
		public static var Uploader:Object = FlexGlobals.topLevelApplication;
		public static var sql:SqlHandler;
		public static var logger:Logger = null;
		
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
		public static var MAX_CONCURRENT_UPLOADS:int = 3;
		
		// MagickUtils, imagemagick
		public static var MAX_CONCURRENT_PROCESSES:int = 4;
		
		// run Misc.startGCCycle after this many uploads
		public static var UPLOAD_GC_LIMIT:int = 9;
	}
}
