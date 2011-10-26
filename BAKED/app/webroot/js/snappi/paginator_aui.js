(function(){	
	var Paginator = function(){
		if (Paginator.doClassInit) Paginator.classInit();
	};
	Paginator.prototype = {};
	
	/*
	 * static properties and methods
	 */
	Paginator.doClassInit = true;
	Paginator.listen = {};
	Paginator.find = {};	// keep track of dialog instances for reuse
	
	Paginator.classInit = function() {
		var Y = SNAPPI.Y;
		Paginator.doClassInit = false;
	};

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
			
			var Y = SNAPPI.Y;
			var controller = SNAPPI.STATE.controller;
			var displayPage = SNAPPI.STATE.displayPage;
			
			var pageCfg = {
					page: displayPage.page,
					total: displayPage.total,  
					maxPageLinks: 9,
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
								Paginator._getPageFromCastingCall(paginateContainer,newState.page);
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
				var delayed = new Y.DelayedTask( function() {
					var P = new Y.Paginator(pageCfg);
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
				var P = new Y.Paginator(pageCfg);
				paginateContainer.Paginator = P;
				paginateContainer.Gallery = g;
				paginateContainer.dom().Paginator = P;
				P.target = target;
				Paginator.find[NAME] = P;
				P.render();			
				return P;
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
			var Y = SNAPPI.Y;
			var target, baseurl, NAME = selector;	
			target = Y.one(NAME);	// XHR response is child of Y.one(NAME);
			var type = /person/.test(selector) ? 'Members' : 'Circles';
				
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
				var delayed = new Y.DelayedTask( function() {
					paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
					paginateContainer.dom().Paginator = paginateContainer.Paginator;
					paginateContainer.Paginator.target = target;
					Paginator.find[NAME] = paginateContainer.Paginator;
				}, this);
				delayed.delay(DELAY);
				return "delayed";
			} else {
				paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
				paginateContainer.dom().Paginator = paginateContainer.Paginator;
				paginateContainer.Paginator.target = target;
				Paginator.find[NAME] = paginateContainer.Paginator;
				return paginateContainer.Paginator;
			}
	};	
	
	/**
	 * add aui paginator to for Air PhotoUpload , 
	 * @params CSS selector to Gallery node, i.e. section.gallery.person
	 */
	Paginator.paginate_PhotoAirUpload = function(node, page, perpage, total){
			var Y = SNAPPI.Y;
			var target, baseurl, 
				type = 'Snaps',
				NAME = node.get('id') || node._yuid;	
			target = node;	// XHR response is child of Y.one(NAME);
				
			if (Paginator.find[NAME]) {
				// already created, just reuse
				return Paginator.find[NAME];
			} 
			
			// set param defaults
			page = page || node.UploadQueue.activePage;
			total = total || node.UploadQueue.count_totalItems;
			perpage = perpage || node.UploadQueue.perpage;
			
			var paginateContainer = target.siblings('div.paging-numbers');
			if (paginateContainer.size() == 0) {
					// auto-create paging DIV
					paginateContainer = target.create("<div class='paging-control paging-numbers grid_16' />");
					target.insert(paginateContainer,'after');
			} else paginateContainer = paginateContainer.shift();	
			
			var pageCfg = {
					page: page,
					total: total,  
					maxPageLinks: 10,
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
						}
					}
			};
			// no delay necessary in Air
			paginateContainer.Paginator = new Y.Paginator(pageCfg).render();
			paginateContainer.dom().Paginator = paginateContainer.Paginator;
			paginateContainer.Paginator.target = target;
			Paginator.find[NAME] = paginateContainer.Paginator;
			return paginateContainer.Paginator;
	};	
	
	/**
	 * get requested page via XHR, NOT JSON
	 * @access private 
	 * @param node, PaginateContainer node, defines node.Paginator
	 * @param pageNumber
	 * @return 
	 */
	Paginator._getPage = function(node, pageNumber){
		var Y = SNAPPI.Y;
		var target = node.Paginator.target;  // paginateContainer
		if (pageNumber == SNAPPI.STATE.displayPage.page) return;
		var baseurl = SNAPPI.STATE.controller.here;	
		var nameData = {
			page: pageNumber,
			perpage: node.Paginator.get('rowsPerPage')
		};
		baseurl = SNAPPI.IO.setNamedParams(baseurl, nameData);
		if (target.io) {
			// already plugged, just reuse
			target.io.set('arguments', nameData)					
			target.io.set('uri', baseurl).start();
		} else {
			// uses pluginIO_RespondAsJson() with Plugin.IO
			target.plug(Y.Plugin.IO, {
				uri: baseurl ,
				parseContent:true,
				arguments: nameData,
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
	 * @return 
	 */	
	Paginator._getPageFromCastingCall = function(node, pageNumber){
		// context = paginateContainer node
		if (pageNumber == SNAPPI.STATE.displayPage.page) return;
		var g = node.Gallery;
		var uri = g.castingCall.CastingCall.Request+ "/.json";
		var nameData = {
			page: pageNumber,
			perpage: node.Paginator.get('rowsPerPage')
		};
		uri = SNAPPI.IO.setNamedParams(uri, nameData);
    	
		// render PhotoGallery
		var cfg = {
			args: {page: pageNumber},
			replace: true,
    		successJson : function(e, i,o,args) {
				var response = o.responseJson.response;
				SNAPPI.mergeSessionData();	// need this for paginate
				var options = {
					page: args.page,
                	castingCall: response.castingCall,
                }
                this.render(options);
                PAGE.jsonData.castingCall = response.castingCall;
                return false;								
			},        			
			ioCfg : {
				parseContent: true,
				dataType: 'json',
				context: g,	// test        				
			}
		};
		g.loadCastingCall(uri, cfg);
		
		return;
	};
	
	/**
	 * get requested page from AIR datasource
	 * @access private 
	 * @param node, PaginateContainer node, defines node.Paginator
	 * @param pageNumber
	 * @return 
	 */
	Paginator._getPageFromAirDs = function(node, pageNumber){
		var Y = SNAPPI.Y;
		var target = node.Paginator.target;  // paginateContainer
		// if (pageNumber == SNAPPI.STATE.displayPage.page) return;
		var nameData = {
			page: pageNumber,
			perpage: node.Paginator.get('rowsPerPage')
		};
		if (!target.loadingmask) {
			var loadingmaskTarget = target;
			// set loadingmask to parent
			target.plug(Y.LoadingMask, {
				target: loadingmaskTarget
			});    			
			target.loadingmask._conf.data.value['target'] = loadingmaskTarget;
			target.loadingmask.overlayMask._conf.data.value['target'] = target.loadingmask._conf.data.value['target'];
			// target.loadingmask.set('target', target);
			// target.loadingmask.overlayMask.set('target', target);
			target.loadingmask.set('zIndex', 10);
			target.loadingmask.overlayMask.set('zIndex', 10);
		}
		target.loadingmask.refreshMask();
		target.loadingmask.show();
		// get new page content
		target.UploadQueue.view_showPage(pageNumber, null, null);
		
		target.loadingmask.hide();
		return;
	};	
	
	
	SNAPPI.Paginator = Paginator;
})();
