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
	CFG = (typeof CFG == 'undefined')? {} : CFG; 
	/*
	 * helper functions
	 */
	var Util = new function(){}
	Util.isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
	Util.userOverrideOK = false;
	Util.get_BatchID = function (utc_now) {
		utc_now = utc_now || Math.floor(new Date().getTime()/1000);
		var root, session;
		if (!( CFG['session'] && CFG['session'].BatchId) && typeof ($.cookie) != 'undefined') {
			// save batchId to cookie: plupload
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
				// ???: use cookie expiration to expire BatchId, instead of time-delta refresh
				// session = $.extend(session || {}, {'BatchId': utc_now});
			}
		}
	}
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
		px = px || 1080;
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
	Util.confirmNoChrome = function(){
		if (Util.userOverrideOK) return true;
		if ($('.plupload_droptext .confirm-not-chrome').length) {
			Util.notify("Please click the I'm Sure button to continue.");
		}
		$('.plupload_droptext').html($('#markup-uploader .confirm-not-chrome').clone())
			.height($('.plupload_dropbox').height())
			.removeClass('hide');
        return false;
	},
	Util.confirmPreferFilesInChrome = function(){
		if (Util.userOverrideOK) return true;
		if ($('.plupload_droptext .confirm-prefer-browse').length) {
			Util.notify("Please click the I'm Sure button to continue.");
		}
		$('.plupload_droptext').html($('#markup-uploader .confirm-prefer-browse').clone())
			.height($('.plupload_dropbox').height())
			.removeClass('hide');
        return false;
	},
	Util.allowUserOverride = function(e){
		Util.userOverrideOK = true;
		$('.plupload_message').remove();
		
		var up = $('#uploader').plupload('getUploader'); 
		up.disableBrowse(!Util.userOverrideOK);	
		if (up.files.length) $('.plupload_droptext').addClass('hide');
		var msg = Util.isChrome ? 'drag folders or JPG files here' : 'drag JPG files here';
		$('.plupload_droptext').html(msg);
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
	Util.notify = function(msg, status) {
		if (!msg) return;
		status = status || 'info';
		$('#uploader').plupload('notify', status, msg);
	}
	// make global,
	CFG['plupload'] = $.extend(CFG['plupload'] || {}, Util);		
	
	
	$("#uploader").plupload({
		// General settings
		runtimes : 'html5,flash,silverlight',
		url : null,

		// Select multiple files at once
		multi_selection: true, // if set to true, html4 will always fail, since it can't select multiple files

		// Maximum file size
		max_file_size : '12mb',

		// User can upload no more then 20 files in one go (sets multiple_queues to false)
		max_file_count: 50,
		
		// chunks : false,
		chunks : {
			size: '2mb',
			send_chunk_number: false // set this to true, to send chunk and total chunk numbers instead of offset and total bytes
		},

		// Resize images on clientside in Chrome, or FALSE for other browsers
		resize : Util.resize(),
	
		// Specify what files to browse for
		filters : [
			{title : "Image files", extensions : "jpg,jpeg"},
			// {title : "Image files", extensions : "jpg,gif,png"},
			// {title : "Zip files", extensions : "zip,avi"}
		],

		// Rename files by clicking on their titles
		rename: true,
		
		// Sort files
		sortable: true,

		// Enable ability to drag'n'drop files onto the widget (currently only HTML5 supports that)
		dragdrop: true,
		
		buttons: {
			browse: true,
			start: true,
			stop: true	
		},

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
			Init: function(up, runtime){
				var msg = (Util.isChrome) 
					? $('#markup-uploader .is-chrome')
					: $('#markup-uploader .not-chrome'); 
				$('.plupload_droptext').html(msg);
				if (!Util.isChrome) $("a.plupload_start").button('disable');
				
				/*
				 * override add files button
				 */
				$('.plupload_logo').on('click',function(event){
					$("a.plupload_add").trigger('click');	
				})
				$("a.plupload_add").bind('click', function(event){
					if (!Util.userOverrideOK) {
						if (Util.isChrome)	Util.confirmPreferFilesInChrome();
						else Util.confirmNoChrome();
						$("a.plupload_start").button('disable');
		       			$('#uploader').plupload('getUploader').disableBrowse(true);
		       			return false;
		       		}
				});
				/*
				 * I'm Sure button listener
				 */
				$('.plupload_droptext').one('click', 'label.plupload_button', function(e){
					Util.allowUserOverride();
				});
				
				/*
				 * bind BEFORE internal event handlers to override
				 */
				up.bind('StateChanged', function(up){
					// Start/Stop
					console.log('event=StateChanged OVERRIDE');
				});
				up.bind('FilesAdded', function(up, files) {
					console.log('event=FilesAdded OVERRIDE');
					// note: not sure we need this if we override QueueChanged
					if (!Util.isChrome && !Util.userOverrideOK) { 
							Util.confirmNoChrome();
							$("a.plupload_start").button('disable');
							// remove files from uploader (not queue) 
							for (var i in up.files) {
								up.removeFile(up.files[i]);
							}
							up.refresh();
console.info('prevent 	event=FilesAdded ');							
							return false;
					}
				});
				up.bind('MissingExif', function(e, up, file) {
					console.warn("mark JPG files with missing Exif");
					up._missingExif = up._missingExif || [];
					up._missingExif.push(file); 
					setTimeout(function(){
						// ???: MissingExif is only called 1 time before exception
						// probably caused by adding/removing files concurrently
						// timeout seems to fix the problem
						$('#uploader').plupload('removeFile', file);
						
						$('#uploader').plupload('notify', 'error', 'JPG files missing the date taken value have not been added.');
						// return false;
					}, 5000);
				});
				up.bind('selected', function(up, files){
					// file selected
					console.log('event=selected: file selected OVERRIDE');
					if (!Util.isChrome && !Util.userOverrideOK) {
console.info('prevent event=selected: file selected ');						
						return false; // do not add to Queue
					}
				});
				up.bind('QueueChanged', function(up){
					// add/remove file to Queue
					// note: uploader.filesAdded triggers QueueChanged BEFORE FilesAdded
					console.log('event=QueueChanged OVERRIDE');
					if (!Util.isChrome) {
						if ($('.plupload_droptext .not-chrome').length==1) { 
							Util.confirmNoChrome();
						}
						if (!Util.userOverrideOK) {
							$("a.plupload_start").button('disable');
							// remove files from uploader (not queue) 
							for (var i in up.files) {
								up.removeFile(up.files[i]);
							}
							up.refresh();
console.info('prevent 	event=QueueChanged ');
							return false; // do not add to Queue
						}
					}
				});
			},
			PostInit: function(up, params) {	
				if (up.features.dragdrop && up.settings.dragdrop) {
					var target = $('#uploader_dropbox');
					Util.activate_dragover(target);
					target.one('drop', function(event){
						target.trigger('dndHoverEnd');
						up.trigger('selected');
					});
				}
				$('form#form').removeClass('hide');
				Util.get_DeviceID();
			},

			FilesAdded: function(up, files) {
				// add folders (count) view
				// NOTE: this handler is called directly by jqueryui.addSelectedFiles(), 
				// cannot override in all cases, so we remove added Files in QueueChanged if needed 
				var root;
				if (Util.userOverrideOK &&  files.length) $('.plupload_droptext').html('');
				for (var i in files) {
					if (files[i].relativePath) {
						files[i].fileName = files[i].name;
						files[i].name = files[i].relativePath;
						root = files[i].relativePath.split('/');
						if (root.length > 2 ) files[i].root = '/'+root[1];				
						else files[i].root = '';
					}
				}
				// remove duplicate files using settings.prevent_duplicates = true
			},
			QueueChanged: function(up){
				// refresh the UI widget on 5 sec delay
				// remove duplicates from UI widget view
				console.log('event=QueueChanged');
			},
			StateChanged: function(up){
				// Start/Stop
				console.log('event=StateChanged');
			},
			BeforeUpload: function(up, file) {
console.log("BeforeUpload for file=#"+file.id);				
				var utc_now = Math.floor(new Date().getTime()/1000);
				Util.get_BatchID(utc_now);	
				CFG['session'].LastUpload = utc_now;
			},
			UploadFile: function(up, file) {
console.log("UploadFile for file=#"+file.id);
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
			FileUploaded: function(up, file) {
				var msg = {key:'msg', value:'FileUploaded'};
				window.parent.postMessage(msg, '*');
				var check;
			},
			complete: function(up, files) {
				console.log('event=complete');
			},
			viewchanged: function(event, args){
				console.log('event=viewchanged');
			}
		},
		// ui events
		selected: function(e, args ) {
			// file selected
			console.log('event=selected: file selected');
			var up = args.up,
				files = args.files;
			if (Util.userOverrideOK) {
				$('.plupload_droptext').addClass('hide');
			} 	
			if (Util.isChrome && !Util.userOverrideOK) {
				// if from drop event or browse, 
				var onlyFolders = true;
				for (var i in files) {
					if (!files[i].root) {
						onlyFolders = false;
						break;
					}
				}					
				if (onlyFolders) {
					$('.plupload_droptext').addClass('hide');
				} else {
					// if from browse event or drop event with files
					Util.confirmPreferFilesInChrome();
				}
			} else if (!Util.userOverrideOK) {
				// Util.confirmNoChrome();
console.error('removed .not-chrome selected action');				
				// addFiles BEHIND .confirm-not-chrome
			}
		},
		start: function(event, up) {
			// upload queue started
			console.log('event=start');
			if ($('.plupload_droptext .confirm-not-chrome').length==1) {
				Util.notify('Snaphappi works better when you upload entire folder(s) of JPGs using the Chrome browser. Please confirm that you do NOT want to use Chrome.')
				event.stopPropagation();
	       		event.preventDefault();
			} 
		},  
		complete: function(e, up) {
			// upload queue processing complete
			console.log('event=complete, upload queue processing complete');
		},
		viewchanged: function(e, up) {
			console.log('event= view changed');
		},
	});
	var uploader = $('#uploader').plupload('getUploader');
	
	var isIFrame = !(window.parent==window); 
	if (isIFrame) {
		// resize to fit iframe height
		var MARGIN_H = 7,
			iFrameH = $(window).height();
		$('#uploader_container').height(iFrameH-MARGIN_H);
		$('#uploader_container').bind('resize', function(e){
			// resize parent <iFrame> height
			var msg = {key:'resize', value:{h:$(this).height()}};
			window.parent.postMessage(msg, '*');
		})
		/*
		 * listen for messages from window.parent
		 */
		$(window).bind('message', function(e){
			var json = e.originalEvent.data,
				origin = e.originalEvent.origin;
			// resize only
			if (json.key == 'resize') {
				var iFrameH = json.value.h;
				$('#uploader_container').height( iFrameH - MARGIN_H);
			}
		});
		// listen for outside iframe resize
	}

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