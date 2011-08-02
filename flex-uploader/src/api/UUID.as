package api
{
	public class UUID
	{
		private static var CHARS:Array = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
		public function UUID()
		{
		}
  	  public static function genUUID(len:int=0, radix:int=10):String {
	    var chars:Array = CHARS;
	    var uuid:Array = [];
	    radix = radix || chars.length;
	    var i:int;
	    if (len) {
	      // Compact form
	      for (i = 0; i < len; i++) uuid[i] = chars[0 | Math.random()*radix];
	    }else{
	      // rfc4122, version 4 form
	      var r:Number;
	      // rfc4122 requires these characters
	      uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
	      uuid[14] = '4';
	      // Fill in random data.  At i==19 set the high bits of clock sequence as
	      // per rfc4122, sec. 4.1.5
	      for (i = 0; i < 36; i++) {
	        if (!uuid[i]) {
	          r = 0 | Math.random()*16;
	          uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
	        }
	      }
	    }
	    return uuid.join('');
	  }
	  // A more performant, but slightly bulkier, RFC4122v4 solution.  We boost performance
	  // by minimizing calls to random()
	  public static function uuidFast():String {
	    var chars:Array = CHARS;
	    var uuid:Array = new Array(36);
	    var rnd:int=0; 
	    var r:Number;
	    for (var i:int = 0; i < 36; i++) {
	      if (i==8 || i==13 ||  i==18 || i==23) {
	        uuid[i] = '-';
	      } else {
	        if (rnd <= 0x02) rnd = 0x2000000 + (Math.random()*0x1000000)|0;
	        r = rnd & 0xf;
	        rnd = rnd >> 4;
	        uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
	      }
	    }
	    return uuid.join('');
	  }
	  // A more compact, but less performant, RFC4122v4 solution:
	  public static function uuidCompact():String {
	    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function():String {
	      var c:String = arguments[0]; 
	      var r:String = Math.random()*16|0;
	      var v:String = (c == 'x') ? r : (r&0x3|0x8);
	      return v.toString(16);
	    }).toUpperCase();
	  }

	}
}