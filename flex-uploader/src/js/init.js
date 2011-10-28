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
LOG("load BEGIN: init.js");
/****************************
 * DOM/HTML INIT
 */
_domready1 = function(Y) {
	LOG("DOMREADY 1 ************************");
	var Helpers = SNAPPI.AIR.Helpers; 
	Helpers.pageOnLoadComplete();		// show body

    var datasource, uploader; 
    
    /*
     * configure Datasource for uploader
     */
	try {
		if (!flexAPI_Datasource) throw new Exception();
		datasource = new SNAPPI.AIR.CastingCallDataSource();
	} catch (e) {
		LOG(">>>>>>>>>>>>>>>>  USING JAVASCRIPT DATASOURCE  >>>>>>>>>>>>>>>>>>>");
		datasource = _JS_DATASOURCE;
	}   
	SNAPPI.DATASOURCE = datasource;
	
	uploader = new SNAPPI.AIR.UploadQueue({
    	container: Y.one('#gallery-container'),
    	datasource: datasource
    });
    SNAPPI.AIR.uploadQueue = uploader;
    /*
     * DEV/Testing config
     */
	if (0 && 'test') {
		Helpers.DEV_runTestSuite();
	}
	
	// magic login for AIR Test user
	Helpers.DEV_addProviderKeyAsTestUser(datasource.getConfigs().provider_key);
	// add all photos to uploadQueue
//	Helpers.addToUploader(uploader, '');
	// var host = SNAPPI.AIR.host=='dev2.snaphappi.com' ? 'remote' : 'local' ;
	Helpers.DEV_setRuntimeHost(uploader);		// local or remote
	
	// add login menu
	SNAPPI.MenuAUI.initMenus({
		'menu-sign-in-markup':1,
		'menu-uploader-batch-markup':1
	});
	Helpers.initUploadGallery(uploader, 1);
	Helpers.hide_StartupLoadingMask();
LOG(">>>>>>>>> DONE");	


	var detach = SNAPPI.Y.on('snappi-air:begin-import', function(){
		SNAPPI.Y.one('#item-body .import-progress-wrap').removeClass('hide');		
	});
	
	
	
}
    
    
/**************
 * PAGE globals
 */
_domready2 = function(Y) {
	var util = SNAPPI.coreutil;
	SNAPPI.namespace('PAGE');
	PAGE.uploader_openTab = function (tab){	// deprecate
		// check if uploader.isPaused == true
		var btn = SNAPPI.Y.one('#start-btn').set('innerHTML', 'Start');					
		SNAPPI.AIR.uploadQueue.show(tab);
//			SNAPPI.AIR.uploadQueue.view_showPage();
		var check;
	}
	PAGE.toggle_upload = function(el) {
		if (SNAPPI.AIR.Helpers.isAuthorized()) {
			var n = SNAPPI.Y.one(el);
			var state = n.get('innerHTML');
			if (state == 'Pause') {
				n.set('innerHTML', 'Resume Upload');
				SNAPPI.AIR.uploadQueue.action_pause();
			} else {
				n.set('innerHTML', 'Pause');
				SNAPPI.AIR.uploadQueue.action_start();
			}
		} else {
			// show login screen
			SNAPPI.Y.one('#login').removeClass('hide');
		}
	}
}
LOG("load complete: init.js");