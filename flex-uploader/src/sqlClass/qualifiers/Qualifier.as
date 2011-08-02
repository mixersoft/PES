package sqlClass.qualifiers{
	
	public class Qualifier extends Object{
		
		import flash.data.SQLStatement;
		import mx.utils.ObjectUtil;
		

		
		import mx.controls.Alert;
		
		private var args:Array;
		
		public function Qualifier(){
			super();
			args = new Array;
		}
		
		public function add(c:String, o:String, v:Object, ct:String = null ):void{
			args.push(new QualifierItem(c, o, v, ct));
		}
		
		public function appendQualifier(q:Qualifier, ct:String = null):void{
			var qi:QualifierItem;
			for each (qi in q.args)
				this.args.push(qi);
		}
		
		public function resolveFor(statement:SQLStatement, eoClass:Class):String{
			var paramIndex:int = 0;
			var paramsString:String = "(";
			for (var i:int=0; i<args.length; i++){
				if (i > 0)
					paramsString += args[i].cat ? " " + args[i].cat + " " : " AND ";
				paramsString += (new eoClass()).sqlNameForVarName(args[i].column);
				if (args[i].value == null){
					if (args[i].operator == "=")
						paramsString += " IS NULL ";
					else
						paramsString += " IS NOT NULL ";
				}
				else{
					paramsString += " " + args[i].operator + " ? ";
					if (ObjectUtil.isSimple(args[i].value))
						statement.parameters[paramIndex] = args[i].value;
					paramIndex++;
				}
			}
			return paramsString + ")";
		}
		
	}
}
// =======================================================================

// Items to be stored in args array
class QualifierItem extends Object{
	
	public var column:String;
	public var operator:String; // = LIKE ...
	public var value:Object;
	public var cat:String;		// AND OR ... Defaults to AND
	
	public function QualifierItem(c:String, o:String, v:Object, ct:String = null ){
		super();
		column = c;
		operator = o;
		value = v;
		cat = ct;
	}
}
