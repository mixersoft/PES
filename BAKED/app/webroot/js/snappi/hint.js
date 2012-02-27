(function(){
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Hint = function(Y){
		if (_Y === null) _Y = Y;
		
		/*
		 * make global
		 */
	    SNAPPI.Hint = Hint;
	    // load Hint.doNotShow from Cookie or Session
	    Hint.doNotShow = _Y.Cookie.getSubs('donotshow') || {};
	    Hint.flushQueue();		// load queued hints
	}
	
    var Hint = function(){
    	if (Hint.instance) return Hint.instance;
    	Hint.instance = this;
    };
    /*
     * static properties
     */
    Hint.doNotShow = {};			// hidden by user
    Hint.CFG = {					// id:CSS_ID
    	HINT_MultiSelect:{
    		css_id:'hint-multiselect-markup', 
    		delay:5000,
    		align:  { points: [ 'bc', 'tc' ] },
    	},
    }
    Hint._default = {
    	align:  { points: [ 'bl', 'tr' ] }
    }
    Hint.markup = {
    	'close': "<span class='close btn white right'>X</span>",
    	'doNotShow': "<div class='hint-footer'><span class='show-next right btn white'>Next Tip &#x25B6;</span><p><input type='checkbox' class='do-not-show' title='Make a note in your browser cookie not to show this hint again.' id='{id}'> Hide this tip.</p></div>",
    }
    Hint.flushQueue = function() {
    	// load queued hints
	    for (var i in SNAPPI.STATE.hints) {
	    	if (SNAPPI.STATE.hints[i] !== 'loaded') {
	    		Hint.load(SNAPPI.STATE.hints[i]);	
	    		SNAPPI.STATE.hints[i] = 'loaded';
	    	}
	    }
    }
    Hint.find = {};
    /**
     * load the hint
     * @params cfg.id string, appears in Hint.CFG, example: HINT_MultiSelect
     * @params cfg.css_id string, CSS id of markup
     * @params cfg.delay int, (optional) milliseconds until hint is displayed
     * @params cfg.trigger, Node or selector
     */
    Hint.load = function(cfg){
    	if (Hint.doNotShow[cfg.id]) return;
    	var cfg2 = (_Y.Lang.isObject(Hint.CFG[cfg.id])) ? Hint.CFG[cfg.id] : {};
    	cfg = _Y.merge(Hint._default, cfg2, cfg);
    	var cancel = SNAPPI.namespace('SNAPPI.timeout.hint');
    	var CSS_ID = cfg.css_id;
    	cfg.delay = cfg.delay || Hint.CFG[cfg.id]['delay'];
    	var body = _Y.one('#markup #'+CSS_ID);
    	var hint = new _Y.Tooltip({
			trigger: cfg.trigger,
			bodyContent: body,
			align: cfg.align,
		});
		Hint.find[cfg.id] = hint;
		hint._cfg = cfg;
		
    	body.listen = body.listen || {};
    	/*
    	 * close listener
    	 */
    	if (!body.listen['close']) {
    		body.listen['close'] = body.delegate('click', function(e){
    			try {
    				var hide = e.container.one('input.do-not-show');
    				if (hide.get('checked')) {
    					// TODO: save to Session or Cookie
    					SNAPPI.Hint.doNotShow[hide.get('id')] = 1;
    					_Y.Cookie.setSub('donotshow', cfg.id, 1, {
    						path: '/',
    						expires: new Date(+new Date + 12096e5),
    					})
    				}
    			} catch(e) {}
    			hint.hide();	// closure
    			hint.set('trigger', '#blackhole');
    		}, 'span.close', body);
    		// add close button to body > h1 > span.close
    		if (!body.one('h1 > span.close')) {
    			try {
    				body.one('h1').append(Hint.markup.close);	
    			} catch (e){}
    		}
    		// add checkbox for body > input.do-not-show
    		if (!body.one('input[type=checkbox].do-not-show')) {
    			body.append(_Y.substitute(Hint.markup.doNotShow, cfg)); // requires cfg.id	
    		}
    		body.listen['show-next'] = body.delegate('click', function(e){
    			try {
    				var first, found, next,
    					current = hint._cfg.id,	// cfg.id of hint (closure)
    					copy = _Y.merge(SNAPPI.STATE.hints);
    				for (var i in copy) {
    					first = first || i;
    					if (found) {
    						next = copy[i];
    						break; 
    					} else if (copy[i] == current) found = true;
    				}
    				if (!next) next = first;
    				if (next == current) e.currentTarget.addClass('disabled');
    				else {
    					hint.hide();	// closure
    					Hint.find[next].show();
    				}
    			}catch(e){}
    		}, 'span.show-next', body);
    	}
    	
		if (cfg.delay) {
			cancel[cfg.id] = _Y.later(cfg.delay, hint, function(trigger){
				hint.render();
			}, cfg.trigger);
		} else hint.render();
};
})();