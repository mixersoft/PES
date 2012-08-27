(function(){	
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.Paginator = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.Paginator = Paginator;
	}	
	
	var Paginator = function(){	};
	
	/*
	 * static properties and methods
	 */
	Paginator.listen = {};
	Paginator.find = {};	// keep track of dialog instances for reuse
	

	/**
	 * add Paginator to gallery
	 * - uses A.DelayedTask, A.PluginIO
	 * @param gallery
	 * @return [Paginator | 'delayed'], if created, or False if not created
	 */
	Paginator.paginate_PhotoGallery = function(gallery){
			var g = gallery.node.Gallery;	// gallery
			var target = g.container;
			var NAME = '.gallery.photo';
			var DELAY = 1000;	// delay_task

			var paginateContainer = target.get('parentNode').next('div.paging-numbers');
			if (!paginateContainer) {
				if (target.ancestor('section.gallery')) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers grid_16' />");
					target.get('parentNode').insert(paginateContainer,'after');
				} else {
					return false;	// this is a preview, do NOT auto-create paging DIV
				}
			}
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			}
			
			var controller = SNAPPI.STATE.controller;
			var displayPage = SNAPPI.STATE.displayPage;
			
			var pageCfg = {
					page: displayPage.page,
					total: displayPage.total,  
					maxPageLinks: 10,
					rowsPerPage: displayPage.perpage,
					rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
		firstPageLinkLabel: '&nbsp;',
		prevPageLinkLabel: '&nbsp;',	
		nextPageLinkLabel: '&nbsp;',
		lastPageLinkLabel: '&nbsp;',		
		pageReportLabelTemplate: 'Page {page} of {totalPages}',
		template:  '<div class="wrap"><div class="wrap-rows-per-page">Snaps per Page {RowsPerPageSelect}</div><div class="wrap-page-report">{CurrentPageReport}</div><div class="wrap-paginator-link">{FirstPageLink} {PrevPageLink} {PageLinks} {NextPageLink} {LastPageLink}</div></div>',	
					on: {
						changeRequest: function(e) {
							// this == Paginator
							// var g = gallery.node.Gallery;
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								// console.warn('Page.changeRequest: page='+newState.page);
								Paginator._getPageFromCastingCall(paginateContainer,newState.page, "force");
							} 
							this.setState(newState);
						},
						rowsPerPageChange: function(e){
							if (e.newVal == e.prevVal) return;
							SNAPPI.STATE.displayPage.perpage = parseInt(e.newVal);
							SNAPPI.STATE.displayPage.page = null;
							SNAPPI.io.writeSession({
								'profile.photos.perpage': SNAPPI.STATE.displayPage.perpage,
								'profile.snaps.perpage': SNAPPI.STATE.displayPage.perpage}
							);
						}						
					}
			};
			if (DELAY) {
				var delayed = new _Y.DelayedTask( function() {
					var P = new _Y.Paginator(pageCfg);
					paginateContainer.Paginator = P;
					paginateContainer.Gallery = g;
					paginateContainer.dom().Paginator = P;
					P.target = target;
					Paginator.find[NAME] = P;
					P.render();
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				var p = new _Y.Paginator(pageCfg);
				p.target = target;
				p.container = paginateContainer;
				paginateContainer.Paginator = p;
				
				paginateContainer.Gallery = g;
				paginateContainer.dom().Paginator = p;
				Paginator.find[NAME] = p;
				p.render();			
				return ;
			}
	};

	/**
	 * add aui paginator to Gallery node, 
	 * 		NOTE: tested for Xhr paginate, not .json
	 * 	target is the node which receives the xhr content
	 * 
	 * @params CSS selector to Gallery node, i.e. section.gallery.person
	 */
	Paginator.paginate_CircleMemberGallery = function(selector){
			var target, baseurl, type, NAME = selector;	
			target = _Y.one(NAME);	// XHR response is child of _Y.one(NAME);
			switch(selector) {
				case '.gallery.person': type = 'Members'; break;
				case '.gallery.group': type = 'Circles'; break;
				case '.gallery.collection': type = 'Stories'; break;
			}
				
			var DELAY = 1000;	// delay_task			
			
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			} 
			
			var paginateContainer = target.siblings('div.paging-numbers');
			if (paginateContainer.size() == 0) {
				if (target.test('section.gallery')) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers grid_16' />");
					target.insert(paginateContainer,'after');
				} else {
					return false;	// this is a preview, do NOT auto-create paging DIV
				}
			} else paginateContainer = paginateContainer.item(1);		
			
			var displayPage = SNAPPI.STATE.displayPage;
			var pageCfg = {
					page: displayPage.page,
					total: displayPage.total,  
					maxPageLinks: 10,
					rowsPerPage: displayPage.perpage,
					rowsPerPageOptions: [displayPage.perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
		firstPageLinkLabel: '&nbsp;',
		prevPageLinkLabel: '&nbsp;',	
		nextPageLinkLabel: '&nbsp;',
		lastPageLinkLabel: '&nbsp;',		
		pageReportLabelTemplate: 'Page {page} of {totalPages}',
		template:  '<div class="wrap"><div class="wrap-rows-per-page">'+type+' per Page {RowsPerPageSelect}</div><div class="wrap-page-report">{CurrentPageReport}</div><div class="wrap-paginator-link">{FirstPageLink} {PrevPageLink} {PageLinks} {NextPageLink} {LastPageLink}</div></div>',	
					on: {
						changeRequest: function(e) {
							// this == Paginator
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								Paginator._getPage(paginateContainer, newState.page);
							} 
							this.setState(newState);
						},
						rowsPerPageChange: function(e){
							if (e.newVal == e.prevVal) return;
							SNAPPI.STATE.displayPage.perpage = parseInt(e.newVal);
							SNAPPI.STATE.displayPage.page = null;
						}
					}
			};
			if (DELAY) {
				var delayed = new _Y.DelayedTask( function() {
					paginateContainer.Paginator = new _Y.Paginator(pageCfg).render();
					paginateContainer.dom().Paginator = paginateContainer.Paginator;
					paginateContainer.Paginator.target = target;
					Paginator.find[NAME] = paginateContainer.Paginator;
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				paginateContainer.Paginator = new _Y.Paginator(pageCfg).render();
				paginateContainer.dom().Paginator = paginateContainer.Paginator;
				paginateContainer.Paginator.target = target;
				Paginator.find[NAME] = paginateContainer.Paginator;
				return paginateContainer.Paginator;
			}
	};	
	
	/**
	 * add aui paginator to for Air PhotoUpload , 
	 * @params CSS selector to Gallery node, i.e. section.gallery.person
	 * @params paginateTarget '#gallery-container .gallery.photo .container'
	 */
	Paginator.paginate_PhotoAirUpload = function(paginateTarget, page, perpage, total){
			var g, baseurl, 
				type = 'Snaps',
				NAME = 'PhotoAirUpload';	
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			} 
			g = paginateTarget.ancestor('.gallery');	// XHR response is child of _Y.one(NAME);
			
			// set param defaults
			page = page || paginateTarget.UploadQueue.activePage;
			total = total || paginateTarget.UploadQueue.count_totalItems;
			perpage = perpage || paginateTarget.UploadQueue.perpage;
			
			var paginateContainer = g.siblings('div.paging-numbers');
			if (paginateContainer.size() == 0) {
					// auto-create paging DIV
					paginateContainer = paginateTarget.create("<div class='paging-control paging-numbers grid_16' />");
					g.insert(paginateContainer,'after');
			} else paginateContainer = paginateContainer.shift();	
			var pageCfg = {
					page: page,
					total: total,  
					maxPageLinks: 7,
					rowsPerPage: perpage,
					rowsPerPageOptions: [perpage, 12,24,48,96],
					alwaysVisible: false,
					containers: paginateContainer,
		firstPageLinkLabel: '&nbsp;',
		prevPageLinkLabel: '&nbsp;',	
		nextPageLinkLabel: '&nbsp;',
		lastPageLinkLabel: '&nbsp;',		
		pageReportLabelTemplate: 'Page {page} of {totalPages}',
		template:  '<div class="wrap"><div class="wrap-rows-per-page">'+type+' per Page {RowsPerPageSelect}</div><div class="wrap-page-report">{CurrentPageReport}</div><div class="wrap-paginator-link">{FirstPageLink} {PrevPageLink} {PageLinks} {NextPageLink} {LastPageLink}</div></div>',	
					on: {
						changeRequest: function(e) {
							// this == Paginator
							var newState = e.state;
							var userClicked = newState.before != undefined;
							if (userClicked) {
								Paginator._getPageFromAirDs(paginateContainer, newState.page);
							} 
							this.setState(newState);
						},
						rowsPerPageChange: function(e){
							if (e.newVal == e.prevVal) return;
							SNAPPI.STATE.displayPage.perpage = parseInt(e.newVal);
							SNAPPI.STATE.displayPage.page = null;
							this.target.UploadQueue.flexUploadAPI.setPerpage(SNAPPI.STATE.displayPage.perpage);
						}
					}
			};
			// no delay necessary in Air
			var p = new _Y.Paginator(pageCfg).render();
			p.target = paginateTarget;
			p.container = paginateContainer;
			paginateContainer.Paginator = p;
			Paginator.find[NAME] = p;
			return p;
	};	
	
	/**
	 * get requested page via XHR, NOT JSON
	 * @access private 
	 * @param node, PaginateContainer node, defines node.Paginator
	 * @param pageNumber
	 * @return 
	 */
	Paginator._getPage = function(node, pageNumber){
		var target = node.Paginator.target;  // paginateContainer
		if (pageNumber == SNAPPI.STATE.displayPage.page) return;
		var baseurl = SNAPPI.STATE.controller.here;	
		var nameData = {
			page: pageNumber,
			perpage: node.Paginator.get('rowsPerPage'),
		};
		baseurl = SNAPPI.IO.setNamedParams(baseurl, nameData);
		if (target.io) {
			// already plugged, just reuse
			target.io.set('arguments', nameData)					
			target.io.set('uri', baseurl).start();
		} else {
			// uses pluginIO_RespondAsJson() with Plugin.IO
			target.plug(_Y.Plugin.IO, {
				uri: baseurl ,
				parseContent:true,
				arguments: nameData,
				data: {inner:1},			// paging-inner
				on: {
					success: function(e, id, o , args) {
						SNAPPI.mergeSessionData();
						SNAPPI.STATE.displayPage.page = args.page;
						var check;
					}
				},
				end: null
			});
		}
		return;
	};	
	/**
	 * get requested page FROM gallery.castingCall, (for Photo galleries, only, not member/circle)
	 * 	uses g.loadCastingCall() pattern, 
	 * 
	 * @access private 
	 * @param node, PaginateContainer node, defines node.Paginator, node.Gallery
	 * @param g SNAPPI.Gallery, type = photo
	 * @param pageNumber
	 * @param force boolean, force page refresh
	 * @return 
	 */	
	Paginator._getPageFromCastingCall = function(node, pageNumber, force){
		// context = paginateContainer node
		pageNumber = pageNumber || node.Paginator.get('page') || SNAPPI.STATE.displayPage.page;  
		if (!force && pageNumber == SNAPPI.STATE.displayPage.page) return;
		var g = node.Gallery;
		var cfg = {};
		cfg.page = pageNumber;
		cfg.perpage = node.Paginator.get('rowsPerPage');
		g.refresh(cfg, force);
		return;
	};
	
	/**
	 * get requested page from AIR datasource
	 * @access private 
	 * @param node, PaginatorContainer node, defines node.Paginator
	 * @param pageNumber
	 * @return 
	 */
	Paginator._getPageFromAirDs = function(node, pageNumber){
		var target = node.Paginator.target;  // paginateTarget
		// if (pageNumber == SNAPPI.STATE.displayPage.page) return;
		var nameData = {
			page: pageNumber,
			perpage: node.Paginator.get('rowsPerPage')
		};
		var pluginNode = target.ancestor('.gallery.photo');
		if (!pluginNode.loadingmask) SNAPPI.AIR.Helpers.init_GalleryLoadingMask(pluginNode);
		pluginNode.loadingmask.refreshMask();
		pluginNode.loadingmask.show();
		
		// get new page content
		target.UploadQueue.view_showPage(pageNumber, null, null);
		var delay = new _Y.DelayedTask( 
			function() {
				pluginNode.loadingmask.hide();
			}, this);
		delay.delay(200);   		
		return;
	};	
	
})();
