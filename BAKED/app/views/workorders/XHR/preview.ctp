<?php 
	$json_castingCall = json_encode($this->viewVars['jsonData']['castingCall']);
?>
		<div class='gallery cf'>
			<div class='container'></div>
		</div>
		<link rel="stylesheet" href="http://snappi1.snaphappi.com/css/gallery/xhr-photo-gallery.css">
		<script type="text/javascript">		
			PAGE = {jsonData: <?php  echo $json_castingCall; ?>};
		</script>
		<script>
			var _Y, CASTING_CALL;
			var yuiConfig = {// GLOBAL
				timeout : 10000,
				loadOptional : false,
				combine : false,
				allowRollup : true,
				filter : "MIN", // ['MIN','DEBUG','RAW'], default='RAW'
				// filter: "DEBUG",
				groups : { 	},
			};
			function bootstrap_SNAPPI() {
				namespace = function() {
					var a = arguments, o = null, i, j, d;
					for( i = 0; i < a.length; i = i + 1) {
						d = a[i].split(".");
						o = window;
						for( j = 0; j < d.length; j = j + 1) {
							o[d[j]] = o[d[j]] || {};
							o = o[d[j]];
						}
					}
					return o;
				};
				// define global namespaces
				namespace('SNAPPI');
				/*
				 * init *GLOBAL* SNAPPI as root for namespace
				 */
				SNAPPI.id = 'SNAPPI';
				SNAPPI.name = 'Snaphappi';
				SNAPPI.namespace = namespace;
				namespace('SNAPPI.STATE');
				SNAPPI.doYready = function(Y) {
					if(SNAPPI.onYready) {
						for(var f in SNAPPI.onYready) {
							// try {
							var fn = SNAPPI.onYready[f];
							delete SNAPPI.onYready[f];
							fn(Y);
							// } catch (e) {
							// }
						}
					}
				}
				SNAPPI.add_ynode = function(Y) {
					Y.substitute = Y.Lang.sub;
					// lightweight version
					console.warn("Node.ynode() may not be compatible with ie8");
					Y = Y || _Y;
					if(Y.Node.prototype.ynode)
						return;
					Y.Node.prototype.dom = function() {
						return Y.Node.getDOMNode(this);
					};
					Y.Node.prototype.ynode = function() {
						return this;
					};
					try {// ie8 incompatibility
						HTMLElement.prototype.dom = function() {
							return this;
						};
						HTMLElement.prototype.ynode = function() {
							return Y.one(this);
						};
					} catch(e) {
					}
				};
			};
			bootstrap_SNAPPI();
		</script>
		<script src="http://yui.yahooapis.com/3.5.1/build/yui/yui-min.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/util-misc.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/sort.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/sortedhash.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/groups3.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/auditions.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/rating.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/thumbnail-helpers.js"></script>
		<script src="http://dev.snaphappi.com/js/snappi/thumbnail3.js"></script>
		<script>

			YUI(yuiConfig).use("jsonp", "node", function(Y) {
				_Y = Y;
				SNAPPI.add_ynode(_Y);
				SNAPPI.doYready(_Y);
				
				CASTING_CALL = PAGE.jsonData.castingCall;
				TestThumbnail.load(CASTING_CALL);
			});
		</script>
		<script>
		/**
		 * 
		 * A simple, but incomplete, class to test the 2 key 
		 * methods of SNAPPI.Thumbnail
		 * 1. new Thumbnail()
		 * 2. Thumbnail.reuse() 
		 * 
		 */
			var TestThumbnail = function(cfg) {
			};
			TestThumbnail.thumbnailCfg = {
					'default' : {
						type : 'Photo', // sample values= [Photo | PhotoPreview | PhotoZoom | ShotGallery ]
						size : 'lm', // sample values= [sq | tn | lm | ll | bp ]
						gallery : "null", 	// unused
					},
					'small' : {
						type : 'Photo', // sample values= [Photo | PhotoPreview | PhotoZoom | ShotGallery ]
						size : 'sq', // sample values= [sq | tn | lm | ll | bp ]
						gallery : "null", 	// unused
					},
					'large' : {
						type : 'Photo', // sample values= [Photo | PhotoPreview | PhotoZoom | ShotGallery ]
						size : 'll', // sample values= [sq | tn | lm | ll | bp ]
						gallery : "null", 	// unused
					},
					'zoom' : {
						type : 'PhotoZoom', // sample values= [Photo | PhotoPreview | PhotoZoom | ShotGallery ]
						// size: 'lm',		// sample values= [sq | tn | lm | ll | bp ]
						gallery : "null", 	// unused
					},
				}

			TestThumbnail.load = function(castingCall) {
				TestThumbnail.parseCC(castingCall);
				var context = {
					container : _Y.one('.gallery .container'),
					_cfg : TestThumbnail.thumbnailCfg['default'],
				}
				SNAPPI.Auditions._auditionSH.each(function(o) {
					TestThumbnail.render.call(context, o, context._cfg);
				});
				TestThumbnail.start_listeners(context);
			}
			TestThumbnail.start_listeners = function (context){
				/*
				 * thumbnail Toolbar:
				 * 	render Thumbnails with different thumbnailCfg
				 * 	reuse thumbnail node/DOM, if possible.
				 */
				if(!TestThumbnail.listen_toolbar) {
					TestThumbnail.listen_toolbar = _Y.on('click', function(n) {
						var cfgLabel = n.currentTarget.get('innerHTML');
						var newCfg = TestThumbnail.thumbnailCfg[cfgLabel.trim()];
						if(this._cfg.type == newCfg.type) {
							this._cfg = newCfg;
							TestThumbnail.reuse(this.container, this._cfg);
						} else {
							this._cfg = newCfg;
							var context = this;
							context.container.setContent();
							SNAPPI.Auditions._auditionSH.each(function(o) {
								TestThumbnail.render.call(context, o, context._cfg);
							});
						}
					}, 'ul.nav.thumbsize > li', context);
				}
				/*
				 * refresh jsonp
				 */
				if(!TestThumbnail.listen_jsonp) {
					TestThumbnail.listen_jsonp = _Y.on('click', function(n) {
						// reset page
						context.container.setContent();
						SNAPPI.Auditions._auditionSH.clear();
						// reload with new jsonp values
						var json_src = _Y.one('.castingCall-src').get('value') + jsonp_suffix;
						_Y.jsonp(json_src, handleJSONP);
					}, 'ul.nav.jsonp > li', context);
				}
			}
			TestThumbnail.parseCC = function(castingCall) {
				var o, aud, Auditions = castingCall['CastingCall']['Auditions']
				var onDuplicate = SNAPPI.Auditions.onDuplicate_ORIGINAL;
				var sh = SNAPPI.Auditions.parseCastingCall(castingCall, null, null, onDuplicate);
			}
			TestThumbnail.render = function(audition, cfg) {
				var audition = SNAPPI.Auditions.get(audition);
				var t = new SNAPPI.Thumbnail(audition, cfg);
				this.container.append(t.node);
			}
			TestThumbnail.reuse = function(container, cfg) {
				container.all('.FigureBox').each(function(n, i, l) {
					if(n.Thumbnail._cfg.type == cfg.type) {
						var audition = SNAPPI.Auditions.find(n.Thumbnail.uuid);
						n.Thumbnail.reuse.call(n.Thumbnail, audition, cfg);
					} else {
						console.error('Error: cannot call Thumbnail.reuse() with different type, type=' + cfg.type);
					}
				}, this);
			}
		</script>		