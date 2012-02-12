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
		XHR_PAGE_INIT_DELAY: 500,
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
				var wait = cfg.delay || this.XHR_PAGE_INIT_DELAY;
				var fragments = _Y.all('.'+TRIGGER);
				if (fragments) {
					fragments.each(function(n,i,l) {
						if (n.hasClass('xhr-loading')) return;	// prevent duplicate loading
						n.addClass('xhr-loading');
						var nodelay = n.getAttribute('nodelay');
						if (nodelay) {
							this.requestFragment(n);							
						} else {
							var delayed = new _Y.DelayedTask( function() {
								this.requestFragment(n);
							}, this);
							// executes after XXXms the callback
							delayed.delay(wait);	
							wait += 500;  // +500ms delay for each subsequent fetch
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
			var nodelay = n.getAttribute('nodelay');
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
					parseContent: true,
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
			
			/*
			 *  before _Y.Plugin.IO.setContent()
			 */
			// var detach = _Y.before(function(content, target, fragment){
				// console.warn("before _Y.Plugin.IO.setContent()");
				// detach.detach();
				// this.before_SetContent(content, target, fragment);
				// // new _Y.DelayedTask(SNAPPI.xhrFetch.init).delay(100);
			// }, target.io, 'setContent', this, target, n); 
			       				
			target.io.set('uri', uri);
			target.io.set('arguments', args);
			target.io.start();
			return;			
		},
		before_SetContent: function(content, target, fragment){
		}
	};
	
	/*
	 * replaced by aui, pagingator-aui and A.IO.plugin
	 */
	XhrFetch.unused_methods = {
		/**
		 * @deprecated, use paginator-aui instead
		 * search for paginate divs, add delegated listeners - can be called
		 * repeatedly with no side-effects
		 */
		initPaging : function() {
			var paging = _Y.all('div.paging-content');	// deprecate. using SNAPPI.Paginator
			if (paging) {
				paging.each(function(n) {
					// add event delegate listeners
						if (!n.listen) n.listen={}; 
						if (!n.listen.paging) {
							n.listen.paging = n.delegate('click',
									this.requestPagingContent,
									'.paging-control a', this);
						}
					}, this);
			}
		},
		/**
		 * 
		 * @deprecated. use paginator-aui instead
		 * 
		 * render new page into 'div.paging-content' - searches for
		 * 'div.paging-content' to attach delegated click listener to
		 * PaginateHelper <A> elements using CSS selector = '.paging-control a' -
		 * xhrSrc == A.href from e.target.get('href') - replaces innerHTML in
		 * target uses the following custom dom attr - xhrTarget: target for
		 * ajax response, default to 'div.paging-content', referenced by Id
		 * 
		 * @param {Object} e - click event object
		 */
		requestPagingContent : function(e) {
			e.halt(); // stop event propagation
			//e.container == delegate event container
			var targetId = e.container.getAttribute('xhrTarget') || e.container.get('id');
			var target = _Y.one('#'+targetId);
			var uri = e.target.get('href');

			try {
				SNAPPI.STATE.displayPage.page = parseInt(uri.match(
						/page:(\d*)/i).pop());
			} catch (e) {
			}
			if (!target.io) {
				target.plug(_Y.Plugin.IO, {
					uri: uri,
					method: 'GET',
					parseContent: true,
					autoLoad: false
				});
			}
			target.io.set('uri', uri);
			target.io.start();
			return;
		},

		
		/**
		 * @deprecated, use  A.Plugin.IO.setContent instead
		 * 
		 * - replaces innerHTML of Dom element with responseText
		 * 
		 * @param {Object} id _Y.io transaction Id
		 * @param {Object} o response object
		 * @param {Object} args args.sectionId == id of DOM container
		 */
		_updateDOM : function(id, o, args) {
			console.warning("DEPRECATE? SNAPPI.xhrFetch._updateDOM");
			var data = o.responseText; // Response data.
			var target = args.target || args[0]; // DOM element id to put
													// data
			var node = target.setContent(data);
	
			SNAPPI.xhrFetch.xhrInit(node); // execute js in ajax markup
			_Y.fire('snappi:ajaxLoad'); // execute js in script files
		},
		/**
		 * DEPRECATED (?) XHR request should call 
		 * XHR page xhr-gets can add init code by markup like this: 
		 * // <script class='xhrInit' type='text/javascript'> 
		 */
		xhrInit : function(xhrNode) {
			console.warning("DEPRECATE? SNAPPI.xhrFetch.xhrInit: PAGE.init.length="+PAGE.init.length);
			// execute deferred javascript init
			while (PAGE.init.length) {
				var init = PAGE.init.shift();
				init();
			}
		},
		
		
		/**
		 * Not finished
		 * getPageFromCache - check if we have page data already cached in a
		 * sortedHash
		 * 
		 * @param {Object}
		 *            e
		 */
	};
	
	
})();
