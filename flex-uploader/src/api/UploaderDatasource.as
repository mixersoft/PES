package api
{
	
	import com.adobe.serialization.json.*;
	
	import flash.filesystem.File;
	
	import mx.utils.StringUtil;
	
	// javascript reference: flexAPI_Datasource, or _flexAPI_UI.datasource
	// Flex global: Config.Datasource
	public class UploaderDatasource
	{
		public var cfg:Object = null;
		public function UploaderDatasource()
		{
		}

		/**
		 *	load config from DB table into UploadDatasource.cfg 
		 * */
		public function loadConfig():void{
			var query:String = "SELECT * FROM config";
			var dt:Array = Config.sql.execQuery(query);
			if(dt && dt.length){
				if (this.cfg ===null) this.cfg = {};
				var key:String, jsonObj:Object, value:*;
				try {
					for (var i:int = 0; i<dt.length; i++) {
						key = dt[i]['key'];
						value = dt[i]['value'];
						try {
							jsonObj = JSON.decode(value);
							this.cfg[ key ] = jsonObj; 
						} catch (e:Error) {
							this.cfg[ key ] = value; 
							continue;
						}
					}
				} catch(e:Error){
					Config.logger.writeLog("Error",e.message + '-while encoding configs json');
					this.cfg = null;
				}	
			}
			//apply defaults if null
			if(this.cfg==null){
				this.cfg = {};
				this.cfg.JPG_COMPRESSION = Config.JPG_COMPRESSION;
				this.cfg.MAX_CONCURRENT_UPLOADS = Config.MAX_CONCURRENT_UPLOADS;
				this.cfg.photosPerpage = 10;
				this.cfg.uploadQueuesPerpage = 10;
			}
			if(typeof(this.cfg.provider_key)=='undefined'){ //unique uuid for this installed app instance very first time
				this.saveConfig("provider_key",UUID.genUUID());	
			}
			this.saveConfig("MAX_CONCURRENT_UPLOADS",Config.MAX_CONCURRENT_UPLOADS);			
			this.setBaseurlToLastSelected();	// restore last select baseurl
		}
		/**
		 * save key=value pair to cfg
		 * */		
		public function saveConfig(key:String, value:*):Boolean{
			var flag:Boolean = false;
			var params:Array = [];
			this.cfg[key] = value;		// save to local obj, then to DB
			params.push({name:"@key", value:key});
			if (typeof value == 'object' && value !== null) {
				params.push({name:"@value", value:JSON.encode(value)});
			} else params.push({name:"@value", value:value});
			try{
				var query:String= "INSERT INTO config(key, value) VALUES (@key, @value)";
				var dt:Array = Config.sql.executeNonSQLParams(query, params);
				flag = true;
			}catch(e:Error){
				try{
					query = "UPDATE config SET value=@value WHERE key=@key";
					Config.sql.executeNonSQLParams(query,params);
					flag = true;
				}catch(e2:Error){
					Config.logger.writeLog("Error",e2.message + '-saveConfigs');
				}
			}
			return flag;
		}		
		/**
		 * merge key-value object to cfg, typically called from JS
		 * */
		public function setConfig(json:Object):void{
			json = json || {};
			for(var key:String in json){
				this.saveConfig(key, json[key]);
			}	
		}
		public function setConfigs(json:Object):void{	// deprecate
			this.setConfig(json);
		}
		
		public function setBaseurlToLastSelected():void{
			if (this.cfg.baseurl) return;		
			// only set to last_selected if null
			var query:String = "SELECT * FROM local_stores WHERE last_selected=1";
			var dt:Array = Config.sql.execQuery(query);
			if(dt && dt.length){
				this.saveConfig('baseurl',dt[0]['base_url']);	
			}	
		}
		
		public function getBaseurls():Object{
			var resp:Object = [];
			try{
				var query:String = "select * from local_stores order by last_selected ASC";
				var dt:Array = Config.sql.execQuery(query);
				if(dt && dt.length){
					for(var i:int=0;i<dt.length;i++){
						resp.push(dt[i]['base_url']);	
					}
				}
				Config.logger.writeJson("getBaseurls",resp);
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-getBaseUrls');
			}
			return resp;				
		}		
		/*
		* Get all batch ids 
		*/
		public function getBatchIdsFromDB():Object{
			var allitems:Object = new Object();
			try{
				var query:String ;
				//open batch_ids 
				query = "select batch_id,status from uploadQueues where (status!='done' and status!='cancelled') group by batch_id ORDER BY batch_id asc";
				allitems.open = [];
				allitems.closed = [];
				var i:int;
				var dt:Array;
				
				dt = Config.sql.execQuery(query);
				if(dt && dt.length){
					for(i=0;i<dt.length;i++){
						allitems.open.push(dt[i].batch_id);
					}
				}
				//closed batch_ids 
				query = "select batch_id,status from uploadQueues where (status='done' or status='cancelled') and batch_id not in('" + allitems.open.join("','") + "')  group by batch_id ORDER BY batch_id asc";
				dt = Config.sql.execQuery(query);
				if(dt && dt.length){
					for(i=0;i<dt.length;i++){
						allitems.closed.push(dt[i].batch_id);
					}
				}
			}catch(e:Error){
				Config.logger.writeLog("Error",e.message + '-getBatchIdsFromDB');
			}
			return allitems;
			
			/*
			var batch_id:String;
			if(!this.isOldSessionCompleted()){
			//pick old batch_id from configs	 
			batch_id = this.cfg.batch_id;
			}else{ 
			//generate new batch_id for new upload queue
			batch_id = UUID.genUUID();
			}
			return batch_id;
			*/
		}		
		public function deletePhoto(photoIds:Array):Boolean{
			var ret:Boolean, flag:Boolean = true;
			var query:String;
			try {
				var params:Array = [], path:String;
				for(var i:int=0;i<photoIds.length;i++){
					var id:String = photoIds[i+''];
					query =  "SELECT id, base_url, rel_path, upload_status FROM photos WHERE id=@uuid";
					params.push({name:"@uuid",value:id});	  
				
					var photos:Array = Config.sql.executeQueryParams(query, params);
					if (photos && photos.length) {
						var deleteUploadQueue:String = "DELETE FROM uploadQueues WHERE uploadQueues.photo_id=@uuid";
						var deletePhotos:String = "DELETE FROM photos WHERE photo.id=@uuid";
						// TODO: if photos.upload_status==1, should we delete from server, too?
						// TODO: what if the photo has been used/shared? cascade delete?
						// TODO: should we also delete from photos table?
						for(var j:int=0;j<photos.length;j++){
							try {
								// delete row from uploadQueue
								Config.sql.executeNonSQLParams(deleteUploadQueue, params);
								ret = true;
								// delete file from File.ApplicationStorageDirectory()
								var deleteFile:File, size:String, sizes:Array = ['bp','sq','tn'];
								while ( sizes.length ){
									try {
										size = sizes.shift();
										deleteFile = SnappiImage.getImgPathBySize(photos[j]['base_url'], photos[j]['rel_path'], size);
										deleteFile.deleteFile();
									} catch (e:Error) {
									}
								}
							} catch (e:Error) {
								ret = false;
							}
							flag = flag && ret;
						}
					}
				}
			}catch(e:Error){
				flag = false;
				Config.logger.writeLog("Error",e.message + '-deletePhoto');
			}
			return flag;
		}
		public function deleteBaseurl(baseurls:Array):Boolean{
			var ret:Boolean, flag:Boolean = false;
			try{
				for(var i:int=0;i<baseurls.length;i++){
					var base_url:String = baseurls[i+""]; 
					var query:String = "SELECT * FROM local_stores WHERE base_url='" + Config.sql.SQLBug(base_url) + "'";
					var dt:Array = Config.sql.execQuery(query);
					if(dt && dt.length){ //if it is exists then delete it and its photos
						query = "DELETE FROM local_stores WHERE base_url='" + Config.sql.SQLBug(base_url) + "'";
						Config.sql.execQuery(query);
						
						query =  "SELECT id, base_url, rel_path FROM photos WHERE base_url='" + Config.sql.SQLBug(base_url) + "'";
						var photos:Array = Config.sql.execQuery(query);
						if (photos && photos.length) {
							query = "DELETE FROM uploadQueues WHERE uploadQueues.photo_id=@id";
							var params:Array = [], path:String;
							for(var j:int=0;j<photos.length;j++){
								// delete row from uploadQueue
//								params.push({name:"@id", value:photos[j]['id']});
								Config.sql.executeNonSQLParams(query, [{name:"@id", value:photos[j]['id']}]);
								// delete file from File.ApplicationStorageDirectory()
								path = photos[j]['base_url']+ File.separator + photos[j]['rel_path'];
								var deleteFile:File = new File(path);
								if (deleteFile.exists) {
									try {
										deleteFile.deleteFile();
										ret = true;
									} catch (e:Error) {
										ret = false;
									}
								}
								flag = flag && ret;
							}
						}
						query = "DELETE FROM photos WHERE base_url='" + Config.sql.SQLBug(base_url) + "'";
						Config.sql.execQuery(query);
					}
				}
				flag = true;
			}catch(e:Error){
				this.logger.writeLog("Error",e.message + '-deleteBaseurl');
			}
			return flag;
		}		
		
		/**
		 * update methods for photos and uploadQueue
		 * */
		public function setUploadStatus(uuid:String,status:String,batch_id:String=''):Boolean{
			var flag:Boolean = false;
			try{
				var query:String = "UPDATE uploadQueues SET status=@status,updated_on=@updated_on " +
					" WHERE photo_id=@uuid";
				var params:Array = [];
				var updated_on:String = '';
				var has2UpdateisStale:Boolean = false;
				if(status=='done'){
					updated_on =  Misc.convertDateStr(new Date());
					has2UpdateisStale = true;
				}	
				params.push({name:"@status",value:status});
				params.push({name:"@updated_on",value:updated_on});
				params.push({name:"@uuid",value:uuid});
				var batch_id:String = this.cfg.batch_id || '';
				if (batch_id) {
					query += " AND batch_id=@batch_id"; 
					params.push({name:"@batch_id",value:batch_id});	
				}
				Config.sql.executeNonSQLParams(query,params);
				
				//					query = "UPDATE uploadQueues SET status='" + status + "', updated_on='" + updated_on + "'" +
				//						" WHERE photo_id='" + uuid + "'";
				//					if (batch_id) query += " AND batch_id='" + batch_id + "'";  
				//					this.sql.execQuery(query);
				
				if(has2UpdateisStale){
					query = "UPDATE photos SET upload_status=1, isStale=true WHERE id='" + uuid + "'";
					Config.sql.execNonQuery(query); 
				}
				flag = true;
			}catch(e:Error){
				this.logger.writeLog("Error",e.message + '-setUploadStatus');
			}
			return flag;
		}
		
		public function updatePhotoProperties(json:Object):Boolean{
			var flag:Boolean=false
			try{
				var query:String = "SELECT * FROM photos WHERE id='" + json.id + "'";
				var dt:Array = Config.sql.execQuery(query);
				if(dt && dt.length){
					var setparams:String = '';
					var i:int = 0;
					var params :Array = [];
					//add modified date
					json.modified = Misc.convertDateStr(new Date());
					for(var k:String in json){
						if(k!='id'){
							if(typeof(dt[0][k])!='undefined'){ //if field exists then update it
								if(i>0){
									setparams += " , ";
								}
								i++;
								setparams += " "  + k + "=@" + k;
								params.push({name:"@" + k,value:json[k]});	   			
							}	
						}
					}
					
					params.push({name:"@id",value:json.id});
					query = "UPDATE photos SET " + setparams + " WHERE id=@id"; 
					Config.sql.executeNonSQLParams(query,params);
					flag = true;		
				}else{
					throw new Error("Photo Not Found With Id = " + json.id);
				}
			}catch(e:Error){
				flag = false;
				Config.logger.writeLog("Error",e.message + '-updatePhotoProperties');
			}
			return flag;
		}
		
		
		/*
		* public methods to fetch castingCall response as async
		* params - accept two params first param is json object as cfg e.g. = {
		* 															 page:1,
		* 															 perpage:10,
		* 															 rating:1, //optional
		* 															 dateFrom:'2010:20:04 18:22:57', //optional
		* 															 dateTo:'2010:20:04 18:22:57', //optional
		* 															 tags : 'cars,red' 	 //optional
		* 															}
		* 			second is callback json {success:fn,failure:fn,arguments:{},scope:obj}	
		*/		
		public function getCastingCall(qs:Object, callback:Object):Object{
			try{
				var params:Object = callback.arguments;
			}catch(e:Error){	
				params = {};
			}
			try{
				var baseurl:String = qs.baseurl || '';
				var rating:int = qs.rating || -1;
				var dateFrom:String = qs.dateFrom || '';
				var dateTo:String = qs.dateTo || '';
				var tags:String = qs.tags || '';
				var page:int = qs.page-1; //cause in sqlite page starts from 0
				var perpage:int = qs.perpage;
				var whereQuery : String = " WHERE 1=1";
				if (baseurl) {
					whereQuery = whereQuery  + " AND base_url='" + Config.sql.SQLBug(baseurl) + "'";						
				}
				if(rating!=-1){ //if rating defined means not -1
					whereQuery = whereQuery  + " AND rating>=" + rating;
				}
				if(StringUtil.trim(dateFrom).length){
					whereQuery = whereQuery  + " AND date_taken>='" + StringUtil.trim(dateFrom) + "'";
				}
				if(StringUtil.trim(dateTo).length){
					whereQuery = whereQuery  + " AND date_taken<='" + StringUtil.trim(dateTo) + "'";
				}
				if(StringUtil.trim(tags).length){
					var tgs:Array = tags.split(",");
					var tagsCond:String = '';
					for(var i:int;i<tgs.length;i++){
						if(i>0){
							tagsCond += ' and ';
						}
						tagsCond += " tags LIKE '%" + tgs[i] + "%'";
					}
					whereQuery = whereQuery  + ' and ' + tagsCond;
				}
				
				var query:String = "SELECT count(*) AS total_rows from PHOTOS " + whereQuery;
				
				var dt:Array = Config.sql.execQuery(query);
				var json:Object = {};
				if(dt && dt.length){
					var total_rows:int = dt[0]['total_rows'];
					page = perpage * page;
					query = "SELECT * FROM photos " + whereQuery +  " LIMIT " + page + "," + perpage;
					dt = Config.sql.execQuery(query);
					if(dt && dt.length){
						json = Misc.createSnaphappiJSON(dt,total_rows,qs);
					}
				}
				params.success = true;
				if (callback === null) {
					return json;
				} else { 
					Config.logger.writeJson("getCastingCall",json);
					callback.success.call(callback.scope || Config.jsGlobal, json,params);
					return true;
				}
			}catch(e:Error){
				params.success = false;
				Config.logger.writeLog("Error",e.message + '-getCastingCall');
				if (callback !== null) {
					callback.failure.call(callback.scope || Config.jsGlobal,{error:e.message},params);
				} 
				return false;
			}
			return false;
		}	
		

	}
}