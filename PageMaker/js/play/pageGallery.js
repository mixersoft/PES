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
		// var Plugin = null;
		var PM = SNAPPI.namespace('SNAPPI.PM');
		PM.namespace('PM.onYready');
		// Yready init
		PM.onYready.Play = function(Y){
			if (_Y === null) _Y = Y;
			PM.bootstrapY = false;
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
		// is
		// determined in the
		// server conde
		FOOTER_H : 142, // based on HTML markup
		MARGIN_W : 22,
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
		charCode : {
			nextPatt : /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
			// keypad right
			prevPatt : /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
			// keypad left
			closePatt : /(^27$)/
		// escape
		}
	};

	var _pageIndex = 0, _curPhotoIndex, _totalPages = 0;
	/*
	 * helper functions
	 */
	var _px2i = function(sz) {
		var nsz = sz.replace(/px/gi, "");
		nsz = parseFloat(nsz);
		return PM.Y.Lang.isNumber(nsz) ? nsz : null;
	};

	var _getFromQs = function(name) {
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

	var Player = function(cfg) {
		cfg = cfg || {};
		this.container = null; 		// outer node, default 'BODY'
		this.content = null;		// child of this.container, parent of .pageGallery
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
			var containerH = 0 - this.cfg.FOOTER_H, containerW = 0 - this.cfg.MARGIN_W;
			if (this.container.get('tagName') == 'BODY') {
				containerH += this.container.get('winHeight');
				containerW += this.container.get('winWidth');
			} else {
				containerH += this.container.get('clientHeight');
				containerW += this.container.get('clientWidth');
			}
			this.indexedPhotos = this.indexPhotos();

			_totalPages = 0;
			var offset, origRect, 
				pages = this.content.all('div.pageGallery');
			pages.each(function(page, i) {
				if (page.get('id') == 'share')
					return;
				_totalPages++;

				if (page.hasClass('hide')) {
					page.removeClass('hide');
					page.addClass('hidden');	// cant get offsets with page.hide
				}
				origRect = {
					X : _px2i(page.getStyle('left')),
					Y : _px2i(page.getStyle('top')),
					W : _px2i(page.getStyle('width')),
					H : _px2i(page.getStyle('height'))
				};
				if (this.isPreview) {
					if (page.hasClass('hide')) {
						page.addClass('hidden');
						page.removeClass('hide');
						var restoreHide = true;
					}
					// need to get layout info for this page
					offset={X:0,Y:0};	// use position:relative for this.content
					if (restoreHide) {
						page.addClass('hide');
						page.removeClass('hidden');
					}
									
					// page.addClass('hidden');
					// page.removeClass('hide');
					// // need to get layout info for this page
					// offset = {
						// X : page.get('offsetLeft'),
						// Y : page.get('offsetTop')
					// };
					// origRect = _Y.merge(origRect, offset);
					// page.addClass('hide');
					// page.removeClass('hidden');
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
				}, this);

				// scale photos on each page to target height or width
				try {
					var pageRect = page.origRect;
				} catch (e) {
					pageRect = _Y.Node.getDOMNode(page);
				}
				if (containerH / containerW > pageRect.H / pageRect.W) {
					this.scale( {
						w : containerW,
						element : page
					});
				} else {
					this.scale( {
						h : containerH,
						element : page
					});
				}
			}, this);

			var pageNo = _getFromQs('page') || 1;
			if (pageNo == 'last') {
				pageNo = _totalPages;
				_pageIndex = pageNo - 1; // zero based index
			} else if (parseInt(pageNo)) _pageIndex = parseInt(pageNo)-1;
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

			}
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
							'#nextPage, #prevPage', 
							this
						);
					}
					// if (!this.listen['prevPageClick']) this.listen['prevPageClick'] = this.container.one(
							// '#prevPage').on('click', this.prevPageClick, this);
					// // photo nav
					if (!this.listen['lightbox']) {
						this.listen['lightbox'] = this.container.one('#glass')
							.delegate('click', this.handleLightboxClick,
									'div, span', this);
					}
					// TODO: use custom-hover to subscribe to keypress
					if (!this.listen['keypress']) this.listen['keypress'] = _Y.on('keypress', this.keyAccelerate,
							document, this);
					if (!this.listen['activateLightBox']) this.listen['activateLightBox'] = this.content.delegate(
							"click", this.activateLightBox,
							'div.pageGallery > img', this);					
				}
				this.listen.winResize = _Y.on('resize', this.winResize,
						window, this);
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
		 * called on next page click
		 */
		showPage : function(index) {
			if (!this.isPreview) {
				this.container.one('#pagenum').set('innerHTML',
						(index + 1) + "/" + (_totalPages));
				if (index == 0) {
					this.container.one('#prevPage').addClass('disabled');
				} else
					this.container.one('#prevPage').removeClass('disabled');
				if (index == _totalPages - 1) {
					this.container.one("#nextPage").addClass('disabled');
				} else
					this.container.one("#nextPage").removeClass('disabled');
			}

			var pages = this.content.all('div.pageGallery');
			pages.each(function(page, i) {
				if (page.get('id') == 'share')
					return;
				if (i == index) {
					page.removeClass('hidden').removeClass('hide');
				} else {
					page.addClass('hide');
				}
			}, this);
		},
		handlePageClick: function(e) {
			var button = _Y.Lang.isString(e) ? _Y.one('#'+e) : e.currentTarget;
			if (button.hasClass('disabled')) return;
			switch (button.get('id')) {
				case 'prevPage':
					this.showPage(--_pageIndex);
					break;		
				case 'nextPage':
					this.showPage(++_pageIndex);
					break;
			}
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
			if (this.container.one("#centerbox").get('clientHeight')>0)
				return;
			var target = e.target;
			// find index of clicked photo
			for ( var i in this.indexedPhotos) {
				if (target == this.indexedPhotos[i].img) {
					_curPhotoIndex = i;
					break;
				}
			}
			var overlay = this.container.one("#glass").removeClass('hide');
			var centerBox = this.container.one("#centerbox").setStyle('opacity',0)
					.removeClass('hide');
			var player = this;
			player.showPhoto(this.indexedPhotos[i], 'animate');
			this.animateOpacity(1, this.cfg.animation.overlayDuration,
					centerBox);			
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
					_container.one("#centerbox").setStyles( {
						width : this.cfg.layout.centerBoxW + 'px',
						height : this.cfg.layout.centerBoxH + 'px'
					}).addClass('hide');
					_container.one("#lightBoxPhoto").set("src", "");
					_container.one("#glass").addClass('hide');
				});
		},

		showPhoto : function(photo, animate) {
			var animation = this.cfg.animation;
			var img = this.container.one("#centerbox > img");
			var src = _stripCrop(photo.img.get('src'));
			var isCached = _isSrcCached(src);
			var _animateOnDelta = function(){
				var dim = _getNaturalDim(img, animation);
				var delta, centerbox = this.container.one("#centerbox");
				delta = Math.abs(centerbox.get('clientWidth') - dim.W)
						+ Math.abs(centerbox.get('clientHeight') - dim.H);
				if (animate || delta >= 50) {
					img.setStyle('opacity', 0.5);
					this.animateBox.call(this, dim.W, dim.H, 1000);
					_Y.later(2000, img, function(){
						img.setStyle('opacity', 1);		// timeout
					})	
				} else {
					centerbox.setStyles( {
						width : dim.W,
						height : dim.H
					});
					this.container.one("#closeBox").removeClass('hidden');
					img.setStyle('opacity', 1);
				}				
			}
			if (isCached) {
				img.set('src', src);
				_Y.later(50, this, function(){
					return _animateOnDelta.call(this);
				})
			} else {
				// async load img from server
				img.setStyle('opacity', 0.5);
				var detach = img.on('load', function(e, img, animation) {
					detach.detach();
					return _animateOnDelta.call(this);
				}, this, img, this.cfg.animation);					
				img.set('src', src);
			}
			if (_curPhotoIndex == this.indexedPhotos.length - 1) {
				// at last IMG, disable Next
				this.container.one("#nextPhoto").addClass('hide');
			} else {
				this.container.one("#nextPhoto").removeClass('hide');
			}	
			if (_curPhotoIndex == 0) {
				// at first IMG, disable Prev
				this.container.one("#prevPhoto").addClass('hide');
			} else {
				this.container.one("#prevPhoto").removeClass('hide');
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
				this.handlePageClick('nextPage');
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
				this.handlePageClick('prevPage');;
			}
			this.showPhoto(nextPhoto);
		},

		/*
		 * NOTE: when deployed in "Designer", containerH != containerH
		 */
		winResize : function(e) {
			var containerH = 0 - this.cfg.FOOTER_H, containerW = 0 - this.cfg.MARGIN_W;
			if (this.container.get('tagName') == 'BODY') {
				containerH += this.container.get('winHeight');
				containerW += this.container.get('winWidth');
			} else {
				// containerH += _px2i(this.container.getComputedStyle('height'));
				// containerW += _px2i(this.container.getComputedStyle('width'));
				containerH += this.container.get('clientHeight');
				containerW += this.container.get('clientWidth');
			}			
			
			var pages = this.content.all('div.pageGallery');
			pages.each(function(page, i) {
				if (page.hasClass('hide')) {
					page.removeClass('hide');
					page.addClass('hidden');	// cant get offsets with page.hide
				}
				if (page.get('id') != "share") {
					var pageRect = {
						W : _px2i(page.getStyle('width')),
						H : _px2i(page.getStyle('height'))
					};
					if (containerH / containerW > pageRect.H / pageRect.W) {
						this.scale( {
							w : containerW,
							element : page
						});
					} else {
						this.scale( {
							h : containerH,
							element : page
						});
					}
				}
			}, this);
			this.showPage(_pageIndex);
		},

		scale : function(cfg) {
			var nativeMaxRes, MAX_HEIGHT = this.cfg.NATIVE_PAGE_GALLERY_H;
			var page = cfg.element;

			var scale, ratio_w = 0, ratio_h = 0;
			var offset, origRect = page.origRect;
			if (cfg.w != undefined) {
				ratio_w = origRect.W / cfg.w;
				nativeMaxRes = origRect.W
						/ (MAX_HEIGHT / origRect.H * origRect.W);
				scale = Math.max(ratio_w, ratio_h);
			}
			if (cfg.h != undefined) {
				ratio_h = origRect.H / cfg.h;
				nativeMaxRes = (origRect.H / MAX_HEIGHT);
				scale = Math.max(ratio_w, ratio_h);
			}
			// do not scale larger than native resolution
			scale = Math.max(scale, nativeMaxRes);
			scale = (scale > 1) ? scale : 1;

			// scale pages relative to original layout
			page.setStyles( {
				// left, top set by CSS
				width : (origRect.W) / scale + "px",
				height : (origRect.H) / scale + "px",
				backgroundColor : 'lightgray'
			});
			if (this.isPreview) {
				// get actual offset AFTER scaled size is set
				if (page.hasClass('hide')) {
					page.addClass('hidden');
					page.removeClass('hide');
					var restoreHide = true;
				}
				// need to get layout info for this page
				offset={X:0,Y:0};	// space for preview offset
				if (restoreHide) {
					page.addClass('hide');
					page.removeClass('hidden');
				}
			}
			// scale photos relative to original layout
			var photos = page.all("img");
			var border_offset, bottomRight;	// space for border width
			photos.each(function(photo) {
				bottomRight = bottomRight || photo;
				var scaledRect, origRect = photo.origRect;
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
			border_offset= {
				X: bottomRight.get('clientLeft'),
				Y: bottomRight.get('clientTop'),
			}
			var br = {
				bottom: ((bottomRight.origRect.Y + bottomRight.origRect.H) / scale + 2*border_offset.Y) ,
				right: ((bottomRight.origRect.X + bottomRight.origRect.W)  / scale + 2*border_offset.X) ,
			}
			page.setStyles( {
				// left, top set by CSS
				width : br.right+border_offset.X +  "px",
				height : br.bottom+border_offset.Y +  "px",
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
		animateBox : function(W, H, delta) {
			var img = this.container.one("#centerbox > img");
			img.setStyle('opacity', 0.5);
			var boxAnim = new _Y.Anim( {
				node : this.container.one("#centerbox"),
				to : {
					width : W,
					height : H
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
				e.preventDefault();
				var charStr = e.charCode + '';
				if (charStr.search(this.cfg.charCode.nextPatt) == 0) {
					this.nextPhotoClick();
				} else if (charStr.search(this.cfg.charCode.prevPatt) == 0) {
					this.prevPhotoClick();
				} else if (charStr.search(this.cfg.charCode.closePatt) == 0) {
					this.closeLightBox();
				}
			} else // change pages
			{
				e.preventDefault();
				var charStr = e.charCode + '';
				if (charStr.search(this.cfg.charCode.nextPatt) == 0) {
					this.nextPageClick();
				} else if (charStr.search(this.cfg.charCode.prevPatt) == 0) {
					this.prevPageClick();
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

		YUI(PM.yuiConfig.yui).use("event-delegate", "node", "anim",
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

				_Y.on("domready", function() {
					player.init();
				});
			}
		});
	}

})();
