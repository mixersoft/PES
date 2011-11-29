package api
{
	import api.Config;
	
	import com.adobe.air.filesystem.FileUtil;
	import com.adobe.images.JPGEncoder;
	
	import flash.display.BitmapData;
	import flash.display.Loader;
	import flash.events.Event;
	import flash.filesystem.File;
	import flash.filesystem.FileMode;
	import flash.filesystem.FileStream;
	import flash.geom.Matrix;
	import flash.system.System;
	import flash.utils.ByteArray;
	import flash.utils.setTimeout;
	
	import mx.controls.Image;
	import mx.formatters.DateFormatter;
	

		
	/*
	 * General Purpose functions used by API 
	 */
	public class Misc
	{
		/*
		* 	Garbage Collection 
		*/ 
		public static function startGCCycle():void{
			Misc.gcCount = 0;
			Config.Uploader.addEventListener(Event.ENTER_FRAME, Misc.doGC);
		}
		private static var gcCount:int = 0;
		private static function doGC(evt:Event):void{
			System.gc();
			if(++Misc.gcCount > 1){
				Config.Uploader.removeEventListener(Event.ENTER_FRAME, Misc.doGC);
				setTimeout(Misc.lastGC, 40);
			}
		}
		private static function lastGC():void{
			System.gc();
		}	
		
		
		public static function getJPGcompression():Number{
			var JPG_COMPRESSION:Number = Config.Datasource.cfg.JPG_COMPRESSION || Config.JPG_COMPRESSION;
			return JPG_COMPRESSION;
		}
	 	public static function convertDateStr(dt:Object):String{
	   		var df:DateFormatter = new DateFormatter();
	   		df.formatString = "YYYY-MM-DD JJ:NN:SS";
	   		return df.format(dt);
	  	}		
		public static function FileWrite(f:File,data:String,mode:String=FileMode.WRITE,overwrite:Boolean = true):void
		{
			if(f.exists && overwrite && mode!=FileMode.APPEND){
				f.deleteFile();
			}
			var s:FileStream = new FileStream();
			s.open(f,mode);
			s.writeMultiByte(data,'iso-8859-1');
			s.close();
			
		}
		public static function normalizeFilePath(path:String):String{
			var newpath:String = '';
			if(path.indexOf('file:/')<0){
				newpath += 'file:///';
			}
			newpath +=  path.replace(/\\/g,'/');
			return  newpath; 
		}
		public static function createSnaphappiJSON(dt:Array,total_rows:int,qs:Object):Object{
			var obj:Object = {};
			obj.CastingCall = {};
			obj.CastingCall.ID = 1271056739;
			obj.CastingCall.Auditions = {};
			obj.CastingCall.Auditions.Total = total_rows;
			obj.CastingCall.Auditions.Perpage  = qs.perpage; 
			obj.CastingCall.Auditions.Pages = total_rows/qs.perpage;
			obj.CastingCall.Auditions.Page = qs.page;
			obj.CastingCall.Auditions.Baseurl = qs.base_url;
			var auditions:Array = [];
			for(var i:int=0;i<dt.length;i++){
				var rec:Object = {};
				rec.id = dt[i]['id'];
				rec.Photo = {};
				rec.Photo.id = dt[i]['id'];
				rec.Photo.W = dt[i]['width'];
				rec.Photo.H = dt[i]['height'];
				rec.Photo.Fix =  {};
				rec.Photo.Fix.Crops = "";
				rec.Photo.Fix.Rating =  dt[i]['rating'];
				rec.Photo.Fix.Rotate = "";
				rec.Photo.Fix.Scrub = "";
				rec.Photo.Img = {};
				rec.Photo.Img.Src = {};
				rec.Photo.Img.Src.W = dt[i]['width'];
				rec.Photo.Img.Src.H = dt[i]['height'];
				rec.Photo.Img.Src.AutoRender = true;
				rec.Photo.Img.Src.Src  = dt[i]['rel_path'];
				rec.Photo.DateTaken = dt[i]["date_taken"];
				rec.Photo.TS = 1252109304;
				rec.Photo.ExifColorSpace =  "1";
				rec.Photo.ExifFlash =  1;
				rec.Photo.ExifOrientation = "1";
				rec.LayoutHint =  {};
				rec.LayoutHint.FocusCenter =  {};
				rec.LayoutHint.FocusCenter.Scale =  4000;
				rec.LayoutHint.FocusCenter.X = 1500;
				rec.LayoutHint.FocusCenter.Y = 2000;
				rec.LayoutHint.FocusVector = {};
				rec.LayoutHint.FocusVector.Direction = 0;
				rec.LayoutHint.FocusVector.Magnitude = 0;
				rec.LayoutHint.Rating =  "";
				rec.LayoutHint.Votes =  0;
				rec.IsCast =  0;
				rec.SubstitutionREF = "";
				rec.Tags = [];
				rec.Clusters = "";
				rec.Credits = "";
				auditions.push(rec);
			}
			obj.CastingCall.Auditions.Audition = auditions;
			return obj;
		}
		/**
		 * use simple string manipulations to get a resized filepath
		 * 		does NOT check for existence,
		 * 		does NOT relocate to applicationStorageDirectory
		 * */
		public static function getImgPathBySize(orig:File, size:String):String {
			// TODO: strip off existing prefix, if any
			var resizedFile:File = new File(orig.parent.nativePath + File.separator + size + '~'+ orig.name);
			return resizedFile.nativePath;
		}
		public static function getImgSize(size:String):int{
			var nsize:int;
			switch(size.toLowerCase()){
				case 'bs':
					nsize = 240;
					break;
				case 'bp':
					nsize = 640;
					break;
				case 'bm':
					nsize = 320;
					break;
				case 'sq':
					nsize = 75;
					break;
				case 'tn':	
				default :
					nsize = 100;
			}
			return nsize;			
		}
		public static function getThumbPath(f:File):String{
			var apppath:String = File.applicationDirectory.nativePath;
			apppath += File.separator + 'thumnails'+ File.separator + f.nativePath.replace(/\:/ig,'_');
			return apppath; 
		}
		public static function getResizePath(f:File):String{
			var apppath:String = File.applicationDirectory.nativePath;
			apppath += File.separator + 'resized' + File.separator  + f.nativePath.replace(/\:/ig,'_');
			return apppath; 
		}
		
		public static function rotateImage(img:Loader,angle:Number):BitmapData{
	        // Calculate rotation and offsets
	        var radians:Number = angle * (Math.PI / 180.0);
	        var offsetWidth:Number = img.content.width/2.0;
	        var offsetHeight:Number =  img.content.height/2.0;
	        // Perform rotation
	        var matrix:Matrix = img.content.transform.matrix;
	        matrix.translate(-offsetWidth, -offsetHeight);
	        matrix.rotate(radians);
	        matrix.translate(+offsetWidth, +offsetHeight);
		   	var bd:BitmapData = new BitmapData(img.content.height, img.content.width);
		   	bd.draw(img.content, matrix);
		   return bd;
		}
		
		
		public static function rotateLeftx(img:Loader):BitmapData
		{
		   var matrix:Matrix = new Matrix();
		   matrix.rotate(-1*Math.PI/2);
		   matrix.ty = img.content.width;
		   var bd:BitmapData = new BitmapData(img.content.height, img.content.width);
		   bd.draw(img.content, matrix);
		   return bd;
		}
		public static function rotateRightx(img:Loader):BitmapData
		{
		   var matrix:Matrix = new Matrix();
		   matrix.rotate(Math.PI/2);
		   matrix.ty = img.content.width;
		   var bd:BitmapData = new BitmapData(img.content.height, img.content.width);
		   bd.draw(img.content, matrix);
		   return bd;
		}

		
		public static function rotateLeft(img:Image):BitmapData
		{
		   var matrix:Matrix = new Matrix();
		   matrix.rotate(-1*Math.PI/2);
		   matrix.ty = img.content.width;  
		   var bd:BitmapData = getBitmapDataMatrix(img, matrix);
		   return bd;
		}
		public static function rotateRight(img:Image):BitmapData
		{
		   var matrix:Matrix = new Matrix();
		   matrix.rotate(Math.PI/2);
		   matrix.tx = img.content.height;
		   var bd:BitmapData = getBitmapDataMatrix(img, matrix);
		   return bd;
		}		
		// Pass in reference to of the Image control with 
		// the original image and the matrix
		public static function getBitmapDataMatrix(img:Image, matrix:Matrix) : BitmapData
		{
		   var bd:BitmapData = new BitmapData(img.content.height, img.content.width);
		   bd.draw(img.content, matrix);
		   return bd;
		}
		public static function resizeIt(b:BitmapData, fixedSize:Number=640,rotate:int=0):BitmapData {
			var thisWidth:int = Math.round(b.width);
			var thisHeight:int = Math.round(b.height);
			var nw:Number,nh:Number;
			if(thisHeight>thisWidth){
				nh = fixedSize;
				nw = (thisWidth * fixedSize)/thisHeight;
			}else if(thisWidth>thisHeight){
				nw = fixedSize;
				nh = (thisHeight * fixedSize)/thisWidth;
			}else{
				nw = fixedSize;
				nh = fixedSize;
			}	
			var bd:BitmapData = new BitmapData(nw,nh,true,0x00FFFFFF); // transparent, in case your image is…
			var matrix:Matrix = new Matrix();
			if(rotate==3 || rotate==6 || rotate==8){
		        var offsetWidth:Number = nw/2.0;
		        var offsetHeight:Number =  nh/2.0;
		        var boffsetWidth:Number = nw/2.0;
		        var boffsetHeight:Number =  nh/2.0;
	        	var angle:Number;
				if(rotate==3){
					angle = 180;	
					matrix.scale(nw / b.width,nh / b.height);
				}else if(rotate==6){ 
					angle = 90;
					bd = new BitmapData(nh,nw,true,0x00FFFFFF); // transparent, in case your image is…
					matrix.scale(nh / b.height,nw / b.width);
					boffsetWidth = offsetHeight;
					boffsetHeight = offsetWidth;
				}else if(rotate==8){ 
					angle = 270;
					bd = new BitmapData(nh,nw,true,0x00FFFFFF); // transparent, in case your image is…
					matrix.scale(nh / b.height,nw / b.width);
					boffsetWidth = offsetHeight;
					boffsetHeight = offsetWidth;
				}
				var radians:Number = angle * (Math.PI / 180.0);
				matrix.translate(-offsetWidth, -offsetHeight);
				matrix.rotate(radians);
				matrix.translate(+boffsetWidth, +boffsetHeight);
			}else{
				matrix.scale(nw / b.width,nh / b.height);
			}			
			bd.draw(b,matrix);
			//rotate also if rotate is 3,6,8
			return bd;
		}	
		public static function saveJPG(b:BitmapData,file:File,compression:Number):void{
			try{
				if(file.exists){
					file.deleteFile();
				}
		        var jpg:JPGEncoder = new JPGEncoder(compression);
		        var ba:ByteArray = jpg.encode(b);
		        var stream:FileStream = new FileStream();
		        stream.open(file, FileMode.WRITE);
		        stream.writeBytes(ba);
		        stream.close();
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-saveJPG');
			}			
		}	
		public static function decimal2hex(num:Number):*{
			var hexval:String = ((num).toString(16));
			return "0x" + hexval;
		}
		public static function decimal2binary(num:Number):String{
			return (num).toString(2);
		}
		public static function readContent(f:File):String{
			var str:String = '';
			if(f.exists){
				var s:FileStream = new FileStream();
				s.open(f,FileMode.READ);
				str = s.readMultiByte(s.bytesAvailable,'iso-8859-1');
				s.close();
			}
			return str;
		}
	
	}
}