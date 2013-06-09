/**
 *
 * Copyright (c) 2009-2013, Snaphappi.com. All rights reserved.
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
// Initialize the widget when the DOM is ready

$(function() {
	var CFG = CFG || {}; 
	/*
	 * helper functions
	 */
	var Util = new function(){}
	Util.isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
	Util.get_DeviceID = function (ruid) {
		/*
		 * DeviceID priority 
		 * 	PAGE.jsonData.DeviceID
		 *  Cookie('DeviceID)
		 *  guid()
		 * 
		 * TODO: alternate strategy, POST folders and see if we can match with existing nativePath
		 */
		try {
			var deviceId = Util.DeviceID || PAGE.jsonData.DeviceID,
				cookieDeviceID = $.cookie("DeviceID");
			if (!deviceId) deviceId = cookieDeviceID;
			if (!deviceId) deviceId = Util.guid();
		} catch (ex) { 
			deviceId = Util.guid();
		}
		if (deviceId !== cookieDeviceID) {
			$.cookie.defaults = {expires : 999};
			$.cookie("DeviceID", deviceId);
		}
		Util.DeviceID = deviceId;
		return Util.DeviceID;
	}
	Util.guid = function(){
		function s4() {
		  return Math.floor((1 + Math.random()) * 0x10000)
		             .toString(16)
		             .substring(1);
		};
		 return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
	}
	Util.resize = function(px){
		px = px || 640;
		if (Util.isChrome) {
			return  {
				width : px, 
				height : px, 
				quality : 90,
				crop: false, 				// true=crop to exact dimensions, false=max dim
				preserve_headers: true,		// BUT do NOT update EXIF size after resize
			}
		} else return false;
	}
	Util.confirmNoChrome = function(target){
		$('.plupload_droptext').html($('#markup-uploader .confirm-not-chrome'));
		$('.plupload_droptext label.plupload_button').one('click', function(e){
			Util.allowNoChrome()
		});
        return false;
	},
	Util.allowNoChrome = function(e){
		$('.plupload_droptext').html('');
	}
	
	Util.activate_dragover = function(target, cfg){
		cfg = cfg || {dragover:'dragover'};
		$.fn.dndhover = function(options) {
			// from http://stackoverflow.com/questions/10867506/dragleave-of-parent-element-fires-when-dragging-over-children-elements
		    return this.each(function() {
		
		        var self = $(this);
		        var collection = $();
		
		        self.on('dragenter', function(event) {
		            if (collection.size() === 0) {
		                self.trigger('dndHoverStart');
		            }
		            collection = collection.add(event.target);
		        });
		
		        self.on('dragleave', function(event) {
		            /*
		             * Firefox 3.6 fires the dragleave event on the previous element
		             * before firing dragenter on the next one so we introduce a delay
		             */
		            setTimeout(function() {
		                collection = collection.not(event.target);
		                if (collection.size() === 0) {
		                    self.trigger('dndHoverEnd');
		                }
		            }, 1);
		        });
		    });
		};
				
		target.dndhover().on({
		    'dndHoverStart': function(event) {
		
		        target.addClass(cfg.dragover);
		
		        event.stopPropagation();
		        event.preventDefault();
		        return false;
		    },
		    'dndHoverEnd': function(event) {
		
		        target.removeClass(cfg.dragover);
		
		        event.stopPropagation();
		        event.preventDefault();
		        return false;
		    }
		});
	}
	// make global,
	// TODO: this is being overwritten by AUI
	CFG['util'] = $.extend(CFG['util'] || {}, Util);		
	
	$("#uploader").plupload({
		// General settings
		runtimes : 'html5,flash,silverlight',
		url : '../upload.php',

		// Select multiple files at once
		multi_selection: true, // if set to true, html4 will always fail, since it can't select multiple files

		// Maximum file size
		max_file_size : '1000mb',

		// User can upload no more then 20 files in one go (sets multiple_queues to false)
		max_file_count: 9999,
		
		chunks : {
			size: '1mb',
			send_chunk_number: false // set this to true, to send chunk and total chunk numbers instead of offset and total bytes
		},

		// Resize images on clientside in Chrome, or FALSE for other browsers
		resize : Util.resize(),
	
		// Specify what files to browse for
		filters : [
			{title : "Image files", extensions : "jpg"},
			// {title : "Image files", extensions : "jpg,gif,png"},
			// {title : "Zip files", extensions : "zip,avi"}
		],

		// Rename files by clicking on their titles
		rename: true,
		
		// Sort files
		sortable: true,

		// Enable ability to drag'n'drop files onto the widget (currently only HTML5 supports that)
		dragdrop: true,

		// Views to activate
		views: {
			list: true,
			thumbs: true, // Show thumbs
			active: 'list',
			remember: false,
		},

		// Flash settings
		flash_swf_url : '../../js/Moxie.swf',

		// Silverlight settings
		silverlight_xap_url : '../../js/Moxie.xap',
		
		
		
		/*
		 * snaphappi customizations
		 */
		multiple_queues : true,
		unique_names: true,
		prevent_duplicates: true,
		autostart: false,
		max_retries: 0,
		multipart: true,
		multipart_params: {},
		preinit: {
			Init: function(){
				var msg = (Util.isChrome) 
					? $('#markup-uploader .is-chrome')
					: $('#markup-uploader .not-chrome'); 
				$('.plupload_droptext').html(msg);
			},
			PostInit: function(up, params) {	
				if (up.features.dragdrop && up.settings.dragdrop) {
					var target = $('#uploader_dropbox');
					Util.activate_dragover(target);
					target.one('drop', function(event){
						target.trigger('dndHoverEnd');
						if (Util.isChrome) $('.plupload_droptext').addClass('hide');
						else if ($('#markup-uploader .confim-not-chrome').length==1) {
							var check; // ok to drop
						} else {
							event.stopPropagation();
	       					event.preventDefault();
							Util.confirmNoChrome(target);
							return false;
						}
					});
				}
				$('form#form').removeClass('hide');
				Util.get_DeviceID();
			},
			FilesAdded: function(up, files) {
				var root;
				for (var i in files) {
					if (files[i].relativePath) {
						files[i].fileName = files[i].name;
						files[i].name = files[i].relativePath;
						root = files[i].relativePath.split('/');
						if (root.length > 2 ) files[i].root = '/'+root[1];				
						else files[i].root = '';
					}
					try {
						// TODO: why can I see this value in firebug???
						var fullpath = files[i].getNative()['mozFullPath'];
						if (fullpath) files[i].fullpath = fullpath; 
					} catch (ex) {
					}
				}
				// remove duplicate files using settings.prevent_duplicates = true
			},
			QueueChanged: function(up){
				// refresh the UI widget on 5 sec delay
				// remove duplicates from UI widget view
				var check;
			},
			BeforeUpload: function(up, file) {
				var root, session, 
					utc_now = Math.floor(new Date().getTime()/1000);
				if (!( CFG['session'] && CFG['session'].BatchId) && typeof ($.cookie) != 'undefined') {
					$.cookie.json = true;
					$.cookie.defaults = {expires : 1};		// BatchId good for 1 day
					session = $.cookie("plupload");
					if (!session || !session.BatchId ) {
					 	session = $.extend(session || {}, {'BatchId': utc_now});
					} 
					$.cookie("plupload", session);
					CFG = $.extend(CFG, {session: session});
				} 
				if (CFG['session'] && CFG['session'].LastUpload) {
					 if ( CFG['session'].LastUpload - CFG['session'].BatchId > 4*60*60 ){
						// use cookie expiration to expire BatchId, instead
						// session = $.extend(session || {}, {'BatchId': utc_now});
					}
				}
				CFG['session'].LastUpload = utc_now;
			},
			UploadFile: function(up, file) {
				// up.settings.url = '../dump.php?id=' + file.id;
				up.settings.url = '/my/plupload?name=' + (file.fileName || file.name);
				up.settings.multipart = true;
				// use cakephp $this->data form for POST data
				up.settings.multipart_params = {
					'data[name]': (file.fileName || file.name), 
					'data[Relpath]': (file.relativePath || ''),
					'data[Root]': file.root || '',
					'data[BatchId]': CFG['session'].BatchId,
					'data[IsOriginal]': up.settings.resize ? 0 : 1,
					'data[DeviceID]' : Util.get_DeviceID(),
				}
				var check;
			},
			viewchanged: function(event, args){
				var check;
			}
		}, 
	});
	// var uploader = $('#uploader').plupload('getUploader');
	// uploader.trigger('Init');

	// Handle the case when form was submitted before uploading has finished
	$('#form').submit(function(e) {
		// Files in queue upload them first
		if ($('#uploader').plupload('getFiles').length > 0) {

			// When all files are uploaded submit form
			$('#uploader').on('complete', function() {
				$('#form')[0].submit();
			});

			$('#uploader').plupload('start');
		} else {
			alert("You must have at least one file in the queue.");
		}
		return false; // Keep the form from submitting
	});
});