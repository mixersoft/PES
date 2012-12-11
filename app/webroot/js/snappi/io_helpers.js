/**
 *
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero GNU General Public License for more details.
 *
 * You should have received a copy of the Affero GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 *
 */
(function(){
	
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Flash = function(Y){
		if (_Y === null) _Y = Y;
		
		SNAPPI.flash = new Flash();
		SNAPPI.timeout = {};
	}
	var TIMEOUT = 5000;
	/**************************************************************************
	 * Cakephp style Session Flash from JS
	 */
	
	var Flash = function(content) {
		var content = arguments;
	};
	Flash.prototype = {
		flash : function(content) {
			if (!content) return;
			var parent = _Y.one('#content > div.messages');
			if (!parent) {
				_Y.one('#content').prepend("<div id='message' class='messages prefix_2 grid_12 suffix_2'></div>");
				parent = _Y.one('#content > div.messages');
			}
			if (parent.one('div:not(.hide).message')) {
				parent.one('div:not(.hide).message').append(content);
			} else {
				parent.setContent("<div class='message'>"+content+"</div>");
			}
			SNAPPI.timeout.flashMsgs = SNAPPI.timeout.flashMsgs || [];
			var hide = _Y.later(TIMEOUT, {}, function() {
				parent.one('.message').addClass('hide');
				var i = SNAPPI.timeout.flashMsgs.indexOf(hide);
				SNAPPI.timeout.flashMsgs.splice(i, 1);
			});
			SNAPPI.timeout.flashMsgs.push(hide);
		},
		flashJsonResponse: function(o){
			try {
				var msg = o.responseJson.message || _Y.JSON.Stringify(o.responseJson);
			} catch (e) {
				msg = o.responseText;
			}
			SNAPPI.flash.flash(msg);			
		},
		setFlashOnReload : function(msg) {
			postMessageData = {
				'Message.flash.message' : msg,
				'Message.flash.element' : 'default',
				'Message.flash.params' : new Array()
			};
			callback = {
				complete : function(id, o, args) {
					var check;
				},
				failure : function(id, o, args) {
					var check;
				}
			};
			SNAPPI.io.writeSession(postMessageData, callback, '');
		}		
	};

})();


/*******************************************************************************
 * XHR (XhrFetch) module SNAPPI.ajax = new XhrFetch();
 */
(function() {
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.XhrFetch = function(Y){
		if (_Y === null) _Y = Y;
		/*
		 * make global
		 */
		SNAPPI.xhrFetch = new XhrFetch();
		SNAPPI.ajax = SNAPPI.xhrFetch;
	}
	/*
	 * XhrFetch Class (singleton class) 
	 * Use '.xhr-get' markup to request CakePhp element by XHR/XhrFetch 
	 * uses the following custom dom attr to encode src/target for request 
	 * - xhrSrc: cakePhp request 
	 * - xhrTarget: target for ajax response, default to current DOM element by Id 
	 * xhr-get request is automatically made for all '.xhr-get' markup
	 * - delay: ms of delay to add, default = 0
	 */
	var XhrFetch = function(cfg) {
		if (XhrFetch.instance) return XhrFetch.instance;
		XhrFetch.instance = this;
		if (cfg && cfg.TRIGGER) this.CSS_CLASS_TRIGGER = TRIGGER;
		return this; // chainable constructor		
	};
	XhrFetch.instance = null;
	
	XhrFetch.prototype = {
		CSS_CLASS_TRIGGER: 'xhr-get',
		XHR_PAGE_DELAY_BUFFER: 200,
		/**
		 * singleton init - attaches delegated click handlers for ajax paging -
		 * launches request for any ajax xhr-gets to fetch after initial page
		 * load
		 */
		init : function(cfg) {
			cfg = cfg || {};
			if (cfg.TRIGGER) this.CSS_CLASS_TRIGGER = TRIGGER;
			this.fetchXhr(null);
		},
		/*
		 * search for page xhr-gets to fetch via ajax request
		 */
		fetchXhr : function(n, cfg) {
			var TRIGGER = cfg && cfg.TRIGGER ? cfg.TRIGGER : this.CSS_CLASS_TRIGGER;  
			if (n && n.hasClass(TRIGGER)) {
				// direct request, just fetch without delay
				this.requestFragment(n, cfg);
			} else {
				// searches page for xhr-gets, add delay as necessary
				var fragments = _Y.all('.'+TRIGGER);
				var buffer = {};
				if (fragments) {
					fragments.each(function(n,i,l) {
						if (n.hasClass('xhr-loading')) return;	// prevent duplicate loading
						n.addClass('xhr-loading');
						var delay = n.getAttribute('delay');
						buffer[delay] = !buffer[delay] ? 1 : buffer[delay]+1; 
						if (!delay) {
							this.requestFragment(n);							
						} else {
							var delayed = new _Y.DelayedTask( function() {
								this.requestFragment(n);
								delete SNAPPI.timeout[n.get('id')];
							}, this);
							// executes after XXXms the callback
							delay = parseInt(delay)+(buffer[delay]*this.XHR_PAGE_DELAY_BUFFER);
							delayed.delay(delay);	
							SNAPPI.timeout[n.get('id')] = delayed;
							if (delay > 1000) {
								n.once('mouseenter', function(e, n){
									if (SNAPPI.timeout[n.get('id')]){
										SNAPPI.timeout[n.get('id')].cancel(); 
										delete SNAPPI.timeout[n.get('id')];
										this.requestFragment(n);
									}
								}, this, n);
							}
						}
					}, this);
				}
			}
		},

		/**
		 * render ajax request into replaceDiv#id on page load uses the
		 * following custom dom attr to encode src/target for request - xhrSrc:
		 * cakePhp request - xhrTarget: target for ajax response, default to
		 * current DOM element by Id xhr-get request is automatically made for
		 * all 'div.xhr-get' markup
		 * 
		 * @param {Object} n - YUI3 node for 'div.xhr-get'
		 */
		requestFragment : function(n, cfg) {
			
			var target = n.getAttribute('xhrTarget');
			target = target ? _Y.one('#'+target) : n;
			var uri = n.getAttribute('xhrSrc');
			
//			var _updateDOM = this._updateDOM;
			// NOTE: key events 
			// 		target.io.afterHostMethod('insert'), use insert instead of setContent for ParseContent
			//		target.io.after('IOPlugin:success')
			//		target.io.after('IOPlugin:activeChange', 
			// 	ParseContent._dispatch() runs from asyncQueue. happens async AFTER IOPlugin:success
			
			var args = {
				target: target, 
				fragment: n,
				cfg: cfg
			};
			if (!target.io) {
				target.plug(_Y.Plugin.IO, {
					uri: uri,
					method: 'GET',
					parseContent: true,	// TODO: doesn't work, use IO.parseContent()
					autoLoad: false,
					arguments: args,
					on: {
						complete: function(e, id, o , args) {
							args.fragment.removeClass('xhr-loading');
							if (args.cfg && args.cfg.complete) {
								args.cfg.complete(e, id, o , args);
							}
						},
						// success: function(e, id, o , args) {
							// console.warn("success");
						// },						
						// failure: function(e, id, o , args) {
							// console.warn("failure");
						// },						
					}
				});
			}
			args.fragment.addClass('xhr-loading');
			target.io.set('uri', uri);
			target.io.set('arguments', args);
			target.io.start();
			return;			
		},
	};
})();
