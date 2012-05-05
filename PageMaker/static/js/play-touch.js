/* Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
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
 * along with this program.  If not, see <http:// www.gnu.org/licenses/>.
 *
 *
 * @author Michael Lin, info@snaphappi.com
 *
 *
 */
(function() {
   	try {
		// load from PageMakerPlugin
		var _Y = null;
		var Plugin = null;
		var PM = SNAPPI.namespace('SNAPPI.PM');
		PM.namespace('PM.onYready');
		// Yready init
		PM.onYready.Play = function(Y){
			if (_Y === null) _Y = PM.Y;
			PM.bootstrapY = false;
			Plugin = PM.PageMakerPlugin.instance;
		}  		
   	} catch (e) {
		// same as base_aui.js
		namespace = function(){
		    var a = arguments, o = null, i, j, d;
		    for (i = 0; i < a.length; i = i + 1) {
		        d = a[i].split(".");
		        o = window;
		        for (j = 0; j < d.length; j = j + 1) {
		            o[d[j]] = o[d[j]] || {};
		            o = o[d[j]];
		        }
		    }
		    return o;
		};
		/*
		 * init *GLOBAL* SNAPPI.PM as root for namespace
		 */    
		var PM = namespace('SNAPPI.PM');
		PM.name = 'Snaphappi PageMaker';
		PM.namespace = namespace;
		PM.onYready = {};
		PM.cfg = {};
		if (!SNAPPI.id) {
			SNAPPI.id = PM.name;
		}
		/*
		 * yui bootstrap
		 */
		PM.bootstrapY = true;		
   	}	


	/*
	 * Configuration object
	 */
	var CONFIG = {
		container : 'body',
		content : '#content',
		NATIVE_PAGE_GALLERY_H : 800 - 82, // this height
		// pageGallery margin = 20px
		// wrap = 16px
		FOOTER_H : 36, // before header, footer
		MARGIN_W : 22,
		DEFER_IMG_LOAD: true,		// delay IMG load by moving IMG.src => IMG.qsrc
		layout : {
			centerBoxW : 480,
			centerBoxH : 480
		},
		animation : {
			overlayDuration : 0.3,
			boxW : 25,
			boxH : 40,
			boxAnimH : 37
		},
		flick: {
	        minDistance:10,
	        minVelocity:0.3,
	    },
		charCode : {
			nextPatt : /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
			// keypad right
			prevPatt : /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
			// keypad left
			closePatt : /(^27$)/
		// escape
		}
	};

	/*
	 * static properties
	 */
	var _containerRect, _pageIndex = 0, _curPhotoIndex, _totalPages = 0;
	var _contentW, _contentH;	// for touch scrolling;

	/*
	 * helper functions
	 */
	var _getFromQs = function(name){
        /*
         * get a query param value by name from the current URL
         */
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regexS = "[\\?&]" + name + "=([^&#]*)";
        var regex = new RegExp(regexS);
        var results = regex.exec(window.location.href);
        if (results == null) 
            return "";
        else 
            return results[1];
	};
		
	var _px2i = function(sz) {
		var nsz = sz.replace(/px/gi, "");
		nsz = parseFloat(nsz);
		return PM.Y.Lang.isNumber(nsz) ? nsz : null;
	};

	var _sortByIndexASC = function(a, b) {
		var retval;
		if (a.index[0] == b.index[0]) {
			if (a.index[1] == b.index[1]) {
				retval = a.index[2] - b.index[2];
			} else
				retval = a.index[1] - b.index[1];
		} else
			retval = a.index[0] - b.index[0];
		return retval; // sort ASC order
	};
	var _stripCrop = function(src) {
		match = src.match(/\.jpg.*\.jpg$/i);
		if (match)
			src = src.substr(0, match.index + 4);
		return src;
	};
	var _getNaturalDim = function(lightBoxPhoto, animation) {
		if (lightBoxPhoto.get('naturalWidth')) {
			W = parseInt(lightBoxPhoto.get('naturalWidth'))
					+ animation.boxW;
			H = parseInt(lightBoxPhoto.get('naturalHeight'))
					+ animation.boxH;
		} else {
			// for ie7, ie8, this is unscaled W,H
			W = parseInt(lightBoxPhoto.dom().width)
					+ animation.boxW;
			H = parseInt(lightBoxPhoto.dom().height)
					+ animation.boxH;
		}
		return {W:W, H:H};
	};
	var _isSrcCached = function(src) {
		var io = new Image();
		io.src = src;
		var isCached = io.naturalWidth || io.complete;
		try { // try to cleanup
			delete io;
		} catch (e) {
			io = null;
		}
		return isCached;
	};
	/*
	 * animate lightbox photo transition
	 */
	var _animateOnDelta = function(img, animate){
		var animation = this.cfg.animation;
		var top, delta, 
			dim = _getNaturalDim(img, animation),
			centerbox = this.container.one("#centerbox"),
			centerboxH = centerbox.get('clientHeight');
		delta = Math.abs(centerbox.get('clientWidth') - dim.W)
				+ Math.abs(centerboxH - dim.H);
		top = Math.max(0,(centerbox.ancestor('#glass').get('clientHeight') - dim.H)/2);
		if (animate || delta >= 50) {
			img.setStyle('opacity', 0.5);
			this.animateBox.call(this, top, dim.W, dim.H, 1000);
			_Y.later(2000, img, function(){
				img.setStyle('opacity', 1);		// timeout
			})	
		} else {
			centerbox.setStyles( {
				width : dim.W,
				height : dim.H,
				top : top,
			});
			this.container.one("#closeBox").removeClass('hidden');
			img.setStyle('opacity', 1);
		}				
	}	
	/**
	 * the W,H boundaries of the container > .pageGallery 
	 * - used for scaling the pageGallery
	 * - works for both touch and normal layouts
	 */
	var _getContainerRect = function(container, cfg, force){
		if (force || !_containerRect) { 
			cfg = cfg || {};
			_containerRect = {};
			_containerRect.H = 0 - (cfg.FOOTER_H || 0);	// offsets
			_containerRect.W = 0 - (cfg.MARGIN_W || 0);
			if (container.get('tagName') == 'BODY') {
				_containerRect.H += container.get('winHeight');
				_containerRect.W += container.get('winWidth');
			} else {
				container = container.ancestor('.aui-dialog-bd') || container;
				_containerRect.H += container.get('clientHeight');
				_containerRect.W += container.get('clientWidth');
			}
		}
		return _Y.merge(_containerRect); // return copy
	}

	var Player = function(cfg) {
		cfg = cfg || {};
		this.container = null; 		// outer node, default 'BODY'
		this.content = null;		// child of this.container, parent of .pageGallery
		this.scrollView = null;		// scrollView class for touch scrolling
		_Y = cfg.Y || PM.Y;		// check: external_Y from Plugin instance
		cfg = _Y.merge(CONFIG, cfg);
		try {	// set container, content nodes
			if (cfg.container instanceof _Y.Node) {
				this.container = cfg.container;
			} else {
				this.container = _Y.one(cfg.container);
			}
			delete cfg.container;

			if (cfg.content instanceof _Y.Node && cfg.content.one('> .pageGallery')) {
				this.content = cfg.content;	
			} else {
				this.content = this.container.one(cfg.content); 
			}
			delete cfg.content;
		} catch (e){}
		this.cfg = cfg;
		
		// parent of div.pageGallery
		this.isPreview = this.cfg.isPreview
				|| PM.bootstrapY == false;

		// content
		this.listen = {}; // detach handlers for active listener

	};
	PM.Player = Player;
		
	/*
	 * end constructor for Player
	 */
	Player.prototype = {
		indexedPhotos: [],
		setStage : function (container, content) {
			this.container = container;
			if (content) {
				this.content = content;
			} else {
				var child = this.container.one('.pageGallery');
				if (child) this.content = child.get('parentNode');
			}
		},
		init : function(e) {
			// set cfg.FOOTER_H offset
			try {
				this.cfg.FOOTER_H += this.container.one('#header').get('clientHeight');
				this.cfg.FOOTER_H += this.container.one('#footer').get('clientHeight');
			}catch(e){}
			var containerRect = _getContainerRect(this.container, this.cfg);
			this.indexedPhotos = this.indexPhotos();
			// this.container.addClass('hidden');

			var offset, origRect, pageNo,
				pages = this.content.all('div.pageGallery');
			
			pageNo = _getFromQs('page') || 1;
			_totalPages = pages.size();
			if (pageNo == 'last') {
				pageNo = _totalPages
				_pageIndex = pageNo - 1; // zero based index
			} else _pageIndex = pageNo-1;
			this.content._isResizing = true;		// disable winResize
this.container.addClass('hide');
			pages.each(function(page, i) {
				origRect = {
					X : _px2i(page.getStyle('left')),
					Y : _px2i(page.getStyle('top')),
					W : _px2i(page.getStyle('width')),
					H : _px2i(page.getStyle('height'))
				};
				if (this.isPreview) {
					offset={X:0,Y:0};	// use position:relative for this.content
				}
				page.origRect = origRect;

				// save original RECT for each photo, along with
				// PageH
				page.all('img').each(function(photo) {
					origRect = {
						X : _px2i(photo.getStyle('left')),
						Y : _px2i(photo.getStyle('top')),
						W : _px2i(photo.getStyle('width')),
						H : _px2i(photo.getStyle('height'))
					};
					photo.origRect = origRect;
					// if (CONFIG.DEFER_IMG_LOAD && i+1 != pageNo) {
						// var src = photo.getAttribute('src');
						// if (src) {
							// photo.setAttribute('qsrc', src);
							// photo.setAttribute('src', '');
						// }
					// }
				}, this);
				// scale photos on each page to target height or width
				containerRect.node = page;
// parent.window.SNAPPI.Y.one('.properties').append("<div style='color:red;position:relative;z-index:999;'>init() page="+i+", pageRect="+ page.origRect.W +':'+ page.origRect.H+"</div>");
				this.scale( containerRect );
				this.pageWrap(page);
			}, this);
this.container.removeClass('hide');	
this.content._isResizing = false;		// enable winResize

			// NOTE: pages must be visible for scrollWrap to get offsets			
			var scrollWrap = this.scrollWrap(this.content, _totalPages);
			scrollWrap.all('.page-wrap').setStyles({width: containerRect.W+'px'});
			this.addScrollView_Page({
				srcNode: scrollWrap,
				width: containerRect.W,
			});
			// this.container.removeClass('hidden');
			if (this.isPreview) {
				this.showPage(pageNo - 1);
				this.startListeners();
			} else {
				this.container.one('#pagenum').set('innerHTML',
						pageNo + "/" + _totalPages);
				this.showPage(pageNo - 1);
				this.startListeners();

				// show the right stuff
				this.container.one('#paging').removeClass('hidden');
				this.content.removeClass('hidden');
				this.container.one('#glass').addClass('hide');
				try {
					this.container.one('#glass > div#bottom').setStyle('opacity', 0.7);
					this.container.one('#glass > .loading').remove();					
				} catch (e) {}
				
				// get sharethis script after done loading first page
				var cancel = _Y.later(2000, this, function(){
					var loading = pages.item(_pageIndex);
					if (this.isPageLoaded(loading)) {
						try {
							cancel.cancel();
							this.load_ShareThisScripts();
						} catch (e){}
					}
				}, null, true);
			}
			this.scrollView.syncUI();
		},
		/*
		 * wrap pageGalleries for touch scrolling
		 */
		pageWrap : function(pageGallery, direction) {
			if (pageGallery.ancestor('.page-wrap')) return pageGallery.ancestor('.page-wrap');
			var wrapStyle, 
				containerRect = _getContainerRect(this.container);
			switch(direction) {
				case 'V':
				case 'Vertical':
					wrapStyle = 'height:'+ containerRect.H+'px;';
				break;
				case 'H':
				default:
					wrapStyle = 'width:'+ containerRect.W+'px;';
				break;
				
			}
			// get window size for touch
			pageGallery.removeClass('hidden').removeClass('hide');
			var wrap = pageGallery.create('<div class="page-wrap" style="'+wrapStyle+'"></div>');
			pageGallery.replace(wrap);
			wrap.append(pageGallery);
			return wrap;
		}, 
		/*
		 *  add scrollWrap for touch, this is cfg.srcNode for ScrollView
		 */
		scrollWrap : function(scrollContent, total, direction){
			if (scrollContent.ancestor('.scroll-wrap')) return scrollContent.ancestor('.scroll-wrap');
			var	wrap = scrollContent.create('<div class="scroll-wrap"></div>');
			scrollContent.replace(wrap);
			wrap.append(scrollContent);
			return wrap;
		},
		/*
		 * add YUI3 ScrollView class for touch scrolling
		 */
		addScrollView_Page : function(cfg) {
			if (this.scrollView) return this.scrollView;
			if (!_Y.ScrollView) _Y = PM.Y;		// TODO: LazyLoad.extras() bug

			cfg = _Y.merge(this.cfg,cfg);
			var scrollView = new _Y.ScrollView({
		        srcNode: cfg.srcNode,
		        width: cfg.width,
		        flick: {
			        minDistance: cfg.flick.minDistance,
			        minVelocity: cfg.flick.minVelocity,
			        axis: cfg.width ? 'x' : 'y'
			    },
		    });
		    /* Plug in pagination support */
		    scrollView.plug(_Y.Plugin.ScrollViewPaginator, {
		        selector: ".page-wrap" // elements definining page boundaries
		    });
		    scrollView.plug(_Y.Plugin.ScrollViewScrollbars);
		    scrollView.render();
		    // update page
		    scrollView.pages.after('indexChange', function(e){
		    	_pageIndex = e.newVal;
		    	this.showPage(e.newVal);
		    }, this);
		    
		    // Prevent default image drag behavior
		    scrollView.get("contentBox").delegate("mousedown", function(e) {
		        e.preventDefault();
		    }, "img");
		    this.scrollView = scrollView;
		},
		addScrollView_Photo: function(target){
			if (this.photo_Scrollview) return this.photo_Scrollview;
			/*
			 *  touch scrollview
			 */
			var wrap = this.getScrollViewMarkup_Photo(target);
			var PHOTO_SCROLL_W = 640+20;
			var cfg = {
				srcNode: wrap,
				width: PHOTO_SCROLL_W,
			} 
			cfg = _Y.merge(this.cfg, cfg);
			wrap.all('.page-wrap').setStyles({width: cfg.width+'px'});
			
			// hack: add img WxH on scrollView render for proper alignment 
		    var firstPhotoHack = _Y.one('#centerbox li.page-wrap > img').setStyles({height:480});
		    
			var photo_Scrollview = new _Y.ScrollView({
		        srcNode: cfg.srcNode,
		        width: cfg.width,
		        flick: {
			        minDistance: cfg.flick.minDistance,
			        minVelocity: cfg.flick.minVelocity,
			        axis: cfg.width ? 'x' : 'y'
			    }
		    });
		    /* Plug in pagination support */
		    photo_Scrollview.plug(_Y.Plugin.ScrollViewPaginator, {
		        selector: "li.page-wrap" // elements definining page boundaries
		    });
		    
		    photo_Scrollview.render();
		    firstPhotoHack.setStyles({height:null});
		    
		    // show Photo
		    photo_Scrollview.pages.after('indexChange', function(e){
		    	_curPhotoIndex = e.newVal;
		    	this.showPhoto(e.newVal);
		    }, this);
		    
		    // scroll to clicked photo
		    photo_Scrollview.pages.set('index', _curPhotoIndex);
		    // this.showPhoto(_curPhotoIndex);
		    // photo_Scrollview.pages.scrollTo(_curPhotoIndex);
		    
		    // prevent click after a flick, move to this.activateLightBox()
		    // this.content.delegate("click", function(e) {
			    // // Prevent links from navigating as part of a scroll gesture
			    // if (Math.abs(this.photo_Scrollview.lastScrolledAmt) > 2) {
			        // e.preventDefault();
			    // }
			// }, "a", this);
		    
		    // Prevent default image drag behavior
		    photo_Scrollview.get("contentBox").delegate("mousedown", function(e) {
		        e.preventDefault();
		    }, "img");
		    this.photo_Scrollview = photo_Scrollview;
		    return photo_Scrollview;
		},
		getScrollViewMarkup_Photo: function(target, force){
			var srcNode = this.container.one("#centerbox .scroll-wrap");
			if (srcNode) 
				return srcNode;
			
			_curPhotoIndex = -1;	// rload photo==1 to center in ScrollView
			var photo, src, tokens, title
				markup = "<li class='page-wrap'><img src='{src}' qsrc='{qsrc}' title='{title}'></li>", 
				scrollContent = _Y.Node.create('<ul></ul>');
			for ( var i in this.indexedPhotos) {
				photo = this.indexedPhotos[i];
				src = photo.img.get('src');
				if (!src) src = photo.img.getAttribute('qsrc');
				src = _stripCrop(src);
				
				tokens = {src: '', qsrc: ''};
				tokens['title'] = photo.img.get('title');
				if (photo.img == target) {
					_curPhotoIndex = parseInt(i);		// found clicked photo
					tokens['src'] = src;
				} else if ( i == 0 ) {	// preload photo 0 for proper alignment in scrollView
					tokens['src'] = src;					
				} else if (CONFIG.DEFER_IMG_LOAD) {
					tokens['qsrc'] = src;
				} else tokens['src'] = src;
				scrollContent.append(_Y.substitute(markup, tokens));
			}
			// var closeBox = this.container.one("#centerbox #closeBox");
			// this.container.one("#centerbox").setContent(closeBox).append(scrollContent);
			this.container.one("#centerbox").setContent(scrollContent);
			return this.scrollWrap(scrollContent, this.indexedPhotos.length);
		},		
		startListeners : function(start) {
			if (start === false) {
				this.stopListeners();
			} else {
				if (!this.isPreview) {
					// page nav
					if (!this.listen['pageClick']) {
						this.listen['pageClick'] = this.container.delegate(
							'click', 
							this.handlePageClick,
							'div#paging > div.next, div#paging > div.prev', 
							this
						);
					}
					if (!this.listen['lightbox']) {
						this.listen['lightbox'] = this.container.one('#glass')
							.delegate('click', this.handleLightboxClick,
									'div, span', this);
					}
					// No Key listeners for touch
					if (!this.listen['keypress']) this.listen['keypress'] = _Y.on('keypress', this.keyAccelerate,
							document, this);
					if (!this.listen['activateLightBox']) this.listen['activateLightBox'] = this.content.delegate(
							"click", this.activateLightBox,
							'div.pageGallery > img', this);					
				}
				this.listen.winResize = _Y.on('resize', function(e){
						if (this.content._isResizing) return;
						this.content._isResizing = true;
						this.winResize(e);
					},	window, this);
			}
		},

		stopListeners : function(name) {
			if (name) {
				try {
					this.listen[name].detach();
				} catch (e) {
				}
			} else {
				for ( var name in this.listen) {
					this.listen[name].detach();
				}
			}
		},
		/*
		 * check if all '.pageGallery > img' are loaded 
		 * TODO: should we be using _isSrcCached()??
		 */
		isPageLoaded : function(loading){
			loading.all('img').some(function(n,i,l){
				if (!n.get('naturalHeight')) {
					done = false;
					return true;	// break .some()
				}
				done = true;
			});
			return done;
		},
		/**
		 * load deferred IMG on pageGallery by setting IMG.src from IMG.qsrc
		 */
		load_DeferredPageImages : function(page) {
			page = page || this;
			page.all('img').each(function(photo) {
				var src = photo.getAttribute('qsrc');
				if (src) {
					photo.setAttribute('src', src);
					photo.setAttribute('qsrc', '');
				}
			});
		},
		load_ShareThisScripts : function(){
			if (!_load_sharethis) return;
			
			var publisherId = 'ur-1fda4407-f1c8-d8ff-b0bd-1f1ff46eeb72';
			var markup = '<script type="text/javascript">var switchTo5x=false;</script>';
			_Y.one('head').append(markup);
			var sharethis = ["http://w.sharethis.com/button/buttons.js"];
			_Y.Get.script(sharethis, {onSuccess : function(e) {
			    stLight.options({publisher: publisherId });
			    // stLight.onReady();
			}});
			_Y.one('div.sharethis').removeClass('hide');
		},
		/*
		 * called on next page click
		 */
		showPage : function(index) {
			if (index < 0 || index > _totalPages-1) return;
			var title, pages = this.content.all('div.pageGallery');
			pages.each(function(page, i) {
				if (i == index) {
					if (CONFIG.DEFER_IMG_LOAD) this.load_DeferredPageImages(page);
					page.removeClass('hidden').removeClass('hide');
					title = page.getAttribute('title');
					return;
				} else if (i == index+1) {
					// pre-load next page
					if (CONFIG.DEFER_IMG_LOAD) {
						var cancel = _Y.later(2000, this, function(page){
							var loading = pages.item(index);
							if (this.isPageLoaded(loading)) {
								this.load_DeferredPageImages(page);
								cancel.cancel();
							}
						}, page, true);
					}
				}
			}, this);
			
			if (!this.isPreview) {
				var header;
				if (title) header = '<b>'+ title + '</b>&nbsp;&nbsp;<span style="font-size:0.8em;">(' +(index + 1) + "/" + (_totalPages)+ ')</span>';
				else header = (index + 1) + "/" + (_totalPages);
				this.container.one('#pagenum').set('innerHTML',	header);
				if (index == 0) {
					this.container.one('#paging .prev').addClass('disabled');
				} else
					this.container.one('#paging .prev').removeClass('disabled');
				if (index == _totalPages - 1) {
					this.container.one("#paging .next").addClass('disabled');
				} else
					this.container.one("#paging .next").removeClass('disabled');
			}
		},
		handlePageClick: function(e, direction) {
			if (e) {
				var button = _Y.Lang.isString(e) ? _Y.one('#paging .'+e) : e.currentTarget;
				if (button.hasClass('disabled')) return;
			}
			if (direction == 'next' || button.hasClass('next')) this.scrollView.pages.next();
			else if (direction == 'prev' || button.hasClass('prev')) this.scrollView.pages.prev();
		},
		indexPhotos : function() {
			var indexed = [];
			var t, l, page, img;
			this.content.all('div.pageGallery').each(function(page, p) {
				if (page.get('id') == 'share')
					return;
				page.all('img').each(function(img, j) {
					t = Math.round(_px2i(img.getStyle('top')));
					l = Math.round(_px2i(img.getStyle('left')));
					indexed.push( {
						index : [ p, t, l ],
						page : p,
						img : img
					});
				});
			}, this);
			return indexed.sort(_sortByIndexASC);
		},

		/* Called on click of a photo */

		activateLightBox : function(e) {
			// Prevent activation as part of a scroll gesture
		    if (Math.abs(this.scrollView.lastScrolledAmt) > 2) {
		        e.preventDefault();
		        return;
		    }
			var overlay = this.container.one("#glass").removeClass('hide');
			var centerBox = this.container.one("#centerbox").removeClass('hide');;
			centerBox.setStyles({
				'opacity':0,
				'left': (centerBox.get('winWidth') - centerBox.get('clientWidth'))/2,
				'top': (centerBox.get('winHeight') - centerBox.get('clientHeight'))/2,  	
			})
			this.animateOpacity(1, this.cfg.animation.overlayDuration, centerBox);						
			if (this.photo_Scrollview) {
				// just find clicked photo
				for ( var i in this.indexedPhotos) {
					if (this.indexedPhotos[i].img == e.target) {
						_curPhotoIndex = parseInt(i);	// found clicked photo
						break;
					}
				}
				this.photo_Scrollview.show();
				this.photo_Scrollview.pages.set('index', _curPhotoIndex);
			} else {
			    this.photo_Scrollview = this.addScrollView_Photo(e.target);
			}
		},

		handleLightboxClick : function(e) {
			var target = e.target;
			var id = target.get('id');
			switch (id) {
			case 'nextPhoto':
				this.nextPhotoClick();
				break;
			case 'prevPhoto':
				this.prevPhotoClick();
				break;
			case 'bottom':
			case 'closeBox':
				this.closeLightBox();
				break;
			default:
				return;
			}
			e.halt();
		},

		/*
		 * Called on centerbox close , called by both close button & overlay
		 * click
		 */
		closeLightBox : function(e) {
			if (this.container.one("#centerbox").hasClass('hide'))
				return;

			var _container = this.container;
			this.animateOpacity(0, this.cfg.animation.overlayDuration,
					_container.one("#centerbox"), function() {
						// hide #glass after animation complete
						this.container.one("#centerbox").addClass('hide');
						this.container.one("#glass").addClass('hide');
						this.photo_Scrollview.hide();
					}, this);
		},
		showPhoto : function(index) {
			var preload = [index, index+1, index-1]; // load before and after
			var src, img,
				photos = _Y.all('#centerbox li.page-wrap > img');
			for (var i in preload) {
				try {
					img = photos.item(preload[i]);
					if (!img) continue; 
					src = img.getAttribute('qsrc');
					if (src) {
						img.setAttribute('src', src);
						img.setAttribute('qsrc', '');
					} 
				} catch (e) {
					console.error('Player.showPhoto() for scrollView');
				}
			}
		},
		nextPhotoClick : function() {
			var page = 0, top = 1, left = 2;
			if (_curPhotoIndex + 1 == this.indexedPhotos.length)
				return;
				
			var thisPhoto = this.indexedPhotos[_curPhotoIndex];
			var nextPhoto = this.indexedPhotos[++_curPhotoIndex];
			if (thisPhoto.index[page] != nextPhoto.index[page]) {
				// we are on last IMG of current page
				this.handlePageClick(null, 'next');
			}
			
			this.showPhoto(nextPhoto);
		},
		prevPhotoClick : function() {
			var page = 0, top = 1, left = 2;
			if (_curPhotoIndex == 0) 
				return;
			
			var thisPhoto = this.indexedPhotos[_curPhotoIndex];
			var nextPhoto = this.indexedPhotos[--_curPhotoIndex];
			if (thisPhoto.index[page] != nextPhoto.index[page]) {
				// we are on first IMG of current page
				this.handlePageClick(null, 'prev');
			}
			this.showPhoto(nextPhoto);
		},

		/*
		 * NOTE: when deployed in "Designer", containerH != containerH
		 */
		winResize : function(e) {
			var containerRect = _getContainerRect(this.container, this.cfg, 'force');		
			// scale pageGalleries
			var pages = this.content.all('div.pageGallery');
this.container.addClass('hide');			
			pages.each(function(page, i) {
// console.warn("winResize page="+i+"pageRect="+ page.origRect.W +':'+ page.origRect.W);					
// try{
// parent.window.SNAPPI.Y.one('.properties').append("<div style='color:green;position:relative;z-index:999;'>winResize() page="+i+", pageRect="+ page.origRect.W +':'+ page.origRect.H+"</div>");
// } catch (e){}
				containerRect.node = page;
				this.scale( containerRect );
			}, this);
			this.showPage(_pageIndex);
this.container.removeClass('hide');	
			
			// center scrollView Photo
			if (this.photo_Scrollview && this.photo_Scrollview.get('visible')) {
				this.photo_Scrollview.hide();
				var centerBox = this.container.one('#centerbox');
				centerBox.setStyles({
					'left': (centerBox.get('winWidth') - centerBox.get('clientWidth'))/2,
					'top': (centerBox.get('winHeight') - centerBox.get('clientHeight'))/2,  	
				})
				_Y.later(500, this, function(){
					this.photo_Scrollview.show();
				}, this)
			}
			_Y.later(50, this, function(){
				this.content._isResizing = false;		// enable winResize	
			});
		
			if (e && PM.pageMakerPlugin) PM.pageMakerPlugin.external_Y.fire('snappi-pm:resize', this, containerH);
		},

		scale : function(cfg) {
			cfg = _Y.merge(cfg);	// copy
			var nativeMaxRes, MAX_HEIGHT;
			try {	// get pageGallery maxH for rendering
            	MAX_HEIGHT = (Plugin.sceneCfg.fnDisplaySize.h-82) || PM.util.NATIVE_PAGE_GALLERY_H;	
            } catch(e){
            	MAX_HEIGHT = this.cfg.NATIVE_PAGE_GALLERY_H;
            }
			var pageRect, page = cfg.node;		// deprecate cfg.element
			var scaleTo, scale, ratio_w = 0, ratio_h = 0;
			try {
				pageRect = page.origRect;
			} catch (e) {
				pageRect = _Y.Node.getDOMNode(page);
			}
// console.warn("pageRect="+ pageRect.W +':'+ pageRect.H);	
try {
parent.window.SNAPPI.Y.one('.properties').append("<div style='color:red;position:relative;z-index:999;'>scale() , pageRect="+ pageRect.W +':'+ pageRect.H+"</div>");
} catch (e){}
			if (cfg.W && cfg.H && (cfg.H / cfg.W > pageRect.H / pageRect.W)) {
				scaleTo = 'same-width';
				// delete cfg.H;  	// use cfg.W as bound, all pages same width
			} else {
				scaleTo = 'same-height';
				// delete cfg.W;  	// use cfg.H as bound, all pages same height
			}

			var offset, scaledRect ={}, origRect = page.origRect;
			if (scaleTo == 'same-width') {
				ratio_w = origRect.W / cfg.W;
				nativeMaxRes = origRect.W
						/ (MAX_HEIGHT / origRect.H * origRect.W);
				scale = Math.max(ratio_w, ratio_h);
			} else if (scaleTo == 'same-height') {
				ratio_h = origRect.H / cfg.H;
				nativeMaxRes = (origRect.H / MAX_HEIGHT);
				scale = Math.max(ratio_w, ratio_h);
			}
			// do not scale larger than native resolution
			scale = Math.max(scale, nativeMaxRes);
			scale = (scale > 1) ? scale : 1;

			// scale pages relative to original layout
			scaledRect.W = (origRect.W) / scale;
			scaledRect.H = (origRect.H) / scale;
			page.setStyles( {
				// left, top set by CSS
				width : scaledRect.W + "px",
				height : scaledRect.H + "px",
				backgroundColor : 'lightgray'
			});
			if (this.isPreview) {
				offset={X:0,Y:0};	// space for preview offset
			}
			// scale photos relative to original layout
			var photos = page.all("img");
			var borderWidth, bottomRight;	// space for border width
			photos.each(function(photo) {
				bottomRight = bottomRight || photo;
				origRect = photo.origRect;
				// +5 to compensate for rounding errors
				if (origRect.X+origRect.W > bottomRight.origRect.X+bottomRight.origRect.W+5) bottomRight = photo;
				if (origRect.Y+origRect.H > bottomRight.origRect.Y+bottomRight.origRect.H+5) bottomRight = photo;
				if (this.isPreview) { // move position by unscaled offset
						scaledRect = {
							left : origRect.X / scale + offset.X + "px",
							top : origRect.Y / scale + offset.Y + "px",
							width : origRect.W / scale + "px",
							height : origRect.H / scale + "px"
						};
				} else {
					scaledRect = {
						left : origRect.X / scale + "px",
						top : origRect.Y / scale + "px",
						width : origRect.W / scale + "px",
						height : origRect.H / scale + "px"
					};
				}
				photo.setStyles(scaledRect);
			}, this);
		
			// page must be .hidden or visible for clientLeft/Top
			borderWidth = _px2i(bottomRight.getStyle('borderWidth'));
			var br = {
				bottom: ((bottomRight.origRect.Y + bottomRight.origRect.H) / scale + 2*borderWidth) ,
				right: ((bottomRight.origRect.X + bottomRight.origRect.W)  / scale + 2*borderWidth) ,
			}
			page.setStyles( {
				// left, top set by CSS
				width : br.right+borderWidth +  "px",
				height : br.bottom+borderWidth +  "px",
			});
		},

		/*
		 * animate opacity of the overlay
		 */
		animateOpacity : function(opacity, duration, node, onEndCallback) {
			var animation = new _Y.Anim( {
				node : node,
				to : {
					opacity : opacity
				}
			});
			animation.set('duration', duration);
			animation.set('easing', _Y.Easing.bounceBoth);
			if (onEndCallback != undefined && onEndCallback instanceof Function) {
				animation.on('end', function(e) {
					onEndCallback.call(this);
				}, this);
			}
			animation.run();
		},

		/*
		 * Animates the center box with enlarged image Accepts element object of
		 * the image to enlarged
		 */
		animateBox : function(X, W, H, delta) {
			var img = this.container.one("#centerbox > img");
			img.setStyle('opacity', 0.5);
			var boxAnim = new _Y.Anim( {
				node : this.container.one("#centerbox"),
				to : {
					width : W,
					height : H,
					top: X,
				}
			});
			var duration = Math.min(delta / 1000, this.cfg.animation.overlayDuration);
			boxAnim.set('easing', _Y.Easing.bounceBoth);
			boxAnim.set('duration', duration);
			var detach2 = boxAnim.on('end', function(e) {
				detach2.detach();
				this.container.one("#closeBox").removeClass('hidden');
				this.animateOpacity(1, duration, img);
			}, this);
			boxAnim.run();
		},

		/*
		 * Key press functionality of next & previous buttons
		 */
		keyAccelerate : function(e) {
			if (!this.container.one("#centerbox").hasClass('hide')) {
				// change Photo
				var charStr = e.charCode + '';
				if (charStr.search(this.cfg.charCode.nextPatt) == 0) {
					this.nextPhotoClick(); e.preventDefault();
				} else if (charStr.search(this.cfg.charCode.prevPatt) == 0) {
					this.prevPhotoClick(); e.preventDefault();
				} else if (charStr.search(this.cfg.charCode.closePatt) == 0) {
					this.closeLightBox(); e.preventDefault();
				}
			} else // change pages
			{
				var charStr = e.charCode + '';
				if (charStr.search(this.cfg.charCode.nextPatt) == 0) {
					this.handlePageClick(e, 'next'); e.preventDefault();
				} else if (charStr.search(this.cfg.charCode.prevPatt) == 0) {
					this.handlePageClick(e, 'prev'); e.preventDefault();
				}
			}
		},		
	}

	
	if (PM.bootstrapY) {
		/*
		 * yui3 config
		 */
		if (PM.yuiConfig == undefined) {
			PM.namespace('SNAPPI.PM.yuiConfig');
			PM.yuiConfig.yui = { // GLOBAL
				base : "http://yui.yahooapis.com/combo?3.3.0/build/",
				timeout : 10000,
				loadOptional : false,
				combine : true,
				filter : "MIN",
				// filter: "DEBUG",
				allowRollup : true
			};
		}

		YUI(PM.yuiConfig.yui).use("event-delegate", "node", "anim", "get", 'substitute',
			"scrollview-base", 
			"scrollview-paginator",
			"scrollview-scrollbars",  // bug in webkit rendering
		/*
		 * yui callback
		 */
		function(Y, result) {
			if (!result.success) {
				_Y.log('Load failure: ' + result.msg, 'warn', 'Example');
			} else {
				PM.Y = Y;
				if (_Y === null) _Y = Y;
				/*
				 * _Y.Node/DOM Element Helper Functions
				 */
				_Y.Node.prototype.dom = function() {
					return _Y.Node.getDOMNode(this);
				};
				_Y.Node.prototype.ynode = function() {
					return this;
				};
				try {
					HTMLElement.prototype.dom = function() {
						return this;
					};
					HTMLElement.prototype.ynode = function() {
						return _Y.one(this);
					};
				} catch (e) {
				}
				var player = new PM.Player();
				_Y.on('contentready', function(e){
					player.init();
				},'#content > div:first-child', this )
			}
		});
	}

})();
