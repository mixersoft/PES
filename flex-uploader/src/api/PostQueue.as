package api
{
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	import flash.net.URLRequestMethod;
	import flash.utils.clearTimeout;
	import flash.utils.setTimeout;
	
	import api.Config;
	import mx.utils.StringUtil;
	public class PostQueue
	{
		public var sql:SqlHandler;
		private var queue:Array;
		private var queueIndex:int;
		private var queueTimer:uint;
		private var cb:Object;
		public var url:String="";
		private var curr_rec:Object;
		private var onPostFireCB:Boolean;
		public function PostQueue(url:String,mode:Boolean = false)
		{
			this.url = url;
			this.sql = null;
			this.queue = new Array();
			this.onPostFireCB = mode;
		}
		public function startPost(cb:Object = null):void{
			this.cb = cb || null;
			if(StringUtil.trim(this.url).length){
				Config.logger.writeLog("Error","Post Queue Url Not Set. Please Set Server Url Where To Post Data");
				if(this.cb && this.cb.failure){
					this.cb.failure.call(this.cb.scope,'Post Queue Url Not Set. Please Set Server Url Where To Post Data',this.cb.arguments);
				}
			}else{
				var query:String = "select * from photos where sync_status=false";
				var dt:Array = this.sql.execQuery(query);
				if(dt && dt.length){
					this.queue = dt;
					this.startQueue();
				}else{
					if(this.cb && this.cb.failure){
						this.cb.failure.call(this.cb.scope,'No Records Found',this.cb.arguments);
					}
				}
			}	
		}
		public function startQueue():void{
			this.curr_rec = null;
			this.queueIndex = 0;
			this.queueTimer = setTimeout(this.doPost,10);
		}
		public function reQueue(photoids:Array,cb:Object):void{
			this.cb = cb || null;
			var query:String = "select * from photos where id in('" + photoids.join("','") + "')";
			// TODO: fix IN clause using params
			var dt:Array = this.sql.execQuery(query);
			if(dt && dt.length){
				this.queue = dt;
				this.startQueue();
			}else{
				if(this.cb && this.cb.failure){
					this.cb.failure.call(this.cb.scope,'No Records Found',this.cb.arguments);
				}
			}
		}
		public function postStaleData(photos:Array,cb:Object):void{
			this.cb = cb || null;
			if(photos.length){
				this.queue = photos;
				this.startQueue();
			}else{
				if(this.cb && this.cb.success){
					this.cb.success.call(this.cb.scope,'No Records Found',this.cb.arguments);
				}
			}
		}
		/**
		 * deprecated? see UploaderUI::postUploadFile()
		 */ 
		public function doPost():void{
			clearTimeout(this.queueTimer);
			if(this.queueIndex<this.queue.length){
				this.curr_rec = this.queue[this.queueIndex];
				var provider_key:String = Config.Datasource.cfg.provider_key;
				var data:String = 'data["provider_key"]=' + provider_key +
								  '&data["uuid"]=' + this.curr_rec['id'] + 
								  '&data["asset_hash"]=' + escape(this.curr_rec['asset_hash']) +
								  '&data["base_url"]=' + escape(this.curr_rec['base_url']) +
								  '&data["rel_path"]=' + escape(this.curr_rec['rel_path']) +
								  '&data["width"]=' + this.curr_rec['width'] +
								  '&data["height"]=' + this.curr_rec['height'] +
								  '&data["date_taken"]=' + escape(this.curr_rec['date_taken']) +
								  '&data["json_exif"]=' + escape(this.curr_rec['json_exif']) +	
								  '&data["rating"]=' + this.curr_rec['rating']+
								  '&data["created"]=' + escape(this.curr_rec['created']) +
								  '&data["modified"]=' + escape(this.curr_rec['modified']) +
								  '&data["json_src"]=' + escape(this.curr_rec['json_src']) +
								  '&data["isFlash"]=' + this.curr_rec['isFlash'] +
								  '&data["isRGB"]=' + this.curr_rec['isRGB'] +
								  '&data["rotate"]=' + this.curr_rec['rotate'] +
								  '&data["tags"]=' + escape(this.curr_rec['tags']) +
								  '&data["json_iptc"]=' + escape(this.curr_rec['json_iptc']);
				var urlreq:URLRequest = new URLRequest(this.url);
				urlreq.method = URLRequestMethod.POST;
				urlreq.data = data;
				var loader:URLLoader = new URLLoader(urlreq);
				loader.addEventListener(Event.COMPLETE,onPost);
	            loader.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
				loader.load(urlreq);
			}else{
				if(!this.onPostFireCB){
					if(this.cb && this.cb.success){
						this.cb.success.call(this.cb.scope,'Data Posted Successfully',this.cb.arguments);
					}
				}	
			}
		}
		private function ioErrorHandler(e:IOErrorEvent):void{
			//decide on error run next or not
			Config.logger.writeLog("Error",e.errorID + '-IO error while posting');
			this.retry();
		}
		private function retry():void{
			clearTimeout(this.queueTimer);
			this.queueTimer = setTimeout(this.doPost,(1000*60)*2);
		}
		private function onPost(e:Event):void{
			try{
				var loader:URLLoader = e.target as URLLoader;
				if(loader.bytesLoaded>=loader.bytesTotal){
					var data:String =  loader.data as String;
					if(data.length>0){
						//update status after successfully post
						var query:String = "update photos set sync_status=true,isStale=false where id=@id";
						try{
							var params:Array = [{name:"@id",value:this.curr_rec['id']}];
							Config.sql.executeNonSQLParams(query,params); 
							// this.sql.execNonQuery(query);
						} catch(e:Error){
							throw new Error(e.message); 
						}	
						this.curr_rec = null;
						//run next queue item after successfull post
						this.queueIndex++;
						this.queueTimer = setTimeout(this.doPost,10);
						if(this.onPostFireCB){
							if(this.cb && this.cb.success){
								this.cb.success.call(this.cb.scope,data,this.cb.arguments);
							}
						}
					}else{
						throw new Error("Internal server error. Please try later or email this error to our support executive.");
					}
				}else{
					throw new Error("Internal server error. Please try later or email this error to our support executive.");
				}					
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '- error while posting');
				//if error occured then wait 2 seconds and retry the queue to post url
				this.retry();
			}	
		}
		
	}
}