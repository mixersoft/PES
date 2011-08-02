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
	/*
	 * protected methods and variables
	 */
	var _namespace = function() {
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
	_namespace('SNAPPI.PM');

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
		return SNAPPI.PM.Y.Lang.isNumber(nsz) ? nsz : null;
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
		var Y = SNAPPI.PM.Y;
		this.cfg = Y.merge(CONFIG, cfg);
		this.container = Y.one(this.cfg.container);
		this.content = Y.one(this.cfg.content); // parent of div.pageGallery
		this.isPreview = this.cfg.isPreview
				|| SNAPPI.PM.Player.bootstrap == false;

		// content
		this.listeners = {}; // detach handlers for active listener
		this.init = function(e) {
			var containerH = 0 - this.cfg.FOOTER_H, containerW = 0 - this.cfg.MARGIN_W;
			if (this.container.get('tagName') == 'BODY') {
				containerH += this.container.get('winHeight');
				containerW += this.container.get('winWidth');
			} else {
				containerH += _px2i(this.container.getComputedStyle('height'));
				containerW += _px2i(this.container.getComputedStyle('width'));
			}
			indexedPhotos = this.indexPhotos();

			_totalPages = 0;
			var offset, origRect, pages = this.content.all('div.pageGallery');
			pages.each(function(page, i) {
				var Y = SNAPPI.PM.Y;
				if (page.get('id') == 'share')
					return;
				_totalPages++;

				origRect = {
					X : _px2i(page.getStyle('left')),
					Y : _px2i(page.getStyle('top')),
					W : _px2i(page.getStyle('width')),
					H : _px2i(page.getStyle('height'))
				};
				if (this.isPreview) {
					page.addClass('hidden');
					page.removeClass('hide');
					// need to get layout info for this page
					offset = {
						X : page.get('offsetLeft'),
						Y : page.get('offsetTop')
					};
					origRect = Y.merge(origRect, offset);
					page.addClass('hide');
					page.removeClass('hidden');
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
					var Y = SNAPPI.PM.Y;
					pageRect = Y.Node.getDOMNode(page);
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
			}
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
				this.container.one('#glass > .loading').remove();
			}
		};

		this.startListeners = function(start) {
			if (start === false) {
				this.stopListeners();
			} else {
				var Y = SNAPPI.PM.Y;
				if (!this.isPreview) {
					// page nav
					this.listeners.nextPageClick = this.container.one(
							'#nextPage').on('click', this.nextPageClick, this);
					this.listeners.prevPageClick = this.container.one(
							'#prevPage').on('click', this.prevPageClick, this);
					// photo nav
					this.listeners.lightbox = this.container.one('#glass')
							.delegate('click', this.handleLightboxClick,
									'div, span', this);
					// TODO: use custom-hover to subscribe to keypress
					this.listeners.keypress = Y.on('keypress', this.keyAccelerate,
							document, this);
					this.listeners.activateLightBox = this.content.delegate(
							"click", this.activateLightBox,
							'div.pageGallery > img', this);					
				}
				this.listeners.winResize = Y.on('resize', this.winResize,
						window, this);
			}
		};

		this.stopListeners = function(name) {
			if (name) {
				try {
					this.listeners[name].detach();
				} catch (e) {
				}
			} else {
				for ( var name in this.listeners) {
					this.listeners[name].detach();
				}
			}
		};

		/*
		 * called on next page click
		 */
		this.showPage = function(index) {
			var Y = SNAPPI.PM.Y;
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
					page.removeClass('hide');
				} else {
					page.addClass('hide');
				}
			}, this);
		};

		this.nextPageClick = function(e) {
			if (_pageIndex < _totalPages - 1) {
				this.showPage(++_pageIndex);
			}
		};

		/*
		 * called on previous page click
		 */
		this.prevPageClick = function(e) {
			if (_pageIndex > 0) {
				this.showPage(--_pageIndex);
			}
		};

		this.indexPhotos = function() {
			var indexedPhotos = [];
			var t, l, page, img;
			this.content.all('div.pageGallery').each(function(page, p) {
				if (page.get('id') == 'share')
					return;
				page.all('img').each(function(img, j) {
					t = Math.round(_px2i(img.getStyle('top')));
					l = Math.round(_px2i(img.getStyle('left')));
					indexedPhotos.push( {
						index : [ p, t, l ],
						page : p,
						img : img
					});
				});
			}, this);
			return indexedPhotos.sort(_sortByIndexASC);
		};

		/* Called on click of a photo */

		this.activateLightBox = function(e) {
			var Y = SNAPPI.PM.Y;
			if (!this.container.one("#centerbox").hasClass('hide'))
				return;
			var target = e.target;
			// find index of clicked photo
			for ( var i in indexedPhotos) {
				if (target == indexedPhotos[i].img) {
					_curPhotoIndex = i;
					break;
				}
			}
			var overlay = this.container.one("#glass").removeClass('hide');
			var centerBox = this.container.one("#centerbox").setStyle('opacity',0)
					.removeClass('hide');
			var player = this;
//			player.showPhoto(indexedPhotos[i], 'animate');
			this.animateOpacity(1, this.cfg.animation.overlayDuration,
					centerBox);			
			
			/*
			 *  setup scrollView content UL > LI 
			 */
			var scrollViewNode = Y.one('#scrollview');
			var index;
			if (!scrollViewNode){
				var scrollViewNode = this.container.create('<div id="scrollview-content" class="yui3-scrollview-loading"><ul></ul></div>');
				var frame = this.container.one("#centerbox").setContent(scrollViewNode);
				frame.setStyles({
					'background': 'none',
					'border': 'none',
					'width': 'auto',
					'overflow': 'auto'
				});
				
				var li, ul = this.container.one("#centerbox ul");
				for (var i in indexedPhotos) {
					var img = indexedPhotos[i].img;
					var src = img.get('src');
	//				src = src.replace('bp', 'bm');
					li = ul.create('<li><img src="'+src+'"></li>');
					ul.append(li);
					if (img == target) index = parseInt(i);
				}
				
				/*
				 * init scrollView, from http://developer.yahoo.com/yui/3/examples/scrollview/scrollview-paging_source.html
				 */
				var scrollView = new Y.ScrollView({
	                id: "scrollview",
	                srcNode : '#scrollview-content',
	                width : 640,
	                flick: {
	                    minDistance:10,
	                    minVelocity:0.3,
	                    axis: "x"
	                }
	            });
	 
	            scrollView.plug(Y.Plugin.ScrollViewPaginator, {
	                selector: 'li'
	            });
	            scrollView.get('boundingBox').setStyles({
	            	'margin':'auto',
	            	'background-color': 'white'
	            });	 
	            scrollView.get('boundingBox').scrollView = scrollView; // Y.one('#scrollview');
	            scrollView.render();

	            
	            var content = scrollView.get("contentBox");
	            
	            content.delegate("click", function(e) {
	                // For mouse based devices, we need to make sure the click isn't fired
	                // at the end of a drag/flick. We're use 2 as an arbitrary threshold.
	                if (Math.abs(scrollView.lastScrolledAmt) < 2) {
	//                	scrollView.set('width', scrollView.get('width'));
	                }
	            }, "img");
	 
	            // Prevent default image drag behavior
	            content.delegate("mousedown", function(e) {
	                e.preventDefault();
	            }, "img");
	 
	
	            try {
		            Y.one('#scrollview-next').on('click', Y.bind(scrollView.pages.next, scrollView.pages));
		            Y.one('#scrollview-prev').on('click', Y.bind(scrollView.pages.prev, scrollView.pages));
	            } catch(e) {}
				var check;
				
	            content.delegate("load", function(e) {
	                console.log('img onload');
	            }, "img");
				
	            scrollView.pages.after('indexChange', function(e) {
	            	var n = ul.all('img').item(e.newVal);
	            	var w = n.get('clientWidth');
	            	if (w) {
		    			this.set('width', w+26);
		            	console.log('scrollView.pages.indexChange: setting scrollView width for i='+e.newVal);
	            	}
	            }, scrollView, Y);
	            scrollView.pages.set('index', index);
			} else {
				for (var i in indexedPhotos) {
					var img = indexedPhotos[i].img;
					if (img == target) {
						index = parseInt(i);
						scrollViewNode.scrollView.pages.set('index', index);
						break;
					}
				}
				
			}
            			
            var check;
		};

		this.handleLightboxClick = function(e) {
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
		};

		/*
		 * Called on centerbox close , called by both close button & overlay
		 * click
		 */
		this.closeLightBox = function(e) {
			var Y = SNAPPI.PM.Y;
			if (this.container.one("#centerbox").hasClass('hide'))
				return;

			var _container = this.container;
			this.animateOpacity(0, this.cfg.animation.overlayDuration,
					_container.one("#centerbox"), 
					function() {
						// hide #glass after animation complete
//						_container.one("#centerbox").setStyles( {
//							width : this.cfg.layout.centerBoxW + 'px',
//							height : this.cfg.layout.centerBoxH + 'px'
//						})
						_container.one("#centerbox").addClass('hide');
						try {
						_container.one("#lightBoxPhoto").set("src", "");
						} catch (e){}
						_container.one("#glass").addClass('hide');
				});
		};

		this.showPhoto = function(photo, animate) {
			var Y = SNAPPI.PM.Y;
			var src = _stripCrop(photo.img.get('src'));
			var isCached = _isSrcCached(src);
			var animation = this.cfg.animation;
			var lightBoxPhoto = this.container.one("#lightBoxPhoto");
			lightBoxPhoto.set('src', src);
			if (isCached) {
				// immediately available, load photo from browser cache
				var dim = _getNaturalDim(lightBoxPhoto, animation);
				var delta, centerbox = this.container.one("#centerbox");
				delta = Math.abs(centerbox.get('clientWidth') - dim.W)
						+ Math.abs(centerbox.get('clientHeight') - dim.H);
				if (animate || delta >= 50) {
					lightBoxPhoto.setStyle('opacity', 0.5);
					this.animateBox.call(this, dim.W, dim.H, 1000);
				} else {
					centerbox.setStyles( {
						width : dim.W,
						height : dim.H
					});
					this.container.one("#closeBox").removeClass('hidden');
				}
			} else {
				// async load img from server
				lightBoxPhoto.setStyle('opacity', 0.5);
				var detach = lightBoxPhoto.on('load', function(e, lightBoxPhoto, animation,
						Y) {
					detach.detach();
					var dim = _getNaturalDim(lightBoxPhoto, animation);
					var delta, centerbox = this.container.one("#centerbox");
					delta = Math.abs(centerbox.get('clientWidth') - dim.W)
							+ Math.abs(centerbox.get('clientHeight') - dim.H);
					this.animateBox.call(this, dim.W, dim.H, delta);
				}, this, lightBoxPhoto, this.cfg.animation, Y);					
			}
		};

		this.nextPhotoClick = function() {
			var Y = SNAPPI.PM.Y;
			var page = 0, top = 1, left = 2;
			if (_curPhotoIndex + 1 == indexedPhotos.length)
				return;
			var thisPhoto = indexedPhotos[_curPhotoIndex];
			var nextPhoto = indexedPhotos[++_curPhotoIndex];
			if (thisPhoto.index[page] != nextPhoto.index[page]) {
				// we are on last IMG of current page
				this.nextPageClick();
			}
			if (_curPhotoIndex == indexedPhotos.length - 1) {
				// at last IMG, disable Next
				this.container.one("#nextPage").addClass('hide');
			} else if (this.container.one("#nextPage").hasClass('hide')) {
				this.container.one("#nextPage").removeClass('hide');
			}
			this.showPhoto(nextPhoto);
		};
		this.prevPhotoClick = function() {
			var Y = SNAPPI.PM.Y;
			var page = 0, top = 1, left = 2;
			if (_curPhotoIndex == 0)
				return;
			var thisPhoto = indexedPhotos[_curPhotoIndex];
			var nextPhoto = indexedPhotos[--_curPhotoIndex];
			if (thisPhoto.index[page] != nextPhoto.index[page]) {
				// we are on first IMG of current page
				this.prevPageClick();
			}
			if (_curPhotoIndex == 0) {
				// at last IMG, disable Prev
				this.container.one("#prevPage").addClass('hide');
			} else if (this.container.one("#prevPage").hasClass('hide')) {
				this.container.one("#prevPage").removeClass('hide');
			}
			this.showPhoto(nextPhoto);
		};

		/*
		 * NOTE: when deployed in "Designer", containerH != containerH
		 */
		this.winResize = function(e) {
			var Y = SNAPPI.PM.Y;
			var containerH = 0 - this.cfg.FOOTER_H, containerW = 0 - this.cfg.MARGIN_W;
			if (this.container.get('tagName') == 'BODY') {
				containerH += this.container.get('winHeight');
				containerW += this.container.get('winWidth');
			} else {
				containerH += _px2i(this.container.getComputedStyle('height'));
				containerW += _px2i(this.container.getComputedStyle('width'));
			}			
			
			var pages = this.content.all('div.pageGallery');
			pages.each(function(page) {
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
		};

		this.scale = function(cfg) {
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

			// scale pages relative to original layout
			page.setStyles( {
				// left, top set by CSS
				width : origRect.W / scale + "px",
				height : origRect.H / scale + "px",
				backgroundColor : 'black'
			});
			if (this.isPreview) {
				// get actual offset AFTER scaled size is set
				if (page.hasClass('hide')) {
					page.addClass('hidden');
					page.removeClass('hide');
					var restoreHide = true;
				}
				// need to get layout info for this page
				offset = {
					X : page.get('offsetLeft'),
					Y : page.get('offsetTop')
				};
				if (restoreHide) {
					page.addClass('hide');
					page.removeClass('hidden');
				}
			}
			// scale photos relative to original layout
			var photos = page.all("img");
			photos.each(function(photo) {
				var scaledRect, origRect = photo.origRect;
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
		};

		/*
		 * animate opacity of the overlay
		 */
		this.animateOpacity = function(opacity, duration, node, onEndCallback) {
			var Y = SNAPPI.PM.Y;
			var animation = new Y.Anim( {
				node : node,
				to : {
					opacity : opacity
				}
			});
			animation.set('duration', duration);
			animation.set('easing', Y.Easing.bounceBoth);
			if (onEndCallback != undefined && onEndCallback instanceof Function) {
				animation.on('end', function(e) {
					onEndCallback.call(this);
				}, this);
			}
			animation.run();
		};

		/*
		 * Animates the center box with enlarged image Accepts element object of
		 * the image to enlarged
		 */
		this.animateBox = function(W, H, delta) {
			var Y = SNAPPI.PM.Y;
			var lightBoxPhoto = this.container.one("#lightBoxPhoto");
			lightBoxPhoto.setStyle('opacity', 0.5);
			var boxAnim = new Y.Anim( {
				node : this.container.one("#centerbox"),
				to : {
					width : W,
					height : H
				}
			});
			var duration = Math.min(delta / 1000, this.cfg.animation.overlayDuration);
			boxAnim.set('easing', Y.Easing.bounceBoth);
			boxAnim.set('duration', duration);
			var detach2 = boxAnim.on('end', function(e) {
				detach2.detach();
				this.container.one("#closeBox").removeClass('hidden');
				this.animateOpacity(1, duration,lightBoxPhoto);
			}, this);
			boxAnim.run();
		};

		/*
		 * Key press functionality of next & previous buttons
		 */
		this.keyAccelerate = function(e) {
			var Y = SNAPPI.PM.Y;
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
		};
	};
	/*
	 * end constructor for Player
	 */

	/*
	 * global variables
	 */
	SNAPPI.PM.Player = Player;

	/*
	 * yui bootstrap
	 */
	SNAPPI.PM.Player.bootstrap = SNAPPI.yuiConfig == undefined;
	if (SNAPPI.PM.Player.bootstrap) {
		/*
		 * yui3 config
		 */
		if (SNAPPI.PM.yuiConfig == undefined) {
			_namespace('SNAPPI.PM.yuiConfig.yui');
			SNAPPI.PM.yuiConfig.yui = { // GLOBAL
				base : "http://yui.yahooapis.com/combo?3.3.0/build/",
				timeout : 10000,
				loadOptional : false,
				combine : true,
				filter : "MIN",
				// filter: "DEBUG",
				allowRollup : true
			};
		}

		YUI(SNAPPI.PM.yuiConfig.yui).use("event-delegate", "node", "anim",
				'scrollview', 'scrollview-paginator',
		/*
		 * yui callback
		 */
		function(Y, result) {
			if (!result.success) {
				Y.log('Load failure: ' + result.msg, 'warn', 'Example');
			} else {
				SNAPPI.PM.Y = Y;
				/*
				 * Y.Node/DOM Element Helper Functions
				 */
				Y.Node.prototype.dom = function() {
					return Y.Node.getDOMNode(this);
				};
				Y.Node.prototype.ynode = function() {
					return this;
				};
				try {
					HTMLElement.prototype.dom = function() {
						return this;
					};
					HTMLElement.prototype.ynode = function() {
						return Y.one(this);
					};
				} catch (e) {
				}

				Y.on("domready", function() {
					var player = new SNAPPI.PM.Player();
					player.init();
					
					/*
					 * experimental
					 */

	                // Prevent default image drag behavior
	    			Y.one("#centerbox").delegate("mousedown", function(e) {
	                    e.preventDefault();
	                }, "img");
				});
			}
		});
	}

})();
