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
 * example:
 * UL#tab-list.inline
 * 		LI#tab-{label}.tab
 * SECTION.tab-view
 * 		ARTICLE#panel-{label}.tab-panel 
 * 
 * or 
 * 
 * UL#tab-list.inline
 * 		LI#tab-{label}.tab A[href={target}]
 * SECTION.tab-view[xhrsrc={target}]
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
	    var cfg = {
	    	tabList: '#tab-list',
	    	tabView: '#tab-section'
	    }
	    SNAPPI.tabSection = new TabNav();
	}
	
    /*
     * protected
     */
    var defaultCfg = {
        tabList: '#tab-list',
        tabLabel: '.tab-label',
        tabView: '.tab-view',
        tabPanel: '.tab-panel',
            	
        section: '#tab-list',	// used in /my/settings
        selected: {
            menu: null,
            nested: null
        }
    }
    /*
     * 
{tabList} ul.tab-list
ul{tabList}.tab-list li.tab.focus     
ul{tabList}.tab-list li.tab a.tab-label
*{tabView}
     * 
     */
    var TabNav = function(cfg){
    	this.cfg = {};
    	this.id = null;
    	this.disabled = false;
    	this.container = {};
    	this.focus = {};
        this.init(cfg);
    };
    
	TabNav.listen = {};
	TabNav.find = {};	// static property, lookup active tabs
	
	
	
    TabNav.prototype = {
    	init : function(cfg){
    		cfg = _Y.merge(defaultCfg, cfg);
    		this.cfg = cfg;
    		this.id = this.cfg.tabList.replace(/\s+/g,'-');
    		this.container = {
    			tabList: _Y.one(this.cfg.tabList),
    			tabView: _Y.one(this.cfg.tabView),
    		}
    		this.focus = {};
    		// register this tab for lookup
    		TabNav.find[this.id] = this;
		},
		/**
		 * @params selector string, CSS selector for either 
		 * 		LI{selector}.tab or 
		 * 		LI.tab > A{selector}, used by container.one()
		 * @params isWizard boolean, if true, use show/hide steps, otherwise load href
		 * TODO: isWizard is the wrong name
		 */
        selectByCSS : function(selector){
        	if (this.disabled) return false;
        	if (selector.id) selector = '#'+selector.id;	// dom
        	if (selector instanceof _Y.Node) selector = '#'+selector.get('id'); // node
        	// else 
            this.setFocus(selector);
            return this.showTabView();
        },
        /*
         * @return false if focus not changed
         */
        setFocus: function(selector) {
        	if (this.disabled) return false;
        	if (!selector || _Y.Lang.isNumber(selector)) {
        		return this.setFocusByIndex(selector);
        	}
        	if (this.focus.selector == selector) {
        		return false;	// unchanged
        	} else {
        		var tabList, tab, name_node;
        		if (!this.container.tabList) {
	        		// re-init if node not available
	        		this.container.tabList = _Y.one(this.cfg.tabList);
	        		this.container.tabView = _Y.one(this.cfg.tabView);
	        	}
        		
	        	tabList = this.container.tabList;
	        	tab = tabList.one(selector+'.tab') || tabList.one('.tab '+selector);
	        	if (!tab.hasClass('tab')) tab = tab.get('parentNode');
	        	
	        	if (tab) {
	        		try {
	        			tabList.all(".tab.focus").removeClass('focus');	
	        		} catch(e) {}
	        		tab.addClass('focus');
	        		this.focus.selector = selector;
	        		this.focus.id = selector.replace(/\s+/,'-');
	        		this.focus.node = tab;
	        		
	        		name_node = tab.one('a') || tab;
	                this.focus.name = name_node.get('innerHTML');
	        	} 
        	}
        	return this.focus;
        },
        /*
         * @return false if focus not changed
         */
        setFocusByIndex: function(num) {
        	if (this.disabled) return false;
        	num = num || 0;
        	var tabs, tab, name_node;
        	if (!this.container.tabList) {
        		// re-init if node not available
        		this.container.tabList = _Y.one(this.cfg.tabList);
        		this.container.tabView = _Y.one(this.cfg.tabView);
        	}
        	
        	try {
        		tabs = this.container.tabList.all(".tab");
    			tab = tabs.item(num);	
    			if (tab.hasClass('focus')) {
    				return false;  	// unchanged
    			} else {
    				tabs.removeClass('focus');
	    			tab.addClass('focus');
		    		this.focus.selector = num;
		    		this.focus.id = tab.get('id');
		    		this.focus.node = tab;
		    		
		    		name_node = tab.one('a') || tab;
		            this.focus.name = name_node.get('innerHTML');
		            return this.focus;
		        }
    		} catch(e) {}
    		return false;
        },
        /*
         * show/hide tabView by selector or focus
         * - checks for exiting panel by tabView.one(selector)
         * - selector can be found in 
         * 		- selector
         *  	- tab.getAttribute('panel')
         * 		- this.focus.id == tab.get('id')
         * 		- this.focus.selector
         * 	automatically replaces #tab{string} with #panel{string}
         * 
         * - if selector is not found, then try loadTabView() via XHR
         * 
         */
        showTabView : function(focus, selector) {
        	if (this.disabled) return false;
        	focus = focus || this.focus;
        	if (!focus.node) focus = this.setFocus(0);
        	
        	var href, tabView, selector, panel;
        	
        	tabView = this.container.tabView;
        	selector = selector || this.focus.node.getAttribute('panel') || this.focus.id || this.focus.selector;
			if (/^\#tab/.test(selector)) selector = selector.replace(/tab/, 'panel');
			panel = tabView.one(selector);
        	if (panel) {
    			tabView.all(this.cfg.tabPanel).addClass('hide');	
    			panel.removeClass('hide');
    			return false;
    		}
    		// else 
    		return this.loadTabView(focus);
        }, 
		/*
         * load by XHR
         * 
         * href = LI.tab A[href]
         * target = this.container.tabView
         */
        loadTabView : function(focus) {
        	if (this.disabled) return false;
        	focus = focus || this.focus;
        	if (!focus.node) focus = this.setFocus(0);
        	
        	var href, tabView;
        	
        	try {
	        	href = focus.node.one('a').getAttribute('href');
	        	if (/\/cancel/.test(href)) return false;	// deprecate
	        	if (/(\?|\&)disabled/.test(href)) return false;
	        	
	        	tabView = this.container.tabView;
	        	if ((tabView.getAttribute('xhrSrc') == href)) return false;
	        	
	        	tabView.setAttribute('xhrSrc', href);
	        	SNAPPI.xhrFetch.requestFragment(tabView);	
        	} catch (e) {}     	
			return false;
        }, 
        disable: function() {
        	this.disabled = true;
        }        
    }
})();
