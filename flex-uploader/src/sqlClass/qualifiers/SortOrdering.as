package sqlClass.qualifiers{
	
	public class SortOrdering extends Object{
		
		import flash.data.SQLStatement;
		
		public static const kAscending:Boolean = true;
		public static const kDescending:Boolean = false;
		
		private var sortArgs:Array;
		
		public function SortOrdering(){
			super();
			sortArgs = new Array();
		}
		
		public function add(c:String, d:Boolean = kAscending):void{
			sortArgs.push(new OrderingItem(c, d));
		}
		
		public function resolveFor(eoClass:Class):String{
			var orderString:String = " ORDER BY ";
			var item:OrderingItem;
			for each (item in sortArgs){
				orderString += (new eoClass()).sqlNameForVarName(item.column);
				if (!item.direction)
					orderString += " DESC";
				orderString += ",";
			}
			return orderString.substring(0, orderString.length - 1); // remove last comma
		}

// end of class		
	}
}

// =======================================================================

// Items to be stored in sortArgs
class OrderingItem extends Object{
	
	public var column:String;
	public var direction:Boolean; // kAscending kDescending - Defaults to kAscending
	
	public function OrderingItem(c:String, d:Boolean){
		super();
		column = c;
		direction = d
	}
}
