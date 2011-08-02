package api
{
	import flash.filesystem.File;

	
public class ConvertOptions {
	
	/***************************************************************************************
	 * @params srcFile
	 * @params destFile
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
	 * @params onExit
	 * @params onIOError
	 */
	public var srcFile:File;
	public var destFile:File;
	public var size:String;
	public var options:Object;		// { create: replace: rotate: autorotate: callback: }
	public var onExit:Function;
	public var onIOError:Function;	
	public function ConvertOptions(srcFile:File, destFile:File, size:String, options:Object, onExit:Function, onIOError:Function){
		this.srcFile = srcFile;
		this.destFile = destFile;
		this.size = size;
		this.options = options;		// { create: replace: rotate: autorotate: callback: }
		this.onExit = onExit;
		this.onIOError = onIOError;
	}
}
}
	
