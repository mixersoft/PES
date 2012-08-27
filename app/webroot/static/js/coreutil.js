(function(){
	/***************************************************************************
	 * Util Class def
	 */
	var Util = function(){};
	Util.prototype = {
		TYPES: {
		    '[object Function]' : 'function',
		    '[object Array]' : 'array',
		    '[object ScriptBridgingArrayProxyObject]' : 'array', 	// for AIR flex/js bridge
		    '[object RegExp]' : 'regexp',
		    '[object Date]' : 'date',
		    '[object Error]' : 'error'
		}, 
		type : function(o) {
			var prototype = Object.prototype.toString.call(o);
			if (prototype === undefined) return o 
			else {
				return this.TYPES[prototype] || (prototype ? 'object' : 'null');
			}
		},
		isUndefined: function(o) {
		    return typeof o === 'undefined';
		},
		isFunction : function(o) {
		    return this.type(o) === 'function';
		},
		isBoolean : function(o) {
		    return typeof o === 'boolean';
		},
		isArray : function(o) {
		    return this.TYPES[Object.prototype.toString.call(o)]  === 'array';
		},
		isNumber : function(o) {
		    return typeof o === 'number'//  && isFinite(o);
		},
		isObject : function(o, failfn) {
		    var t = typeof o;
		    return (o && (t === 'object' ||
		        (!failfn && (t === 'function' || this.isFunction(o))))) || false;
		},
		isString : function(o) {
		    return typeof o === 'string';
		},
		isRegexp: function(o) {
		    return this.type(o) === 'regexp';
		},
		isDate : function(o) {
		    // return o instanceof Date;
		    return this.type(o) === 'date' && o.toString() !== 'Invalid Date'; // && !isNaN(o);
		},
		// merge properties of anonymous objects
		merge: function(a, b){
		    if (!this.isObject(a, true)){
		    	return false;
		    }
		    if (!this.isObject(b, true)){
		    	var b = a, a = {};
//		    	return this.copy(a);
		    }
		    for (var p in b) {
		    	if (b.hasOwnProperty(p)) a[p] = b[p];
		    }
		    return a;
		},
		// sort of a deep copy. 
		copy: function(o){
		    if (o == null || !this.isObject(o, false)) 
		        return o;
		    if (this.isArray(o)) {
		        var temp = [];
		        for (var i = 0; i < o.length; i++) {
		            // special code for dataElement.boundTo =[]
		            if (typeof(o[i]) == 'object' && !(o[i] instanceof HTMLElement)) 
		                temp.push(this.copy(o[i]));
		            else 
		                temp.push(o[i]);
		        }
		        return temp;
		    }
		    else 
		        if (this.isRegexp(o)) {
		            return o;
		        }
		    // object or function
		    var temp = {};
		    for (var key in o) 
		        temp[key] = this.copy(o[key]);
		    return temp;
		}	
	};
	SNAPPI.coreutil = new Util();
	/**
	 * end Util class
	 ******************************************************************************/
})();