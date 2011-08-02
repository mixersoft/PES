package com.gnstudio.nabiro.utilities.data
{
	import com.gnstudio.nabiro.utilities.mock.core.Pair;

	public class HashItem extends Pair
	{
		public function HashItem(n:String=null, v:*=null)
		{
			super(n, v);
		}
		
		private var _next:HashItem;
		
		public function set next(value:HashItem):void{
			
			_next = value;
			
		}
		
		public function get next():HashItem{
			
			return _next;
			
		}
		
		private var _previous:HashItem;
		
		public function set previous(value:HashItem):void{
			
			_previous = value;
			
		}
		
		public function get previous():HashItem{
			
			return _previous;
			
		}
		
	}
}