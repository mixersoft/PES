/**
 * 
 * Copyright (c) 2009-2011, Snaphappi.com. All rights reserved.
 * 
 * This program is free software: you can redistribute it and/or modify it under
 * the terms of the Affero GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
 * later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the Affero GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the Affero GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * 
 * @author Michael Lin, info@snaphappi.com
 * 
 * 
 */
console.log("load BEGIN: helpers.js");	
(function() {
	/***************************************************************************
	 * Helpers Static Class
	 * 	SNAPPI.AIR.Helpers = Helpers;
	 */
	var Helpers = function() {}
	Helpers.prototype = {};
	SNAPPI.AIR.Helpers = Helpers;
	SNAPPI.namespace('SNAPPI.STATE.displayPage');
	
	Helpers.add_snappiHoverEvent = function(Y) {
		/*
		 * add 'snappi:hover' custom event see:
		 * http://developer.yahoo.com/yui/3/examples/event/event-synth-hover.html
		 * TODO: replace with yui3 hover in yui 3.4.0
		 */
		var check = Y.Event.define("snappi:hover", {
			processArgs : function(args) {
				// Args received here match the Y.on(...) order, so
			// [ 'hover', onHover, "#demo p", endHover, context, etc ]
			var endHover = null, selector = null, context = null;
			if (args.length > 3) {
				endHover = args[3];
				args.splice(3, 1);
			}
			if (args.length > 3) {
				context = args[3];
				args.splice(3, 1);
			}
			if (args.length > 3) {
				selector = args[3];
				args.splice(3, 1);
			}

			// This will be stored in the subscription's '_extra' property
			return {
				endHover : endHover,
				context : context,
				selector : selector
			};
		},
		on : function(node, sub, notifier) {
			var onHover = sub.fn;
			var endHover = sub._extra && sub._extra.endHover || null;
			var context = sub._extra && sub._extra.context || null;
			sub.context = context;
			// To make detaching the associated DOM events easy, use a
			// detach category, but keep the category unique per subscription
			// by creating the category with Y.guid()
			sub._evtGuid = Y.guid() + '|';

			node.on(sub._evtGuid + "mouseenter", function(e) {
				// Firing the notifier event executes the hover subscribers
					sub.fn = onHover;
					notifier.fire(e);
				});

			node.on(sub._evtGuid + "mouseleave", function(e) {
				// Firing the notifier event executes the hover subscribers
					sub.fn = endHover;
					notifier.fire(e);
				});
		},
		detach : function(node, sub, notifier) {
			// Detach the mouseenter and mouseout subscriptions using the
			// detach category
			node.detach(sub._evtGuid + '*');
		},
		// add delegate support. it will be used in zoom or other places
			delegate : function(node, sub, notifier, filter) {
				var onHover = sub.fn;
				var selector = sub._extra && sub._extra.selector || null;
				var context = sub._extra && sub._extra.context || null;
				sub._evtGuid = Y.guid() + '|';

				node.delegate(sub._evtGuid + "mouseenter", sub.fn, selector,
						context);

			},

			// Delegate uses a separate detach function to facilitate undoing
			// more
			// complex wiring created in the delegate logic above. Not needed
			// here.
			detachDelegate : function(node, sub, notifier) {
				sub._delegateDetacher.detach();
			}
		});
	}
	
	Helpers.DEV_setRuntimeHost = function(uploader, host) {
		if (!SNAPPI.AIR.host) {
			alert("helpers.js: SNAPPI.AIR.host is not set");
			// should already be set in flex & base.js
			// // deprecate
			// host = host || 'local';
		    // /*
		     // * set upload server
		     // */
			// switch (host) {
			// case 'remote':
				// // upload REMOTE
				// SNAPPI.AIR.host = "dev2.snaphappi.com";
		        // // firefox dev2.snaphappi.com uuid
				// break;
			// case 'local':
			// default:
				// var Y = SNAPPI.Y;
				// SNAPPI.AIR.host =  Y.one('base').getAttribute('host');
				// break;
			// }			
		}

	    var uploadHost = {
	    		local: "http://"+SNAPPI.AIR.host+"/my/upload",
	    		remote: "http://dev2.snaphappi.com/my/upload",
	    };		
		// login
		var login_Url = "http://" + SNAPPI.AIR.host + "/users/login/.json?optional=1";
		SNAPPI.AIR.XhrHelper.login_Url = login_Url;
		LOG("login_Url > "+login_Url);
		
		// upload
		var upload_Url = "http://"+SNAPPI.AIR.host+"/my/upload";
		LOG("upload_Url > "+upload_Url);
		uploader.flexUploadAPI.setUploadFilePOSTurl(upload_Url);
		try {
			LOG(">>> UPLOAD HOST="+flexAPI_Datasource.cfg.uploadHost);
			LOG(">>> UPLOAD HOST="+flexAPI_UI.datasource.cfg.uploadHost);
		} catch (e) {}
	}
	

	Helpers.isAuthorized = function() {
		try {
			var userid = SNAPPI.STATE.user.id;
			// TODO: post uuid to server to validate
			return userid;
		} catch (e) {
		}
		return false;
	}	
	
	Helpers.pageOnLoadComplete = function() {
		SNAPPI.Y.one('body').setAttribute('style','');
	}
	
	/*
	 * add provider_key to magic login for testing, DEV only
	 */
	// TODO: providerKey !== provider_account_key/id, right???
	Helpers.DEV_addProviderKeyAsTestUser = function(uuid) {
		var option = SNAPPI.Y.one('form#UserLoginForm select > option.hr');
		option.insert("<option value='" + uuid
				+ "'>AIR upload test user</option>", 'after');
	}
	
	Helpers.DEV_runTestSuite = function(){
		var Y = SNAPPI.Y;
	    try {	
			LOG("TEST: get new SNAPPI.CastingCallDataSource()");
			var datasource = new SNAPPI.AIR.CastingCallDataSource();
			var test = SNAPPI.AIR.Test;
			LOG("start Test suite");
			test.init(datasource, dsCfg);
			test.go();
			
	        
	        /*
	         * test snappi:hover
	         */
	       	var startDrop = function() {
	    		LOG("hover start");
	    	};
	    	var stopDrop = function() {
	    		LOG("hover stop");
			}; 
	    	Y.one('#upload').on('snappi:hover', startDrop, stopDrop, this);	
	  	} 
	    catch (e) {
	        LOG("SNAPPI.AIR.Test not available");
	    } 		
	}
	
	/**
	 * Automatically adds imported photos to upload queue
	 *	@params uploader 
	 *	@params folderpath string OPTIONAL 
	 */
	Helpers.DEV_importFolder = function(uploader, folderpath){
		var datasource = uploader.ds;
		folderpath = folderpath || 'C:\\USERS\\michael\\Pictures\\importTest';
	    /*******************************
	     * 
	     *  import photos
	     */
		var importPhoto_callback = {
	        success: function(o, baseurl){
	            LOG("******************   AFTER IMPORT PHOTOS **************************");
	            LOG("baseurl="+baseurl);
	            datasource.setConfig({
	                baseurl: baseurl
	            });
//	            var count = Helpers.addToUploader(uploader, baseurl);
	        }, 
			failure: function(o) {
				alert('addPhotos failure');
				LOG(o);
			},
			progress: function(){
				var progress = datasource.getImportProgress();
				LOG(progress);
//	    									if (progress.scanned > 5) datasource.cancelImport();
			}
	    }
		datasource.importPhotos(folderpath, importPhoto_callback);		            
	}
	/**
	 * add imported photos (by baseurl) to uploadQueue, creates a new batchid
	 *	@params uploader OPTIONAL, uses global SNAPPI.AIR.UploadQueue
	 *	@params baseurl string OPTIONAL, add all baseurls if null 
	 *	@return int - count of photos added
	 */	
	Helpers.addToUploader = function(uploader, baseurl){
//  baseurl = baseurl || 'C:\\USERS\\michael\\Pictures\\importTest';
		uploader = uploader || SNAPPI.AIR.uploadQueue;
        var query = {	
        		page: 1,
                perpage: 1999,
                baseurl: baseurl  
            };
        var added = 0;
        LOG("Helpers.addToUploader  ****************************************************");
        var datasource = uploader.ds;
        datasource.getAuditions_all(query, function (auditions) {
        	LOG("datasource.getAuditions_all");
            //before adding photos to upload queue set batch_id first
            added = uploader.flexUploadAPI.addToUploadQueue(auditions);
            var batchId = uploader.flexUploadAPI.getLastOpenBatchId();
            LOG("added to uploadQueue, count="+added+", open batchId="+batchId);			                
            uploader.show("reload");
        }, datasource, false);
        Helpers.set_Filter_FolderSelect();
        return added;
    }
    Helpers.show_login = function() {
    	var Y = SNAPPI.Y;
    	// SNAPPI.Helper.Dialog.showLogin();
    	if (SNAPPI.AIR.debug && Y.one('#login select.postData')) {
    		Y.one('#login select.postData').removeClass('hide');
    	}
    }
    /*
     * initialize Uploader for the first time
     * @params page int, page number, defaults to SNAPPI.STATE.displayPage.perpage
     * @params uploadQueue SNAPPI.AIR.UploadQueue, same as SNAPPI.AIR.uploadQueue
     */
	Helpers.initUploadGallery = function(uploadQueue, page, perpage, batchId, folder, filter) {
LOG("Helpers.initUploadGallery, BATCHID="+batchId+", folder="+folder+", page="+page);		
		uploadQueue = uploadQueue || SNAPPI.AIR.uploadQueue;
		page = page || 1;
		perpage = perpage || SNAPPI.STATE.displayPage.perpage || 24;
		SNAPPI.STATE.displayPage.page = page;
		SNAPPI.STATE.displayPage.perpage = perpage;
		
		// init/show upload queue
		var Y = SNAPPI.Y;
		
		
		// check .filter for current focus
		if (filter) {
			// set filter focus
			var filterBtns = uploadQueue.container.all('.gallery-display-options .settings .filter li.btn');
			filterBtns.each(function(n,i,l){
				var action = n.getAttribute('action').split(':').pop();
				if (action == filter) n.addClass('focus'); 
				else n.removeClass('focus');
			});
		}
		var hasFocus = uploadQueue.container.one('.gallery-display-options .settings .filter li.btn.focus');
		if (hasFocus) {
			filter = hasFocus.getAttribute('action').split(':').pop();
		} else filter = 'pending';	// Ready for upload
		
		uploadQueue.initQueue(filter, {
			batchId: batchId,
			folder: folder, 				// folder='all' => baseurl=''
			perpage: perpage,
			page: page,
		});
		// show initial page using Paginator
		
		var node = Y.one('#gallery-container .gallery.photo');
		// init gallery listeners
		Helpers.init_GalleryLoadingMask(node);
		var p = SNAPPI.Paginator.find['PhotoAirUpload'];
		if (!p) {
			var paginateTarget = Y.one('#gallery-container .gallery.photo .container');
			paginateTarget.UploadQueue = uploadQueue;
			p = SNAPPI.Paginator.paginate_PhotoAirUpload(paginateTarget, page, SNAPPI.STATE.displayPage.perpage, uploadQueue.count_totalItems);
		}
		SNAPPI.Paginator._getPageFromAirDs(p.container, page);
		// other init steps
		// Helpers.set_Filter_FolderSelect();
// c = paginateTarget;
// LOG("CHECK MULTISELECT LISTEN")
// LOG(c);
	}
	
	/*
	 * use HTML5 startup loading mask to show before JS is ready
	 */
	Helpers.hide_StartupLoadingMask = function(){
		var Y = SNAPPI.Y;
		var mask = Y.one('#startup-loading-mask');
		if (mask) {
			mask.get('parentNode').append(mask.one('*'));
			mask.empty().destroy();
		}
	}
	
	
	Helpers.init_GalleryLoadingMask = function(pluginNode, target){
		var Y = SNAPPI.Y;
		// show initial page using Paginator, doesn't work
		pluginNode = pluginNode || Y.one('#gallery-container .gallery.photo');
		target = target || pluginNode;
		if (target.ancestor('.gallery.photo')) target = target.ancestor('.gallery.photo');
		
		if (!pluginNode.loadingmask) {				// add loadingmask ASAP
			var loadingmaskTarget = target;
			// set loadingmask to parent
			pluginNode.plug(Y.LoadingMask, {
				pluginNode: loadingmaskTarget
			});    			
			pluginNode.loadingmask._conf.data.value['pluginNode'] = loadingmaskTarget;
			pluginNode.loadingmask.overlayMask._conf.data.value['pluginNode'] = pluginNode.loadingmask._conf.data.value['pluginNode'];
			// pluginNode.loadingmask.set('pluginNode', pluginNode);
			// pluginNode.loadingmask.overlayMask.set('pluginNode', pluginNode);
			pluginNode.loadingmask.set('zIndex', 10);
			pluginNode.loadingmask.overlayMask.set('zIndex', 10);
			pluginNode.loadingmask.show();
		} else {
			pluginNode.loadingmask.refreshMask();
			pluginNode.loadingmask.show();
		}		
		return pluginNode;
	}
	
}());


// LOG("load complete: helpers.js");	