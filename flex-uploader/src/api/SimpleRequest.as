package api
{
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	import flash.net.URLRequestMethod;
	
	import api.Config;
	public class SimpleRequest
	{
		private var cb:Object = null;
		public function sendRequest(url:String,postparams:String,cb:Object):void{
			this.cb = cb;
			try{
				var urlreq:URLRequest = new URLRequest(url);
				urlreq.method = URLRequestMethod.POST;
				urlreq.data = postparams;
				var loader:URLLoader = new URLLoader(urlreq);
				loader.addEventListener(Event.COMPLETE,onPost);
	            loader.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
				loader.load(urlreq);
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-sendRequest');
				if(this.cb && this.cb.failure){
					this.cb.failure.call(this.cb.scope || Config.jsGlobal ,e.message,this.cb.arguments);
				}
			}
		}
		private function ioErrorHandler(e:IOErrorEvent):void{
			//decide on error run next or not
			var msg:String = e.errorID + '-IO error while sendingRequest';
			Config.logger.writeLog("Error",msg);
			if(this.cb && this.cb.failure){
				this.cb.failure.call(this.cb.scope || Config.jsGlobal ,msg,this.cb.arguments);
			}
		}
		private function onPost(e:Event):void{
			try{
				var loader:URLLoader = e.target as URLLoader;
				if(loader.bytesLoaded>=loader.bytesTotal){
					var data:String =  loader.data as String;
					if(data.length>0){
						if(this.cb && this.cb.success){
							this.cb.success.call(this.cb.scope || Config.jsGlobal ,data,this.cb.arguments || null);
						}
					}else{
						throw new Error("Internal server error. Please try later or email this error to our support executive.");
					}
				}else{
					throw new Error("Internal server error. Please try later or email this error to our support executive.");
				}					
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '- error while sendingRequest');
				if(this.cb && this.cb.failure){
					this.cb.failure.call(this.cb.scope || Config.jsGlobal ,e.message,this.cb.arguments);
				}
			}	
		}		
	}
}