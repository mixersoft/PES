(function(){
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Hint = function(Y){
		if (_Y === null) _Y = Y;
		Hint.overlayManager = new _Y.OverlayManager();
		Hint.lookupHintByTriggerSH = new SNAPPI.SortedHash();
		/*
		 * make global
		 */
	    SNAPPI.Hint = Hint;
	    // load Hint.doNotShow from Cookie or Session
	    // Hint.doNotShow = _Y.Cookie.getSubs('SNAPPI_doNotShow') || {};
	    Hint.doNotShow = {}
	    Hint.cookie = {};
	    Hint.flushQueue();		// load queued hints
	}

    var Hint = function(){};
	/*
	 * 
	 */
    Hint.CFG = {					// id:CSS_ID
    	HINT_Preview:{
    		css_id:'hint-preview-markup',
    		uri: '/help/markup/tooltips', 
    		showDelay: 100,
    		showArrow: false,
    		align:  { points: [ 'tc', 'tc' ] },
    		trigger: 'body',
    		anchor: '#content',
    	},
    	HINT_MultiSelect:{
    		css_id:'hint-multiselect-markup',
    		uri: '/help/markup/tooltips', 
    		showDelay:9000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: 'section.gallery.photo .container',
    		anchor: 'section.gallery.photo .container .FigureBox.Photo:first-child',
    	},
    	HINT_ContextMenu:{
    		css_id:'hint-contextmenu-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:9000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: 'section.gallery.photo .container',
    		anchor: 'section.gallery.photo .container .FigureBox.Photo:first-child .icon.context-menu',
    	},
    	HINT_Bestshot:{
    		css_id:'hint-bestshot-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:9000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: 'section.gallery.photo .container',
    		anchor: 'section.gallery.photo .container .FigureBox.Photo:first-child',
    	},
    	HINT_HiddenShot:{
    		css_id:'hint-hiddenshot-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:9000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: 'section.gallery.photo .container',
    		anchor: 'section.gallery.photo .container .FigureBox.Photo:first-child .hidden-shot, section.gallery.photo .container .FigureBox.Photo:first-child ',
    	},
    	HINT_Keydown_Gallery:{
    		css_id:'hint-keydown-gallery-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:9000,
    		align:  { points: [ 'tr', 'bc' ] },
    		trigger: '.gallery-header .keydown',
    	},
    	HINT_Keydown_Preview:{
    		css_id:'hint-keydown-preview-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:9000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: '.FigureBox.PhotoZoom .extras .keydown, .FigureBox.PhotoPreview .extras .keydown',
    		anchor: '.FigureBox .extras .keydown',
    	},
    	HINT_Filmstrip:{
    		css_id:'hint-filmstrip-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:5000,
    		align:  { points: [ 'tc', 'bc' ] },
    		trigger: 'section.preview-body nav.settings',
    		anchor: 'section.preview-body nav.settings ul.filmstrip-nav',
    	},
    	HINT_Create:{
    		css_id:'hint-create-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:5000,
    		align:  { points: [ 'tr', 'bc' ] },
    		trigger: '.head .menu-trigger-create',
    		anchor: '.head .menu-trigger-create span.green',
    	},
    	HINT_DisplayOptions:{
    		css_id:'hint-display-option-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:5000,
    		align:  { points: [ 'tr', 'bc' ] },
    		trigger: '.gallery-header .display-option',
    		anchor: '.gallery-header .display-option',
    	},
    	HINT_Montage:{
    		css_id:'hint-montage-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:5000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: 'nav.section-header li.montage, nav.section-header li.gallery',
    		anchor: 'nav.section-header li.montage',
    	},
    	HINT_Lightbox:{
    		css_id:'hint-lightbox-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:2000,
    		align:  { points: [ 'br', 'tr' ] },
    		trigger: '#lightbox',
    		anchor: '#lightbox .lightbox-tab',
    	},
    	HINT_Badge:{
    		css_id:'hint-badge-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:3000,
    		align:  { points: [ 'tl', 'bc' ] },
    		trigger: 'section.item-header li.thumbnail',
    	},    	
    	HINT_Upload:{
    		css_id:'hint-upload-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay:5000,
    		align:  { points: [ 'tc', 'bc' ] },
    		trigger: '.gallery.photo .container .FigureBox',
    		anchor: '.upload-toolbar li.btn.start',
    	},
    	HINT_PMToolbarEdit:{
    		css_id:'hint-pm-toolbar-edit-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay: 2000,
    		hideDelay: 20000,
    		align:  { points: [ 'tc', 'bc' ] },
    		trigger: '#dialog-alert .pagemaker-stage',
    		anchor: '#menu-pm-toolbar-edit', 
    	},
    	HINT_PMPlay:{
    		css_id:'hint-pm-play-markup', 
    		uri: '/help/markup/tooltips', 
    		showDelay: 0,
    		hideDelay: 500,
    		align:  { points: [ 'bc', 'tc' ] },
    		trigger: '#menu-pm-toolbar-edit .play',
    	},
    	HINT_Preview_StoryByRatings:{
    		css_id:'hint-preview-story-ratings-markup',
    		uri: '/help/markup/tooltips', 
    		// showDelay:3000,
    		hideDelay: 10000, 
    		showArrow: false,
    		align:  { points: [ 'tc', 'tc' ] },
    		trigger: '#dialog-alert .pagemaker-stage',
    	},
    }
    /*
     * static properties
     */        
    Hint.instance = null;			// singleton class
    Hint.doNotShow = {};			// hidden by user, load from Cookie
    Hint.lookupHintByTriggerSH = null;
    Hint.active = {					// set in _getHintMarkupFromTriggerSH()
    	trigger: null,
    	body: null,
    	cfg: null,
    }
    Hint.anyTrigger = false;		// show All Hints on NextTip

    /*
     * Static methods 
     */
    Hint.XHR_WAIT = {};
    Hint.flushQueue = function() {
    	// load queued hints
	    for (var i in SNAPPI.STATE.hints) {
	    	if (SNAPPI.STATE.hints[i] !== 'loaded') {
	    		var loadCfg = SNAPPI.STATE.hints[i];
	    		if (!loadCfg.id) loadCfg = {id: i}; 
	    		if (Hint.load(loadCfg)) {
	    			SNAPPI.STATE.hints[i] = 'loaded';	
	    		}
	    	}
	    }
    }
    Hint.alignToAnchor = function(){
    	try {		// using _override_refreshAlign() instead
    		var cfg = Hint.active.cfg;
    		var anchor = cfg.anchor || cfg.trigger;
    		if(cfg.anchor) {
	    		Hint.instance.align(anchor, cfg.align.points);
	    	}	
    	}catch(e){}
    }
    /*
     * private attributes
     */
    var _defaultCfg = {
    	align:  { points: [ 'bl', 'tr' ] },
    	constrain: true,
    	hideDelay: 5000,
    	cancellableHide: true,
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
			// foundByTrigger = Hint.lookupHintByTrigger[trigger];
			row = {
				id: cfg.id,
				cfg: cfg,
				body: body,
			};
			// if (foundByTrigger && _Y.Lang.isArray(foundByTrigger)) foundByTrigger.push(row);
			// else Hint.lookupHintByTrigger[trigger] = [row];
			// use SH
			var rowSH = Hint.lookupHintByTriggerSH.get(trigger);
			if (rowSH) rowSH.add(row); 
			else {
				rowSH = new SNAPPI.SortedHash();
				rowSH.add(row);
				Hint.lookupHintByTriggerSH.add(trigger, rowSH);
			}
		}
    }
    /*
     * set hint body based on the node/selector which triggered the hint
     * 		called by hint.on('visibleChange') before hint.show();
     * @params hint, Hint object
     * @params node, the node which triggered, i.e. Hint.get('currentNode');
     * @params anyTrigger boolean. expand search for any hint/trigger when true 
     * 		typically true for ShowNextTip
     * @return found;
     */ 
    var _setHintBodyByTriggerNode = function(hint, node, anyTrigger, dryrun){
    	node = node || hint.get('currentNode');
    	if (!node) anyTrigger = true;
    	var trigger, current, 
    		sh = SNAPPI.Hint.lookupHintByTriggerSH;
    	var found, 
    		focus = sh.getFocus(), 
    		i = sh.indexOfValue(focus), 
    		triggerSelectors = sh.getKeys();
 		// find the next valid Hint markup, using .test() and setBodyContent
    	// if no more, then check if there is another Hint.trigger that was suppressed
    	// resorder array to start from focus
    	if (i) {
    		var move = triggerSelectors.slice(0,i);
    		triggerSelectors = triggerSelectors.slice(i).concat(move);
    	}
    		
		for (var j in triggerSelectors) {
			var validSelector = anyTrigger ? _Y.one(triggerSelectors[j]) : false;
    		if ((validSelector || (node && node.test(triggerSelectors[j]))) 
    			&& (found = _getHintMarkupFromTriggerSH(triggerSelectors[j], anyTrigger))
    		){
    			// found == Hint.active
    			if (!dryrun) hint.set('bodyContent', Hint.active.body);
// console.log('hint.body set to '+found.cfg.id+', anyTrigger='+(anyTrigger ? 1 : 0));    
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
    var _getHintMarkupFromTriggerSH = function(trigger, anyTrigger, _originalTrigger){
    	trigger = trigger.trim();
    	sh = SNAPPI.Hint.lookupHintByTriggerSH;
    	var hint, found, triggerVisible, 
			anyTrigger = anyTrigger || Hint.anyTrigger,
    		triggerSH = sh.get(trigger),
    		activeId = Hint.active.cfg && Hint.active.cfg.id;
    		triggerVisible = _Y.one(trigger);
		if (!triggerVisible || !triggerVisible.get('clientHeight')) {
			if (anyTrigger) {
				// this trigger is not a validSelector on this page. 
				// get next nextTriggerSH by recursion
    			var nextTrigger, 
    				nextTriggerSH = sh.next();
    			if (!nextTriggerSH) nextTriggerSH = sh.setFocus(0);
    			_originalTrigger = _originalTrigger || trigger;	// already .trim()
    			nextTrigger = sh.getKeyForValue(nextTriggerSH);
    			if (nextTrigger == _originalTrigger) return false;	// same trigger, no new hint
    			else {
    				return _getHintMarkupFromTriggerSH( nextTrigger, true, _originalTrigger);
    			}
    		} else return false;	 
		}    		
    	if (triggerSH) {
    		hint = triggerSH.getFocus();
    		while (hint 
    			&& (hint.id == activeId || _checkDoNotShow(hint)) ) 
    		{	// get next valid hint in triggerSH
    			hint = triggerSH.next();
    		} 
    		if (hint && hint.id == activeId) hint = false;		// same as active
    		if (hint) found = hint;
    		else if (!hint && !anyTrigger) {
    			// 	not found, try from the beginning
    			hint = triggerSH.setFocus(0);		
    			while (hint && _checkDoNotShow(hint)) {
    				hint = triggerSH.next();
    			}
    			if (hint && hint.id == activeId) return false;	// same hint, no new hints
    			if (hint) found = hint;
    		} else if (!hint && anyTrigger) {
    			hint = triggerSH.setFocus(0);	// start old triggerSH from beginning, 
    			// get next nextTriggerSH
    			var nextTrigger, 				// move to new triggerSH
    				nextTriggerSH = sh.next();
    			if (!nextTriggerSH) nextTriggerSH = sh.setFocus(0);
    			_originalTrigger = _originalTrigger || trigger;	// already .trim()
    			nextTrigger = sh.getKeyForValue(nextTriggerSH);
    			if (nextTrigger == _originalTrigger) return false;	// same trigger, no new hint
    			else {
    				return _getHintMarkupFromTriggerSH( nextTrigger, true, _originalTrigger);
    			}
    		}
    	}
    	if (found) {
    		Hint.active = {
	    		trigger: trigger,
	    		body: found.body,
	    		cfg: found.cfg,
	    	}	
	    	return found;
    	} 
    	return false;
    }
    var _checkDoNotShow = function (found){
    	return found && found.cfg && (Hint.doNotShow[found.cfg.id] ? true : false);
    }
    var _addDoNotShow = function(id, saveToCookie){
    	Hint.doNotShow[id] = 1;
    	var exp = new Date(+new Date + 12096e5);
    	if (saveToCookie) {
    		Hint.cookie[id] = 1;
    		_Y.Cookie.set('SNAPPI_doNotShow', _Y.JSON.stringify(Hint.cookie), {
				path: '/help/markup/tooltips',			
				expires: exp,
			});
    	}
    }
    
    var _handle_VisibleChange = function(e){
		if (e.newVal == true && e.prevVal==false) {
			// check for inactivity time against global
			var inactivity_ms = new Date().getTime() - SNAPPI.last_action_ms;
// console.error("inactivity_ms="+inactivity_ms+", showDelay="+this.get('showDelay'));			
			if (inactivity_ms < this.get('showDelay')) {
				hint.clearIntervals();
				hint.set('showDelay', this.get('showDelay'));
// console.error('_handle_VisibleChange: clearIntervals, reset='+this.get('showDelay'));				
				return;
			}
			
			// this == hint
			// var trigger = e.currentTarget._cfg.trigger;
			var triggerNode = this.get('currentNode');
			var found = _setHintBodyByTriggerNode(this, triggerNode);
			if (!found) e.halt();		// prevent hint.show();
			if (!found && _checkDoNotShow(Hint.active)) {
				var moreTips = _setHintBodyByTriggerNode(this, triggerNode, true, 'dryrun');
				if (!moreTips) {
					this.set('trigger', '#blackhole');
	console.warn("visibleChange: Hints disabled, trigger set to #blackhole");
				}
			} else if (found) { // else just show active
				var footer = this.getStdModNode('footer');
				footer.one('span.show-next').removeClass('disabled');
	    		footer.one('.do-not-show').set('id', Hint.active.cfg.id).set('checked', false);
	    	}
		} else if (e.newVal == false && e.prevVal== true) {
			// var body = e.currentTarget.get('bodyContent') !== Hint.active.body;
			Hint.sleepHints(1);
		}
	}
	var _override_refreshAlign = function(){
		this.constructor.prototype.refreshAlign.call(this);	// parentClass method
		try{	// align to anchor without changing this.get('currentNode')
			var anchor = _Y.one(Hint.active.cfg.anchor || Hint.active.cfg.trigger);
			if (anchor) this.align(anchor, Hint.active.cfg.align.points);
		}catch(e){}
	};
	var _handle_clickOutside = function(e, hint){
		if (hint.get('visible')==false) return false;	// skip if hidden
		if (!hint.get('boundingBox').contains(e.target)) {
			hint.hide();	
			hint.clearIntervals();
			e.halt();
			return true;
		};
		return false;
	}
	var _handle_MenuDialogVisible = function(o, visible) {
		// TODO: what is both menu & dialog visible, then one is hidden?
		Hint.instance.set('disabled', visible ? true : false);
		if (visible) Hint.instance.hide();
		var check;
	}
	var _handle_ResetDelayTimer = function(e, hint){
		if (hint.get('visible')) return false; 			// skip if visible
		if (_sleep_status.later) {
			_sleep_status.later.cancel();
// console.warn('resetting hint delay SLEEP timer on click');			
			_sleep_status.later = _Y.later( _sleep_status['time'], hint, 
				function(e){
					hint.set('trigger', hint.triggers.join(',') );
					_sleep_status['time'] = 0;
					_sleep_status.later = null;
					console.log('awake');				
				}	
			);
		} else {
			// TODO: is this ALREADY handled in _handle_VisibleChange(), SNAPPI.last_action_ms
// console.warn('_handle_ResetDelayTimer: clearIntervals, delay=' + hint.get('showDelay'));				
			hint.clearIntervals();
			hint.set('showDelay', hint.get('showDelay'));
		}
		return true;
	}
	/*
	 * sleep timer for hints on hide() or close
	 */
	var _sleep_status = {count: 0, time: 0, later: null}
	Hint.sleepHints = function(mins){
		// if (mins!==false && !mins) mins = 1;		// allow mins==0
		var secs, h = Hint.instance;
		if (mins === false) {	// cancel sleep
			h.set('trigger', h.triggers.join(',') );
			_sleep_status.count = _sleep_status['time'] = 0;
			if (_sleep_status.later) _sleep_status.later.cancel();
			_sleep_status.later = false;
			h.set('hideDelay', 10000);
			h.set('showDelay', 0);
			SNAPPI.last_action_ms = 0;		// disable last_action check
// console.log('force awake, triggers='+h.triggers.join(','));				
			return;
		}
		if (_sleep_status.later===false) {
// console.log('showing all tips. cancel sleep');			
			return;	// cancel for this page.
		}
		
		secs = mins*60*1000;
		if ( (secs !==0) && secs < _sleep_status['time']) return;	// already sleeping
		
		if (secs !==0) _sleep_status.count += 1;
		else _sleep_status.count = 0;
		_sleep_status['time'] = secs  * _sleep_status.count  || 10;
		if (_sleep_status.later) _sleep_status.later.cancel();
		_sleep_status.later = _Y.later( _sleep_status['time'], h, 
			function(){
				h.set('trigger', h.triggers.join(',') );
				_sleep_status['time'] = 0;
				_sleep_status.later = null;
// console.log('awake');				
			}	
		);
		h.set('trigger', '#blackhole');
// console.log("sleep for sec="+_sleep_status['time'])	;	
	};
	var _handle_Close = function(e, contentBox){
		try {		// context: this = hint or ToolTip
			var hide = e.container.one('input.do-not-show');
			if (hide.get('checked')) _addDoNotShow(Hint.active.cfg.id, true);
			else _addDoNotShow(Hint.active.cfg.id, false); 
		} catch(e) {}
		this.hide();
		Hint.sleepHints(3);
		var check;
	}
	var _handle_ShowNextTip = function(e){ // context: this = hint or ToolTip
		e.halt();
		e.stopImmediatePropagation();
		if (e.currentTarget.hasClass('disabled')) return;
		var footer = this.getStdModNode('footer');
		if (footer.one('.do-not-show').get('checked')){
			_addDoNotShow(Hint.active.cfg.id, true);
			// footer.one('.do-not-show').set('checked', false);
		} 
		// else _addDoNotShow(Hint.active.cfg.id, false);
		var triggerNode = this.get('currentNode');
		var found = _setHintBodyByTriggerNode(this, triggerNode, true);
		if (!found) e.currentTarget.addClass('disabled');
		var check;
	}
	var _handle_ShowAllTips = function(e){
		// context: hint
		e.halt();
		if (Hint.anyTrigger) {
			Hint.anyTrigger = false;
			this.hide();
			_sleep_status.later = null;
			e.currentTarget.setContent('Show All Tips');
			SNAPPI.UIHelper.nav.showHelp();
		} else {
			Hint.anyTrigger = true;
			Hint.sleepHints(false);
			this.show();
			Hint.doNotShow = {}
			SNAPPI.Hint.flushQueue();		// if Hint already available
			// reset button
			e.currentTarget.setContent('Hide Tips');
			SNAPPI.UIHelper.nav.showHelp();
		}
	}
    /**
     * NOTE: this method blocks Hint.flushQueue() on XHR
     * 		- assumes that all cfg.uri contains markup for all hints 
	 * fetch html markup using Plugin.IO, 
	 * 		copied from SNAPPI.Menu
	 * @param cfg {uri:, selector:, container:, plug _Y.Plugin.io cfg attrs}
	 * @param callback, callback method to init Menu
	 * @return menu or false on XHR request
	 */
	Hint.getMarkup = function(cfg, callback){
		var container = cfg.container || _Y.one('#markup');	
		var selector = cfg.selector || '#'+cfg.css_id;	// selector for markup
		var markup = container.one(selector);
		if (markup) return markup;
		
		var markupNode = _Y.Node.create("<div class='hide'></div>");
		container.append(markupNode);
		var ioCfg = {
				uri: cfg.uri,
				autoLoad: true,
				// parseContent: true,			// pass Cookies as PAGE.Cookie
				showLoading:false, 
				arguments: {
					cfg: cfg,
					parent: markupNode,
					callback: callback,
				},
				on: {
					success: function(e, id, o, args) {
						SNAPPI.setPageLoading(false);
						var node = SNAPPI.IO.parseContent(o.responseText);
						args.parent.setContent(node);
						SNAPPI.util.setForMacintosh(args.parent);
						// args.callback.call(this, args.cfg);
						Hint.XHR_WAIT[args.cfg.uri] = false;
						
						try {
							Hint.doNotShow = _Y.merge(Hint.doNotShow, PAGE.Cookie.doNotShow);	
							Hint.cookie = _Y.merge(Hint.cookie, PAGE.Cookie.doNotShow);
						} catch(e){}
						
						
						Hint.flushQueue();	// just flushQueue() to restart
						return false;	
					}
				},
		};		
		SNAPPI.setPageLoading(true);
		markupNode.plug(_Y.Plugin.IO, ioCfg);
		Hint.XHR_WAIT[cfg.uri] = true;	
		return false;	
	};
	
    /**
     * load the hint
     * @params cfg.id string, appears in Hint.CFG, example: HINT_MultiSelect
     * @params cfg.css_id string, CSS id of markup
     * @params cfg.delay int, (optional) milliseconds until hint is displayed
     * @params cfg.trigger, Node or selector
     * @return false if waiting for XHR
     */
    Hint.load = function(cfg){
    	if (Hint.doNotShow[cfg.id]) return;	
    	if (_Y.Lang.isObject(Hint.CFG[cfg.id])) {
    		cfg = _Y.merge(_defaultCfg, Hint.CFG[cfg.id], cfg);
    	} else cfg = _Y.merge(_defaultCfg, cfg);
    	
    	// get markup, from DOM or by XHR
    	if (cfg.uri && Hint.XHR_WAIT[cfg.uri]) return false;	// WAIT until XHR complete
    	var body = Hint.getMarkup(cfg, Hint.load);
    	if (body === false) return false;		// wait for callback
    	
    	// use only 1 hint, modify trigger, bodyContent
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
    		hint.set('footerContent', _Y.Lang.sub(_bodyMarkup.doNotShow, cfg));
    		hint.listen = {};
    		hint.listen['visibleChange'] = hint.on('visibleChange', _handle_VisibleChange, hint);
    		hint.listen['any-click'] = _Y.on('click', function(e, hint){
    			_handle_ResetDelayTimer(e, hint);
    			_handle_clickOutside(e, hint)
// console.log("hint any-click");    		    				
    			
    			// if (_handle_clickOutside(e, hint) == false) {
    				// _handle_ResetDelayTimer(e, hint);
    			// }
    		}, null, hint, hint);
    		hint.listen['any-contextmenu'] = _Y.on('contextmenu', function(e, hint){
// console.log("hint contextmenu");    			
    			_handle_ResetDelayTimer(e, hint);
    			_handle_clickOutside(e, hint)
    		}, null, hint, hint);
    		hint.listen['show-all-tips'] = _Y.on('click', _handle_ShowAllTips , 'section.help li.btn.show-all-tips', hint);
    		hint.listen['menu-visible'] = _Y.on('snappi:menu-visible', _handle_MenuDialogVisible, null, hint);
    		hint.listen['dialog-visible'] = _Y.on('snappi:dialog-visible', _handle_MenuDialogVisible, null, hint);
    		/*
    		 * override refreshAlign(), called by show() method
    		 */
    		hint.refreshAlign = _override_refreshAlign;
    		contentBox = hint.get('contentBox');
    		contentBox.listen = {};
			contentBox.listen['close'] = contentBox.delegate('click', _handle_Close, 'span.close', hint, contentBox);
    		contentBox.listen['show-next'] = contentBox.delegate('click', _handle_ShowNextTip, 'span.show-next', hint);    		
    	}else{		// reuse hint
    		/*
    		 * TODO: BUG: showDelay is stuck on the first loaded Hint, the different
    		 * triggers show determine the showDelay
    		 */
    		try {
    			var update, trigger, triggers = cfg.trigger.split(',');
    			for (var i in triggers) {
    				trigger = triggers[i].trim();
    				if (hint.triggers.indexOf(trigger)==-1) {
		    			hint.triggers.push(trigger);
		    			update = true;
		    		}
    			}
    			if (update || (hint.get('trigger') == '#blackhole')) {
    				hint.set('trigger', hint.triggers.join(','));
    			}
    		} catch(e){
    			console.error("Error: Hint.load(): expecting trigger to be a selector string");
    		}
    		// body will be updated in hint.on('visibleChange') after _registerHint()
    		// register Hint
    		_registerHint(hint, cfg, body);
    	}
    	return true;
	};
})();