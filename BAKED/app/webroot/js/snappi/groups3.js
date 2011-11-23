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
 * Groups: group of Thumbnails, with a bindTo property, represented in markup as UL/LI
 * manages both CSS and underlying data object
 *
 */
/*
 * Group - yui3
 */
(function(){


    var Y = SNAPPI.Y;
    
    /*
     * protected
     */
    var _registeredGroups = {};
    /*
     * manages the Data element for SubstitutionGroup.
     * Note: data exists as part of Thumbnail.data.substitutes.
     * from xml parse in datasource3.js
     * this is well before DOM elements have been created
     */
    var SubstitutionGroupData = function(cfg){
    	this.init(cfg);
    };
    SubstitutionGroupData.prototype = {
        init : function(cfg){
            var defaultCfg = { 
            	stale: false
            };
            var _cfg = Y.merge(defaultCfg, cfg);
            
            // sortedHash
            this.id = _cfg.id || Y.guid('subGrData-');
            this._sh = new SNAPPI.SortedHash();
            
            // public methods
            this.best = null;
            this.stale = _cfg.stale;
            this.shotType = _cfg.shotType;
        },
        /*
         * iterate through each element in SubGr, set this.best, and return
         */
        findBest : function(){
            var best = null, bRating = -1;
            this._sh.each(function(o){
                if (best == null) {
                    best = o;
                }
                else {
                    var oRating = o.rating || o.data && o.data.rating || 0;
                    bRating = best.rating || best.data && best.data.rating || 0;
                    if (oRating > bRating) {
                        best = o;
                    }
                }
            });
            this.best = best;
            return best;
        },
        
        isBest : function(o){
            if (!o.Audition) o = o.dom();
            try {
                var bestId = this.best.id || this.best.data && this.best.data.id;
                var oId = o.id || o.data && o.data.id;
                return (bestId == oId);
            } 
            catch (err) {
                return false;
            }
        },
        /*
         * isBest also includes any photo with the same rating as Best???
         */        
        sameAsBest : function(o) {
        	if (!o.Audition) o = o.dom();
            try {
                var bestScore = this.best.rating || this.best.data && this.best.data.rating || 0;
                var oScore = o.rating || o.data && o.data.rating || 0;
                return (bestScore == oScore);
            } 
            catch (err) {
                return false;
            }        	
        	
        },
        /*
         * compare 1 element against this.best, set if necessary
         */
        setIfBest : function(o){
        	if (!o.Audition) o = o.dom();
            var key = o.id || o.data && o.data.id;
            if (!this._sh.hasKey(key)) 
                return false; // not in subGrData
            if (this.best == null) {
                this.best = o;
            }
            else {
                try {
                    var oRating = o.rating || o.data && o.data.rating || o.Audition.Photo.Fix.Rating || 0;
					var b = this.best;
					var bRating = b.data ? b.data.rating : b.rating ? b.rating : b.Audition.Photo.Fix.Rating || 0;
                    if (oRating > bRating) 
                        this.best = o;
                } 
                catch (err) {
                    return false;
                }
            }
            return true;
        },
        setBest : function(o) {
        	this.best = o;
        },
        
        /**
         * add audition to Shot
         * @param o audition
         * @param noRemove boolean, DEFAULT == FALSE, if noRemove==TRUE, do NOT remove audition from old Shot
         * 		WARNING: to prevent data errors, you need to manually remove from old Shots
         * @return
         */
        add : function(o, noRemove){
        	noRemove = noRemove || false;
            var data, key;
            if (!o.Audition) o = o.dom();
            o = o.data || o; // add back reference
            var oldSubGroup = o.Audition.Substitutions;
            var check = o.Audition.Substitutions == this;	// should be false???
            if (!noRemove && oldSubGroup && oldSubGroup.id !== this.id) {
                var ret = oldSubGroup.remove(o);
            }
            this._sh.add(o); // replace dataElement with Thumbnail
            
            // update back reference to subGrData
            var check = this._sh.get(o); 	// audition
            this._count = this.count();
            check = this.id;				// destSubGroup.id
            check = o.Audition.Substitutions == this;	// should be false???
            o.Audition.Substitutions = this;
            o.Audition.SubstitutionREF = this.id;
            o.substitutes = this; // DEPRECATE
            return this.setIfBest(o);
        },
        /*
         * returns true if best was updated after removal
         */
        remove : function(o){
            if (!o.Audition) o = o.dom();        	
            this._sh.remove(o);
            this._count = this.count();
            var hiddenShotCount = o.Audition.Shot.count;
            try {
            	// remove reference to Shot from audition
				o.Audition.Shot = {id: null, count: null};
				o.Audition.SubstitutionREF = null;
				o.Audition.Substitutions = null;
            } catch(e){}            
            if (this.isBest(o)) {
                this.findBest(); // removed the best pick, so find new best
                return true; // stale indicator
            }
            return null;
        },
        importGroup : function(srcGroup){
        	var destGroup = this;
        	var d1 = destGroup.count();
        	var s1 = srcGroup.count();
        	srcGroup.each(function(o){
        		destGroup.add(o, false);				// noRemove == TRUE
        	});
        	srcGroup.stale = true;
        	srcGroup._sh.clear();
        	SNAPPI.Auditions._shotsSH.remove(srcGroup);
        },        
        each : function(fn, context){
            this._sh.each(fn, context);
        },
        get : function(o){
            return this._sh.get(o);
        },
        count : function(){
            return this._sh.count();
        },        
        indexOfBest : function (){
        	return this._sh.indexOf(this.best);
        },
        hashcode : function(){
            return this.id;
            // return this.dataElementType + '_' + this.id;
        }
    };// end SubstitutionGroupData Class def
    
    
    
    
    
    
    
    
    
    /**
     * Group BASE Class
     */
    var Group = function(cfg){
        var defaultCfg = {};
        var _cfg = Y.merge(defaultCfg, cfg);
        
        
        // initial rendering of substitutes using stack._fnInsertWithSubstitutes
        this.init = function(cfg){
        };
        // why am I calling the baseClass method here?
        this.add = function(subGroup, nodeList){
        };
        this.remove = function(subGroup, nodeList){
        };
        this.style = function(parent){
        };
        
        this.init(cfg);
    };
    
    /*
     * static functions
     */
    Group.styleOddEven = function(cfg){
        var defaultCfg = {
            parent: null,
            selector: 'ul',
            oddStyle: '',
            evenStyle: ''
        };
        _cfg = Y.merge(defaultCfg, cfg);
        var parent = _cfg.parent.ynode();
        //            var parent = _cfg.parent instanceof HTMLElement ? Y.one(_cfg.parent) : _cfg.parent;
        parent.all(_cfg.selector).each(function(n, i, nl){
            var thisStyle = (i % 2 == 0) ? _cfg.evenStyle : _cfg.oddStyle;
            var lastStyle = thisStyle == _cfg.evenStyle ? _cfg.oddStyle : _cfg.evenStyle;
            // if prior node has odd/even class, then change, if null, then skip
            n.addClass(thisStyle);
            n.removeClass(lastStyle);
        });
    };
    Group.getRegistered = function(subGrData){
        if (subGrData.id in _registeredGroups) 
            return _registeredGroups[subGrData.id];
        return false;
    };
    Group.register = function(subGrData, group){
        _registeredGroups[subGrData.id] = group;
    };
    
    
    /*
     * Class SubstitutionGroup
     * manages DOM elements associated with shots
     */
    var SubstitutionGroup = function(cfg){
        // parent
        var defaultCfg = {
            subGrData: null,
            el: null,
            parent: null,
            className: 'substitutionGroup'
        };
        var _cfg = Y.merge(defaultCfg, cfg);
        SubstitutionGroup.superclass.constructor.call(this, _cfg);
        
        this.init = function(_cfg){
            this._subGrData = _cfg.subGrData;
            if (_cfg.el == null) {
                // create element, if necessary
                this._n = Y.Node.create("<ul class='" + _cfg.className + "' />");
                this._el = this._n.dom();
                
            }
            else {
                this._el = _cfg.el.dom();
                this._n = _cfg.el.ynode();
            }
            // append to parentEl
            _cfg.parent.ynode().append(this._el);
            this._el.subGr = this; // back reference
        };
        this.getEl = function(){
            return this._el;
        };
        this.add = function(nodeList){
            if (!(nodeList instanceof Y.NodeList)) {
                // make a nodeList
                if (nodeList instanceof HTMLElement) 
                    nodeList = Y.one(nodeList);
                if (nodeList.hasOwnProperty('_node')) 
                    nodeList = new Y.NodeList([nodeList]);
            }
            
            var pass = {
                byref: {}
            };
            nodeList.each(function(n){
                if (!n.hasClass('thumb-wrapper')) 
                    n = n.ancestor('.thumb-wrapper');
                var t = n.dom();
                this._subGrData.add(t, pass); // automatically removes from existing subGr
                this._el.appendChild(t); // update DOM, put in right place
            }, this);
            // style elements, include those changed on removal of node
            //                var stack = Y.one(this._el).ancestor('div.stack-content').dom().stack;
            //                SubstitutionGroup.styleElements(this._subGrData, stack);
            SubstitutionGroup.styleElements(this._subGrData);
            for (var p in pass.byref) {
                SubstitutionGroup.styleElements(pass.byref[p]);
            }
            // style parent after all adds are finished
        };
        this.move = function(nodeList){
            this.add(nodeList);
            // style parent
            SNAPPI.Group.styleOddEven({
                parent: this._el.parentNode,
                selector: 'ul.substitutionGroup',
                evenStyle: 'even'
            });
        };
        
        this.remove = function(nodeList){
            if (!(nodeList instanceof Y.NodeList)) {
                // make a nodeList
                if (nodeList instanceof HTMLElement) 
                    nodeList = Y.one(nodeList);
                if (nodeList.hasOwnProperty('_node')) 
                    nodeList = new Y.NodeList([nodeList]);
            }
            
            //                var pass = {
            //                    byref: {}
            //                };
            //                pass.byref[this._subGrData.id] = this._subGrData;
            nodeList.each(function(n){
                if (!n.hasClass('thumb-wrapper')) 
                    n = n.ancestor('.thumb-wrapper');
                var t = n.dom();
                if (t.data.substitutes) {
                    t.data.substitutes.remove(t);
                    delete t.data.substitutes;
                }
                /*
                 * remove the element from SubGr,
                 * return to original place according to stack._dataElement()
                 * unstyle removed element
                 */
                //                    deprecated Thumbnail._returnHome(n);
                var stack = n.ancestor('div.stack-content').dom().stack;
                var oDataElement = n.dom().data;
                
                var refNode = stack.nextElement(oDataElement).ynode();
                var nextUL = refNode.get('parentNode');
                if (nextUL.hasClass('substitutionGroup')) {
                    refNode = stack.prevElement(oDataElement).ynode();
                    nextUL = refNode.get('parentNode');
                    if (nextUL.hasClass('substitutionGroup')) {
                        if (nextUL.previous().hasClass('substitutionGroup')) {
                            // make a new UL
                            var newUL = Y.Node.create("<ul class='photoSet'></ul>");
                            nextUL.insert(newUL, 'before');
                            //                                nextUL.get('parentNode').insertBefore(newUL, nextUL);
                            // make droppable
                            var node = SNAPPI.DragDrop.pluginDrop(newUL);
                            // keep back reference to unplug
                            node.dom().node = node;
                            newUL.append(n);
                        }
                        else 
                            nextUL.previous().append(n);
                    }
                    else 
                        nextUL.insert(n, refNode);
                }
                else {
                    refNode.insert(n, 'before');
                    //						nextUL.insert(n, refNode);
                }
                
                
                SubstitutionGroup.removeStyle(n);
            });
            // style remaining elements in SubGroup
            SubstitutionGroup.styleElements(this._subGrData);
            SNAPPI.Group.styleOddEven({
                parent: this._el.parentNode,
                selector: 'ul.substitutionGroup',
                evenStyle: 'even'
            });
        };
        this.findBest = function(t){
            if (t instanceof HTMLElement && t.data) {
                return this._subGrData.setIfBest(t);
            }
            return this._subGrData.findBest();
        };
        
        
        /*
         * main
         */
        this.init(_cfg);
        
    };
    Y.extend(SubstitutionGroup, Group);
    
    
    /*
     * Static methods
     */
    SubstitutionGroup.getRegistered = function(subGrData, cfg){
        var subGrp = Group.getRegistered(subGrData); // superclass ???
        if (!subGrp) {
            subGrp = new SubstitutionGroup(cfg);
            
            // register subGr with parent class, Group. key on subGrData. 
            Group.register(subGrp._subGrData, subGrp);
        }
        return subGrp;
    };
    SubstitutionGroup.styleElements = function(o){
        // style entire group
        if (o instanceof SubstitutionGroup) 
            o = o._subGrData;
        if (o instanceof SubstitutionGroupData) {
            subGrData = o;
            subGrData.each(function(t){
                var n = Y.one(t);
                if (!(n.hasClass('thumb-wrapper'))) 
                    return;
                var ret = subGrData.isBest(t);
                if (ret === null) 
                    return;
                n.addClass(ret ? 'hiddenshot-show' : 'hiddenshot-hide');
                n.removeClass(!ret ? 'hiddenshot-show' : 'hiddenshot-hide');
            });
            return;
        }
        
        // style nodeList
        var nodeList = o;
        if (!(nodeList instanceof Y.NodeList)) {
            if (nodeList instanceof HTMLElement) 
                nodeList = Y.one(nodeList);
            if (nodeList.hasOwnProperty('_node')) 
                nodeList = new Y.NodeList([nodeList]);
        }
        nodeList.each(function(o){
            if (!o.hasClass('thumb-wrapper')) 
                o = o.ancestor('li.thumb-wrapper');
            if (o) {
                var ret = o.dom().data.substitutes.isBest(o);
                o.ynode().addClass(ret ? 'hiddenshot-show' : 'hiddenshot-hide');
                o.ynode().removeClass(!ret ? 'hiddenshot-show' : 'hiddenshot-hide');
            }
        });
    };
    SubstitutionGroup.removeStyle = function(n){
        if (!n.hasClass('thumb-wrapper')) 
            n = n.ancestor('li.thumb-wrapper');
        n.removeClass('hiddenshot-hide');
        n.removeClass('hiddenshot-show');
    };
    
    SNAPPI.Group = Group;
    SNAPPI.SubstitutionGroup = SubstitutionGroup;
    SNAPPI.SubstitutionGroupData = SubstitutionGroupData;
    
    
})();

