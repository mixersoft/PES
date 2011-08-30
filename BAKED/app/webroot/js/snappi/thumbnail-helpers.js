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
	/*
	 * protected
	 */
	// // find only visible elements. copied from SNAPPI.util.isDOMVisible(n);
    var _isDOMVisible = function(n){
    	return n.getComputedStyle('display') != 'none' && n.getComputedStyle('visibility') != 'hidden';
	};

	// find multiSelect boundary element for shift-click
	var _boundary = function(n) {
		var found = n.hasClass('selected');
		found = found && (n.hasClass('FigureBox'));
		found = found && _isDOMVisible(n);
		return found;
	};
	var MultiSelect2 = function(cfg) {
		this.selectHandler = function(e) {
			var target = e.target;
			if (!e.ctrlKey && !e.shiftKey) {
				// No shift key - remove all selected images,
				var found = false;
				target.ancestor('ul').all('.FigureBox.selected').each(function(node) {
					node.removeClass('selected');
					if (node.Thumbnail && node.Thumbnail.select) node.Thumbnail.select(false);
					found = true;
				});
				if (found)
					e.halt(); // halt click if necessary
			} else
				e.halt(); // if shift or control down, halt
			if (e.shiftKey) {
				this.selectContiguousHandler(target.ancestor('.FigureBox'));
			} else if (e.ctrlKey) {
				// Check if the target is an image and select it.
				var target = target.ancestor('.FigureBox');
				target.toggleClass('selected');
				if (target.Thumbnail && target.Thumbnail.select) target.Thumbnail.select();
				// save selction to Session for lightbox
				if (target.ancestor('#lightbox')) SNAPPI.lightbox.save();
			}
		};
	};
	
	MultiSelect2.prototype = {
		selectContiguousHandler : function(n) {
			// select target regardless
			n.addClass('selected');
	
			var end, start = n.previous(_boundary);
			if (!start) {
				start = n;
				end = start.next(_boundary);
			} else {
				end = n;
			}
			if (end) {
				var node = start;
				do {
					node = node.next(_isDOMVisible);
					if (node && node.hasClass('FigureBox')) {
						node.addClass('selected');
						if (node.Thumbnail && node.Thumbnail.select) node.Thumbnail.select(true);
					}
				} while (node && node != end);
			}
		},
		selectAll : function(nodeUL) {
			nodeUL.all('> .FigureBox').each(function(n, i, l) {
				// select
					n.addClass('selected');
					if (n.Thumbnail && n.Thumbnail.select) n.Thumbnail.select(true);
				});
		},
		clearAll : function(nodeUL) {
			nodeUL.all('> .FigureBox').each(function(n, i, l) {
				// select
					n.removeClass('selected');
					if (n.Thumbnail  && n.Thumbnail.select) n.Thumbnail.select(false);
				});
		},
		listen : function(container, status) {
			var Y = SNAPPI.Y;
			status = (status == undefined) ? true : status;
			container = container || 'section.gallery.photo > div';
			if (status) {
				// listen
				Y.all(container).each( function(n) {
						if (!n.listener_multiSelect) {
							n.listener_multiSelect = n.delegate('click',
								this.selectHandler, '.FigureBox',
								this
							);
						}
					}, this
				);
				// SNAPPI.lightbox.listen(true); // listen in base.js
			} else {
				// stop listening
				Y.all(container).each(function(n) {
						if (n.listener_multiSelect) {
							try {
								n.listener_multiSelect.detach();
							} catch (e) {}
						}
					}, this
				);
			}
		}		
	};
	/*
	 * make global
	 */
	SNAPPI.MultiSelect2 = MultiSelect2;
	SNAPPI.multiSelect = new MultiSelect2();

})();