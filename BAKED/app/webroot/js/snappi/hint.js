(function(){
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Hint = function(Y){
		if (_Y === null) _Y = Y;
		
		Hint.overlayManager = new _Y.OverlayManager();
		
		/*
		 * make global
		 */
	    SNAPPI.Hint = Hint;
	    // load Hint.doNotShow from Cookie or Session
	    Hint.doNotShow = _Y.Cookie.getSubs('donotshow') || {};
	    Hint.flushQueue();		// load queued hints
	}
	
    var Hint = function(){};
	/*
	 * 
	 */
    Hint.CFG = {					// id:CSS_ID
    	HINT_MultiSelect:{
    		css_id:'hint-multiselect-markup', 
    		// showDelay:5000,
    		align:  { points: [ 'tc', 'tc' ] },
    		trigger: 'section.gallery.photo .container',
    	},
    	HINT_ContextMenu:{
    		css_id:'hint-contextmenu-markup', 
    		// showDelay:5000,
    		align:  { points: [ 'tc', 'tc' ] },
    		trigger: 'section.gallery.photo .container',
    	},
    }
    /*
     * static properties
     */        
    Hint.instance = null;			// singleton class
    Hint.doNotShow = {};			// hidden by user, load from Cookie
    Hint.lookupHintByTrigger = {}	// struct to lookup hint.body from trigger
    Hint.active = {					// set in _getHintMarkupFromTrigger()
    	trigger: null,
    	body: null,
    	cfg: null,
    }

    /*
     * Static methods 
     */
    Hint.flushQueue = function() {
    	// load queued hints
	    for (var i in SNAPPI.STATE.hints) {
	    	if (SNAPPI.STATE.hints[i] !== 'loaded') {
	    		var loadCfg = SNAPPI.STATE.hints[i];
	    		if (!loadCfg.id) loadCfg = {id: i}; 
	    		Hint.load(loadCfg);	
	    		SNAPPI.STATE.hints[i] = 'loaded';
	    	}
	    }
    }
    /*
     * private attributes
     */
    var _defaultCfg = {
    	align:  { points: [ 'bl', 'tr' ] },
    	constrain: true,
    }
    var _bodyMarkup = {
    	'close': "<span class='close btn white right'>X</span>",
    	'doNotShow': "<div class='hint-footer'><span class='show-next right btn white'>Next Tip &#x25B6;</span><p><input type='checkbox' class='do-not-show' title='Make a note in your browser cookie not to show this hint again.' id='{id}'> Hide this tip.</p></div>",
    }
    /*
     * "private" helper methods
     */
    /*
     * register Hint cfg for lookup via trigger on Hint.lookupHintByTrigger
     */
    var _registerHint = function(hint, cfg, body){
    	Hint.instance = Hint.instance || hint;
    	var update, triggers = cfg.trigger.split(',');
    	var foundByTrigger, trigger, row;
		for (var i in triggers) {
			trigger = triggers[i].trim();
			foundByTrigger = Hint.lookupHintByTrigger[trigger];
			row = {
				cfg: cfg,
				body: body,
			};
			if (foundByTrigger && _Y.Lang.isArray(foundByTrigger)) foundByTrigger.push(row);
			else Hint.lookupHintByTrigger[trigger] = [row];
		}
    }
    /*
     * set hint body based on the node/selector which triggered the hint
     * 		called by hint.on('visibleChange') before hint.show();
     * @params hint, Hint object
     * @params node, the node which triggered, i.e. Hint.get('currentNode');
     * @return found;
     */ 
    var _setHintBodyByTriggerNode = function(hint, node){
    	node = node || hint.get('currentNode');
    	// find the next valid Hint markup, using .test() and setBodyContent
    	// if no more, then check if there is another Hint.trigger that was suppressed
    	try {
    		var triggerSelectors = hint.triggers;	
    	} catch(e){
    		console.error("Error Hint:_setHintBodyByTriggerNode(). trigger is not a Selector String");
    	}
    	
    	var i, found;
    	for (var i in triggerSelectors) {
    		if (node.test(triggerSelectors[i]) 
    			&& (found = _getHintMarkupFromTrigger(triggerSelectors[i], true))
    		){
    			// found == Hint.active
    			hint.set('bodyContent', Hint.active.body);
console.log('hint.body set to '+found.cfg.id)    
				break;			
    		}
    	}
    	return found;
    	
    }
    /*
     * get first valid Hint body 'after' current hint 
     * @params trigger string. CSS Selector string
     * @params anyTrigger Boolean. if true, scan entire Hint.lookupHintByTrigger tree
     * @return object, value from Hint.lookupHintByTrigger[trigger]
     */
    var _getHintMarkupFromTrigger = function(trigger, anyTrigger){
    	// iterate through HintMarkup, check _checkDoNotShow()
    	trigger = trigger.trim();
    	var current, first, found, hintDesc = Hint.lookupHintByTrigger[trigger];
    	if (Hint.active.trigger == trigger) current = Hint.active;
    	if (_Y.Lang.isArray(hintDesc)){
    		// get first valid object after current
    		for (var i in hintDesc){
console.log("scanning for active hints. trigger="+trigger+", hintId="+hintDesc[i].cfg.id);    			
    			if (current && hintDesc[i].body!==current.body) {
    				if (!first && !_checkDoNotShow(hintDesc[i])) first = hintDesc[i];
    				continue;
    			} else if (current && hintDesc[i].body==current.body) {
    				continue;
    			}
    			if (!_checkDoNotShow(hintDesc[i])) {
    				found = hintDesc[i];
    				break;
    			}
    		}
    		if (current && current.body && !found && first) found = first;
    	} else if (!_checkDoNotShow(hintDesc)){
    		found = hintDesc;
    	}
    	if (found) {
    		Hint.active = {
	    		trigger: trigger,
	    		body: found.body,
	    		cfg: found.cfg,
	    	}	
	    	return found;
    	} else if (anyTrigger) {	// 'recurse' through Hint.lookupHintByTrigger
    		for (var j in Hint.lookupHintByTrigger) {
    			if (j == trigger) continue;
    			found = _getHintMarkupFromTrigger(j, false);
    			if (found && current 
    				&& found.body !== current.body 
    				&& !_checkDoNotShow(found)
    			) return found;
    		}
    	} 
    	return false;
    }
    var _checkDoNotShow = function (o){
    	return o && (Hint.doNotShow[o.cfg.id] ? true : false);
    }
    var _addDoNotShow = function(id){
    	Hint.doNotShow[id] = 1;
		// _Y.Cookie.setSub('donotshow', id, 1, {
			// path: 'dev.snaphappi.com',
			// expires: new Date(+new Date + 12096e5),
		// });
    }
    
    var _handle_VisibleChange = function(e){
		if (e.newVal == true && e.prevVal==false) {
			// this == hint
			// var trigger = e.currentTarget._cfg.trigger;
			var triggerNode = this.get('currentNode');
			var found = _setHintBodyByTriggerNode(this, triggerNode);
			if (!found && _checkDoNotShow(Hint.active)) {
				this.set('trigger', '#blackhole');
console.warn("visibleChange:: trigger set to #blackhole");
				e.halt();
				return;
			} // else just show active
			var footer = this.getStdModNode('footer');
			footer.one('span.show-next').removeClass('disabled');
    		footer.one('.do-not-show').set('id', Hint.active.cfg.id).set('checked', false);
		} else if (e.newVal == false && e.prevVal== true) {
			// var body = e.currentTarget.get('bodyContent') !== Hint.active.body;
		}
	}
	
	var _handle_Close = function(e, contentBox){
		try {		// context: this = hint or ToolTip
			var hide = e.container.one('input.do-not-show');
			if (hide.get('checked')) _addDoNotShow(Hint.active.cfg.id); 
		} catch(e) {}
		this.hide();
		var check;
	}
	var _handle_ShowNextTip = function(e){ // context: this = hint or ToolTip
		if (e.currentTarget.hasClass('disabled')) return;
		var footer = this.getStdModNode('footer');
		if (footer.one('.do-not-show').get('checked')){
			_addDoNotShow(Hint.active.cfg.id);
			footer.one('.do-not-show').set('checked', false);
		}
		var triggerNode = this.get('currentNode');
		var found = _setHintBodyByTriggerNode(this, triggerNode);
		if (!found) e.currentTarget.addClass('disabled');
		var check;
	}
    
    /**
     * load the hint
     * @params cfg.id string, appears in Hint.CFG, example: HINT_MultiSelect
     * @params cfg.css_id string, CSS id of markup
     * @params cfg.delay int, (optional) milliseconds until hint is displayed
     * @params cfg.trigger, Node or selector
     */
    Hint.load = function(cfg){
    	if (Hint.doNotShow[cfg.id]) return;	
    	
    	if (_Y.Lang.isObject(Hint.CFG[cfg.id])) {
    		cfg = _Y.merge(_defaultCfg, Hint.CFG[cfg.id], cfg);
    	} else cfg = _Y.merge(_defaultCfg, cfg2, cfg);
    	var cancel = SNAPPI.namespace('SNAPPI.timeout.hint');
    	var CSS_ID = cfg.css_id;
    	var body = _Y.one('#markup #'+CSS_ID);
    	
    	
    	// use only 1 hint, modify trigger, bodyContent
    	// var hint = Hint.findByTrigger[cfg.trigger];
    	var hint = Hint.instance;
    	if (!hint) { // create new hint
	    	hint = new _Y.Tooltip(cfg).render();
			// register Hint
    		_registerHint(hint, cfg, body);
			hint.triggers = cfg.trigger.split(',');		// array of Selector strings
			for (var i in hint.triggers) {
    				hint.triggers[i] = hint.triggers[i].trim();
    		}
			// set the tip before show
    		hint.set('headerContent', _bodyMarkup.close);
    		hint.set('footerContent', _Y.substitute(_bodyMarkup.doNotShow, cfg));
    		hint.on('visibleChange', _handle_VisibleChange, hint);
    		contentBox = hint.get('contentBox');
    		contentBox.listen = {};
			contentBox.listen['close'] = contentBox.delegate('click', _handle_Close, 'span.close', hint, contentBox);
    		contentBox.listen['show-next'] = contentBox.delegate('click', _handle_ShowNextTip, 'span.show-next', hint);    		
    	}else{		// reuse hint
    		try {
    			var update, trigger, triggers = cfg.trigger.split(',');
    			for (var i in triggers) {
    				trigger = triggers[i].trim();
    				if (hint.triggers.indexOf(trigger)==-1) {
		    			hint.triggers.push(trigger);
		    			update = true;
		    		}
    			}
    			if (update || hint.get('trigger') == '#blackhole') hint.set('trigger', hint.triggers.join(','));
    		} catch(e){
    			console.error("Error: Hint.load(): expecting trigger to be a selector string");
    		}
    		// body will be updated in hint.on('visibleChange') after _registerHint()
    		// register Hint
    		_registerHint(hint, cfg, body);
    	}
};
})();