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
 * tab navigation
 *
 *
 */
(function(){
	
	var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.TabNav = function(Y){
		if (_Y === null) _Y = Y;
		/*
	     * make global
	     */
	    SNAPPI.TabNav = new TabNav();
	}
	
    /*
     * protected
     */
    var defaultCfg = {
        top: '#top',
        menu: '#tabs',
        nested: '#nested',
        section: '#section-tabs',	// used in /my/settings
        selected: {
            menu: null,
            nested: null
        }
    }
    
    var TabNav = function(cfg){
    	this.selected = {
            menu: {},
            nested: {},
            section: {},
		};
		this.lookup = {
            menu: [],
            nested: [],
            section: [],
        };
        this.init(cfg);
    };
	TabNav.listen = {};
	TabNav.find = {};	
    TabNav.prototype = {
    	init : function(cfg){
		},
        selectByName : function(page, isWizard){
            if (page.section) {
            	var container = _Y.one(defaultCfg.section);
                var section = container.one('#'+ page.section);
                if (section) {
					container.all("li.focus").removeClass('focus');
					var tab = section.get('parentNode');
                    tab.addClass('focus');
                    this.selected.section.id = page.section;
                    this.selected.section.name = section.get('innerHTML');
                }
            }
            // for the moment, nested menus are not being used
            if (page.menu) {
                var parts = page.menu.split(':');
                var tab = _Y.one(defaultCfg.menu + " li > a." + parts[0]);
                if (tab) {
					_Y.all(defaultCfg.menu + " .FigureBox.selected").removeClass('selected');
                    tab.get('parentNode').addClass('selected');
                    this.selected.menu.id = parts[0];
                    this.selected.menu.name = tab.get('innerHTML');
                }
                var nested = _Y.one(defaultCfg.nested + " > ul#" + this.selected.menu.id);
                if (nested) {
                    nested.addClass('selected').removeClass('hide');
                    // test for nested tab select
                    if (parts.length > 1 && parts[1]) {
                        nested.all("a").some(function(n2, i){
                            if (n2.get('innerHTML') == parts[1]) {
                                n2.get('parentNode').addClass('selected');
                                return true;
                            }
                            else 
                                return false;
                        }, this);
                    }
                }
            }
        },
        select : function(cfg){
            // select tab
            if (cfg && cfg.tab) {
                if (_Y.Lang.isString(cfg.tab)) {
                
                }
                else 
                    this.selected.menu.index = cfg.tab;
            }
            this.selected.menu.index = defaultCfg.selected.menu;
            // select tab
            _Y.all(defaultCfg.menu + " li > a").each(function(n, i){
                if (i == this.selected.menu.index) {
                    n.get('parentNode').addClass('selected');
                    this.selected.menu.id = n.get('className');
                    this.selected.menu.name = n.get('innerHTML');
                }
                else {
                    n.removeClass('selected');
                }
            }, this);
            // show/hide nested UL
            _Y.all(defaultCfg.nested + " > ul").each(function(n, i){
                if (this.selected.menu.id == n.get('id')) {
                    n.addClass('selected').removeClass('hide');
                    
                    // select nested LI
                    if (cfg && cfg.nested) {
                        if (_Y.Lang.isString(cfg.nested)) {
                        
                        }
                        else 
                            this.selected.nested.index = cfg.nested;
                    }
                    else 
                        this.selected.nested.index = defaultCfg.selected.nested;
                    
                    n.all("li").each(function(n2, i){
                        if (i == this.selected.nested.index) {
                            n2.addClass('selected').removeClass('hide');
                        }
                        else {
                            n2.removeClass('selected').addClass('hide');
                        }
                    }, this);
                }
                else {
                    n.removeClass('selected').addClass('hide');
                }
            }, this);
        },
    }
})();
