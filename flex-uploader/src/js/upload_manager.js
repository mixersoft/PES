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
(function() {
	var util = SNAPPI.coreutil;
	/*
	 * callback object for managing all async uploads
	 */
	var UploadManager = function(uploadQueue, row, progress) {
		this.flexUploadObj = null;
		if (!UploadManager.MAX_CONCURRENT_UPLOADS < 2) {
			UploadManager.MAX_CONCURRENT_UPLOADS = _flexAPI_UI.datasource.cfg.MAX_CONCURRENT_UPLOADS;
		}
		/*
		 * create progress object for rendering upload progress
		 *  - common interface, multiple skins
		 * progress interface methods:
		 * 		.percentComplete(int)
		 * 		.complete
		 * 		.error
		 * 		.start
		 * 
		 */
		this.row = row;
		this.progress = progress;
		this.uploadQueue = uploadQueue;
		this.hashcode = function(){
//			return this.progress.progressContainer.get('id');
			return this.row.photo_id;
		}
		UploadManager.add(this); // move to domready		
	};
	/*
	 * static methods
	 */
	UploadManager.MAX_CONCURRENT_UPLOADS = 4;
	UploadManager.activeSH = new SNAPPI.SortedHash(); //init new SNAPPI.SortedHash() later, not on script load
	UploadManager.add = function(o){
		return UploadManager.activeSH.add(o);
	}
	UploadManager.get = function(o){
		return UploadManager.activeSH.get(o);
	}
	UploadManager.remove = function(o){
		return UploadManager.activeSH.remove(o);
	}
	UploadManager.count = function(){
		return UploadManager.activeSH.count();
	}
	
	
	UploadManager.prototype = {
		setProgress : function (o) {
			this.progress = o;
		},
		/** checked
		 * when upload starts for a file it is executed to tell that upload
		 * start now do whatever you want to do when upload just starting
		 * @params flex object - Flex UploadFile.as 
		 */
		uploadStart_Callback : function(flex) {
			var uploader = this.uploadQueue;
			this.flexUploadObj = flex; // needed to cancel
			try {
				this.progress.showCancelBtn(true);
				uploader.view_setUploadTotalProgress();
			} catch (ex) {
			}
		},
		/** checked
		 * when any error occured in upload process then it is fired do whatever
		 * you want to do when upload error comes
		 * 
		 * @params f object - Flex:UploadFile.file
		 * @params msg string - error msg
		 */
		uploadError_Callback : function(f, msg) {
			var uploader = this.uploadQueue;
			try {
//				var row = uploader.uploadRows[uploader.uploadItemIndex];
				var row = this.row;
				if (msg == "File Upload Cancelled.") {
					this.progress.setCancelled(msg);
					_flexAPI_UI.datasource.setUploadStatus(row.photo_id, 'cancelled', uploader.batch);
//					uploader.uploadRows[uploader.uploadItemIndex].status = 'cancelled';
					this.row.status = 'cancelled';
				} else {
					this.progress.setAlert("Warning: upload failed. " + msg);
					_flexAPI_UI.datasource.setUploadStatus(row.photo_id, 'error', uploader.batch);
//					uploader.uploadRows[uploader.uploadItemIndex].status = 'error';
					this.row.status = 'error';
				};
			} catch (ex) {
			}
			uploader.view_setUploadTotalProgress(1);
			if (!uploader.isCancelling) {
				// do next
				uploader.doUpload();
			}
		},
		/** checked
		 * it tells how much bytes sent by upload process and remaining ones,
		 * here we can run our progress bar based one sent bytes and total
		 * bytes.
		 * 
		 * @params f object - Flex:UploadFile.file
		 * @params bytesLoaded	int
		 * @params bytesTotal int
		 */
		uploadProgress_Callback : function(f, bytesLoaded, bytesTotal) {
//			var uploader = this.uploadQueue;
			try {
				var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
				this.progress.setProgress(percent, "Uploading...");
			} catch (ex) {
				LOG("EXCEPTION uploadProgress_Callback");
			}
		},
		/** checked
		 * when upload success and server gives response then it is called it
		 * contains f object reference and server response as string and
		 * responseRecieved flag true/false
		 * 
		 * @params f object - Flex:UploadFile.file
		 * @serverData
		 * @responseReceived 
		 */
		uploadSuccess_Callback : function(f, serverData, responseReceived) {
//			LOG("uploadSuccess_Callback");			
			var uploader = this.uploadQueue;
			try {
				this.progress.setComplete();
				this.progress.setStatus("Upload Complete.");
			} catch (ex) {
			}
			try { // save upload status to DB

//				var row = uploader.uploadRows[uploader.uploadItemIndex];
//				_flexAPI_UI.datasource.setUploadStatus(row.photo_id, 'done', uploader.batch);
//				uploader.uploadRows[uploader.uploadItemIndex].status = 'done';
				
				var row = this.row;
				_flexAPI_UI.datasource.setUploadStatus(row.photo_id, 'done', uploader.batch);
				row.status = 'done';
				uploader.view_setUploadTotalProgress(1);
				uploader.ds.updatePhotoProperties( {
					id : row.photo_id,
					upload_status : 1
				})
				if (!uploader.isCancelling) {
					uploader.doUpload();
				}
			} catch (ex) {
			}
		},
		/*
		 * at the end upload object fires the uploadComplete method here we can
		 * manage our disable or enable buttons
		 */
		uploadComplete_Callback : function(f) {
//LOG("uploadComplete_Callback");			
			var uploader = this.uploadQueue;
			try {
				UploadManager.remove(this);	// already removed?
			} catch (e) {
			}
		},
		/*
		 * cancel in progress item
		 */
		cancel : function() {
			var uploader = this.uploadQueue;
			if (this.flexUploadObj) {
				this.flexUploadObj.cancel();
				this.flexUploadObj = null;
			}
		}
		
	}
	SNAPPI.AIR.UploadManager = UploadManager;
	
	LOG("load complete: uploadmanager.js");	
}());
