
package sqlClass{
	import com.dehats.air.sqlite.SimpleEncryptionKeyGenerator;
	
	import flash.data.SQLMode;
	import flash.data.SQLSchemaResult;
	import flash.utils.ByteArray;

// Standard file used by NeoQuant to access SQLite database	
	[Bindable]
	public class EditingContext extends Object{
		
		import flash.filesystem.File;
		import flash.data.SQLResult;
		import flash.data.SQLStatement;
		import flash.data.SQLConnection;
		import flash.events.SQLEvent;
		import flash.events.SQLErrorEvent;
		import flash.data.SQLTableSchema;
		import flash.data.SQLColumnSchema;
		import flash.errors.SQLError;
		import flash.utils.getQualifiedClassName;
		
		
		private static const kInsertRequest:int = 0;
		private static const kUpdateRequest:int = 1;
		private static const kDeleteRequest:int = 2;
		private static const kRubbishRequest:int = 3;
		
		private static var requestVerb:Array = ["Inserting ", "Updating ", "Deleting ", "Binning "];
		
		private static const kForUpdate:Boolean = true;
		private static const kForInsert:Boolean = false;
		
		private var connection:SQLConnection;
		private var dbStatement:SQLStatement;			// Same statement used throughout
		
		private var pkChanged:Boolean = false;
		private var dbName:String;
		public static var isAttached:Boolean=false;

		
		public function EditingContext(dbName:String, tableCreator:Function = null,password:String=null,whereto:String='app-storage'){
				
				
			super();
			var bytes:ByteArray;
			
			if (password!=null)
			{
				
				bytes = new ByteArray();
				var s:SimpleEncryptionKeyGenerator=new SimpleEncryptionKeyGenerator();
				bytes=s.getEncryptionKey(password);
							
				//bytes=generateEncryptionKey(password);
			}			
			var dbFile:File;
			if(whereto=='app'){
				dbFile = File.applicationDirectory.resolvePath(dbName);
			}else{
				dbFile = File.applicationStorageDirectory.resolvePath(dbName);
			}
			var fileExisted:Boolean = dbFile.exists;
			connection = new SQLConnection();
			connection.open(dbFile,SQLMode.UPDATE,false,1024, bytes);
		}
		
			
		public function close():void{
			connection.close();
		}
		
// Utilities
		public function executeSQL(sqlString:String, eoClass:Class = null):Array{
			var returnArray:Array = new Array();
			//if (eoClass)
            //	dbStatement.itemClass = eoClass; // If it's a select statement
			dbStatement=new SQLStatement();
			dbStatement.sqlConnection=connection;
			dbStatement.text = sqlString;
			dbStatement.execute();
			var resultData:Array = dbStatement.getResult().data;
			dbStatement.clearParameters();
		    return resultData;
		}
		public function executeNonSQLParams(sqlString:String,params:Array):Array{
			var returnArray:Array = new Array();
			//if (eoClass)
            //	dbStatement.itemClass = eoClass; // If it's a select statement
			dbStatement=new SQLStatement();
			dbStatement.sqlConnection=connection;
			dbStatement.text = sqlString;
			for(var i:int=0;i<params.length;i++){
				dbStatement.parameters[params[i].name] = params[i].value;
			}
			dbStatement.execute();
			var resultData:Array;
			if(sqlString.indexOf("insert")>=0){
				resultData = [];
				resultData.push({id:connection.lastInsertRowID}); 
			}
		    return resultData;
			
		}
		public function executeQueryParams(sqlString:String,params:Array):Array{
			var returnArray:Array = new Array();
			//if (eoClass)
            //	dbStatement.itemClass = eoClass; // If it's a select statement
			dbStatement=new SQLStatement();
			dbStatement.sqlConnection=connection;
			dbStatement.text = sqlString;
			for(var i:int=0;i<params.length;i++){
				dbStatement.parameters[params[i].name] = params[i].value;
			}
			dbStatement.execute();
			var resultData:Array = dbStatement.getResult().data;
			dbStatement.clearParameters();
		    return resultData;
		}
		
		public function executeNonSQL(sqlString:String, eoClass:Class = null):Array{
			var returnArray:Array = new Array();
			//if (eoClass)
            //	dbStatement.itemClass = eoClass; // If it's a select statement
			dbStatement=new SQLStatement();
			dbStatement.sqlConnection=connection;
			dbStatement.text = sqlString;
			dbStatement.execute();
			var resultData:Array;
			if(sqlString.indexOf("insert")>=0){
				resultData = [];
				resultData.push({id:connection.lastInsertRowID}); 
			}
		    return resultData;
		}
		public function getSchema(tablename:String):Array{
			connection.loadSchema(SQLTableSchema,tablename,"main",true);
			var r:SQLSchemaResult = connection.getSchemaResult();
			var columns:Array = [];
			var table:Object = r.tables[0];
			for(var i:int=0,len:int=table.columns.length;i<len;i++){
				var column:Object = {};
				column.name = table.columns[i].name;
				column.type = table.columns[i].dataType;
				column.pk = table.columns[i].primaryKey;
				columns.push(column);
			}
			return columns;
		}
	}
}

