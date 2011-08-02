package api
{
	import flash.filesystem.File;
	import api.Config;
	
	import sqlClass.EditingContext;

	public class SqlHandler
	{
		private var db_name:String = 'snaphappi.db3';
		private var db:File;
		public function SqlHandler(db_name:String = 'snaphappi.db3', db_root:File = null):void{
			this.db_name = db_name;
			if (db_root === null || !db_root.exists || !db_root.isDirectory) {
				this.db =  File.applicationStorageDirectory.resolvePath('db' + File.separator + this.db_name);	
			} else {
				this.db = new File(db_root.nativePath + File.separator + this.db_name); 
			}
		}
		public function cloneDb(sourceDb:File, replace:Boolean = false):Boolean {
			if (this.db.exists && replace == false) return true;
			try{
				sourceDb.copyTo(this.db, replace);
				// copyTo creates any required parent directories
				return true;
			}catch(e:Error){
				if (replace === false) return true;
				else Config.logger.writeLog("Error",e.message + '-' + e.errorID + '-SqlHandler::cloneDb'); 
			}
			return false;
		}
		public function getDbFile():String{
			return this.db.nativePath;
		}
		public function execQuery(query:String, whereto:String='app-storage'):Array{
			var arr:Array=[];
			var sqlconn:EditingContext = null;
			try{
				var pwd:String = null;
				sqlconn = new EditingContext(this.getDbFile(),null,pwd,whereto);
				arr = sqlconn.executeSQL(query);
			}catch(e:Error){
				throw new Error(e.message);
			}finally{
				if(sqlconn!=null){
					sqlconn.close();
				}				
			}	
			return arr;						
		}
		public function execNonQuery(query:String):Array{
			var arr:Array=[];
			var sqlconn:EditingContext = null;
			try{
				var pwd:String = null;
				sqlconn = new EditingContext(this.getDbFile(),null,pwd);
				arr = sqlconn.executeNonSQL(query);
			}catch(e:Error){
				throw new Error(e.message);
			}finally{
				if(sqlconn!=null){
					sqlconn.close();
				}				
			}	
			return arr;						
		}	
		public function executeNonSQLParams(query:String,params:Array):Array{
			var arr:Array=[];
			var sqlconn:EditingContext = null;
			try{
				var pwd:String = null;
				sqlconn = new EditingContext(this.getDbFile(),null,pwd);
				arr = sqlconn.executeNonSQLParams(query,params);
			}catch(e:Error){
				throw new Error(e.message);
			}finally{
				if(sqlconn!=null){
					sqlconn.close();
				}				
			}	
			return arr;						
		}
		public function executeQueryParams(query:String,params:Array):Array{
			var arr:Array=[];
			var sqlconn:EditingContext = null;
			try{
				var pwd:String = null;
				sqlconn = new EditingContext(this.getDbFile(),null,pwd);
				arr = sqlconn.executeQueryParams(query,params);
			}catch(e:Error){
				throw new Error(e.message);
			}finally{
				if(sqlconn!=null){
					sqlconn.close();
				}				
			}	
			return arr;						
		}		
		public function getColumnList(tablename:String):Array{
			var sqlconn:EditingContext = null;
			var columns:Array = [];
			try{
				var pwd:String = null;
				sqlconn = new EditingContext(this.getDbFile(),null,pwd);
				columns = sqlconn.getSchema(tablename); 
			}catch(e:Error){
				columns = null;
			}finally{
				if(sqlconn!=null){
					sqlconn.close();
				}				
			}	
			return columns;
		}		
				
		public function SQLBug(str:String):String{
			return str.replace(/\'/ig,'"');
		}
			
	}
}