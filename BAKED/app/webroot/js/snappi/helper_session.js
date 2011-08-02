/**
 *
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
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

	SNAPPI.namespace('SNAPPI.helper'); // define namespace, if not exist.

	var SessionHelper = function() {
	};
	// make static helper CLASS global
	SNAPPI.helper.Session = SessionHelper;

	/*
	 * Static methods
	 */
	SessionHelper.savePreviewSize = function(size) {
		var callback = {
			complete : function(id, o, args) {
				var check;
			},
			failure : function(id, o, args) {
				var check;
			}
		};
		var postData = [];
		var photoSizeKeyName = 'profile.previewSize';
		postData[photoSizeKeyName] = size;
		SNAPPI.io.writeSession(postData, callback, '');
	};
})();
