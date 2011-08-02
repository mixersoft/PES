package api
{
	/*
	 * Classic Logger used to write a log into text file 
	 */
	import com.adobe.serialization.json.JSON;
	
	import flash.filesystem.File;
	import flash.filesystem.FileMode;
	public class Logger
	{
		private var logFolder:File;
		public function Logger(logFolder:File){
			if (!logFolder.exists) {
				logFolder.createDirectory();	
			}
			this.logFolder = logFolder;
		}
		private function writeError(str:String):void{
			var f:File = logFolder.resolvePath('_err.txt');
			if (!f.exists) {
				f = new File(f.nativePath);	
			}
			str +='\n';
			Misc.FileWrite(f,str,FileMode.APPEND);	
		}
		public function writeJson(str:String, json:Object):void{
			var f:File = logFolder.resolvePath('_info.txt');
			if (!f.exists) {
				f = new File(f.nativePath);	
			}
			str = str + "=" + JSON.encode(json)+'\n';
			Misc.FileWrite(f,str,FileMode.APPEND);	
		}		
		public function writeInfo(str:String):void{
			var f:File = logFolder.resolvePath('_info.txt');
			if (!f.exists) {
				f = new File(f.nativePath);	
			}
			str +='\n';
			Misc.FileWrite(f,str,FileMode.APPEND);	
		}
		private function writeDebug(str:String):void{
			var f:File = logFolder.resolvePath('_debug.txt');
			if (!f.exists) {
				f = new File(f.nativePath);	
			}
			str +='\n';
			Misc.FileWrite(f,str,FileMode.APPEND);	
		}
		public function writeLog(mode:String,str:String):void{
			this['write' + mode].call(this,str + '\n');
		}
	}
}