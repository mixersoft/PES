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
(function(){
	
	var Y = SNAPPI.Y;
	var TIMEOUT = 5000;
	/**************************************************************************
	 * Cakephp style Session Flash from JS
	 */
	
	var Flash = function(content) {
		var Y = SNAPPI.Y;

		var content = arguments;

	};
	Flash.prototype = {
		flash : function(content) {
			var msgNode = Y.one('#content > div.messages');
			if (!msgNode) {
				var tokens = {
					'class' : 'messages',
					id : 'flashMessage'
				};
				var msgNode = Y.Node.create(
						Y.substitute("<div id='{id}' class='{class}'></div>",
								tokens)).dom();
				Y.one('#content').prepend(msgNode);
				msgNode.setContent(null).append("<div class='message'>"+content+"</div>");
			} else {
				msgNode.setContent(null).append("<div class='message'>"+content+"</div>");
				msgNode.removeClass('hide');
			}

			SNAPPI.timeout.flashMsg = Y.later(TIMEOUT, {}, function() {
				msgNode.addClass('hide');
			});
		},
		flashJsonResponse: function(o){
			try {
				var msg = o.responseJson.message || Y.JSON.Stringify(o.responseJson);
			} catch (e) {
				msg = o.responseText;
			}
			if (SNAPPI.timeout && SNAPPI.timeout.flashMsg) {
				SNAPPI.timeout.flashMsg.cancel();
			}
			SNAPPI.flash.flash(msg);			
		},
		setFlashOnReload : function(msg) {
			postMessageData = {
				'Message.flash.message' : msg,
				'Message.flash.element' : 'default',
				'Message.flash.params' : new Array()
			};
			callback = {
				complete : function(id, o, args) {
					var check;
				},
				failure : function(id, o, args) {
					var check;
				}
			};
			SNAPPI.io.writeSession(postMessageData, callback, '');
		}		
	};

	SNAPPI.flash = new Flash();
	
	SNAPPI.timeout = {	};	

})();