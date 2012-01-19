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
 * Thumbnail - factory class
 * makes a LI element which wraps an IMG and additional behaviors and ratings
 * includes many static functions
 *
 */
(function(){
    if (!SNAPPI.Thumbnail) {
    
        var Y = SNAPPI.Y;
        
        /*
         * protected
         */
        var _pCount = 0;
        var _defaultPrefix = 'thumbnail';
        var _showSubstitutes; // stylesheet for hiding substitutes
        var _attachRating = function(node, v){
            v = v || 0;
            // create a div to hold rating
            var tokens = {
                className: 'ratingGroup',
                id: node.get('id') + "_ratingGrp"
            };
            var elRatingGroup = Y.Node.create(Y.substitute("<div id='{id}' class='{className}'></div>", tokens)).dom();
            node.append(elRatingGroup);
            var r = new SNAPPI.Rating(elRatingGroup, 5, v, true, 'input-rating');
            r.setDbValueFn = node.dom().setRatingDbValue;
            node.dom().Rating = r;
            node.one('img').addClass('rating' + v);
        };
        
        /*
         * Singleton/Factory Class, returns a LI *HTMLElement* with additional behaviors
         */
        Thumbnail = {
            zoomBoxOverlay: null,
            showSizeByRating: true,
            hideSubstitutes: true,
            setData: function(dataElement){
                // replace dataElement for existing Thumbnail
                // not yet implemented
            },
            make: function(bindTo, cfg){
                var defaultCfg = {
                    deferLoad: false,
                    draggable: true,
                    droppable: false,
                    hideRepeats: false,
                    queue: true
                };
                _cfg = Y.merge(defaultCfg, cfg);
                
                var tokens = {
                    id: Y.guid('thumb-'), // Thumbnail.makeId(bindTo, _cfg.prefix),
                    className: 'thumb-wrapper thumbnail sq'
                };
                var LI = Y.Node.create(Y.substitute("<LI id='{id}' class='{className}'></LI>", tokens));
                var domLI = LI.dom();
                if (Y.Lang.isArray(bindTo.boundTo)) {
                    bindTo.boundTo.push(domLI);
                }
                else {
                    bindTo.boundTo = [domLI];
                }
                domLI.data = bindTo;
                
                /*
                 * mark substitutes
                 */
                if (bindTo.substitutes) {
                    bindTo.substitutes.add(domLI); // replace data with Thumb in SubstitutionGroupData
                    SNAPPI.SubstitutionGroup.styleElements(domLI);
                    _cfg.droppable = _cfg.hideRepeats === false;
                }
                
                /*
                 * create child IMG
                 */
                var tokens = {
                    id: Y.guid('img-'), // LI.get('id') + '_img',
                    className: 'blur'
                };
                var IMG = Y.Node.create(Y.substitute("<IMG id='{id}' class='{className}' />", tokens));
                if (bindTo.imageHeight > bindTo.imageWidth) 
                    IMG.addClass('portrait');
                /*
                 * get src url, varies according to datasource
                 */
                if (bindTo.urlbase === undefined) 
                    bindTo.urlbase == '';
                var src = Thumbnail.getImgSrcBySize(bindTo, 'sq', IMG);
                if (_cfg.queue && SNAPPI.imageloader.QUEUE_IMAGES) 
                    IMG._node.qSrc = src;
                else 
                    IMG.set('src', src);
                
                //SNAPPI.util3.ImageLoader.queueOneImg(IMG); // defer, queue by selector
                
                IMG.set('title', src);
				var thumbWrap = Y.Node.create("<div class='thumb'></div>").append(IMG);
                LI.append(thumbWrap);
                domLI.img = IMG; // can be replaced by LI.one('img');
                // make the li draggable
                if (_cfg.draggable) 
                    SNAPPI.DragDrop.pluginDrag(LI);
                
                /*
                 * add additionial Thumbnail methods to domLI
                 */
                domLI.Thumbnail = ThumbnailBehaviors; // check for className.match('thumb-wrapper')>=0
                Y.mix(domLI, ThumbnailBehaviors);
                
                _attachRating(LI, bindTo.rating);
                
                // legacy, please deprecate
                LI.wrapper = domLI;
                LI.wrapper.Thumb = LI;
                LI.Thumb = LI; // this backlink is key for getThumbElement(dataElement)
                // legacy
                
                return domLI;
            },
            // deprecated
            makeId: function(bindTo, prefix){
                prefix = prefix || _defaultPrefix;
                var template = "(thumb:" + SNAPPI.util.hash(bindTo) + ")";
                return prefix + template;
            },
            getImgSrcBySize: function(bindTo, size, Img){
                /*
                 * ToDo: this function varies by CastingCall provider. i.e. snappi vs. flickr
                 */
                var src;
                if (SNAPPI.DATASOURCE.HOST == 'AIR') {
                    var callback = {
                        success: function(src, arguments){
//console.log("******** thumbnail3.getImgSrcBySize()  change this to updated return value  **********");
//							arguments.img.dom().src = src; 							
                            arguments.img.dom().src = SNAPPI.DATASOURCE.schemaParser.getImgSrcBySize(arguments.node, arguments.size);
                        },
						arguments: {node: bindTo, size: size, img: Img}
                    };
                    src = SNAPPI.DATASOURCE.schemaParser.getImgSrcBySize(bindTo, size, callback);
                }
                else {
					src = bindTo.src ? bindTo.src  : bindTo;
                    src = SNAPPI.DATASOURCE.schemaParser.getImgSrcBySize(src, size, bindTo);
					src = bindTo.urlbase ? bindTo.urlbase + src : src;
                }
                return src; // default is passthru
            },
            getThumbFromChild: function(node){
                return Y.one(node).ancestor('.thumb-wrapper').dom();
            },
            getStackDataFromChild: function(el){
                alert("Thumbnail.getStackDataFromChild should be deprecated");
                var UL = UL.ancestor('.thumb-wrapper').get('parentNode').dom();
                return UL ? UL._dataElementSH : null;
            }            /*
             * thumbnail size varies by rating;,
             * TODO: these should be protected in a closure
             */
            ,
            toggleHideSubstitutes: function(value){
                if (_showSubstitutes === undefined) {
                    // change style of class "div.ratingGroup" to display:none
                    _showSubstitutes = new Y.StyleSheet('hideSubstitutes');
                    _showSubstitutes.disable();
                    _showSubstitutes.set('ul.photo-roll > li.substitute-hide', {
                        display: 'block'
                    });
                    _showSubstitutes.set('ul.photo-roll > li.substitute-show > img', {
                        border: '1px solid red'
                    });
                }
                Thumbnail.hideSubstitutes = value;
                if (value) {
                    _showSubstitutes.disable();
                }
                else {
                    _showSubstitutes.enable();
                    
                }
                // make substitutionGroups droppable when visible
                Thumbnail.makeSubstitutionGroupsDroppable(!value);
                
                // scroll focus into view
				if (SNAPPI.StackManager)
                	SNAPPI.StackManager.getFocus().scrollFocusIntoView(true);
                
            },
            makeSubstitutionGroupsDroppable: function(value){
                /*
                 * this doesn't work.
                 */
                Y.all('#content-tabview div.stack-content > ul.photo-roll').each(function(ul){
                    if (value) {
                        SNAPPI.DragDrop.pluginDrop(ul);
                    }
                    else {
                        /*
                         * this is a bug. ul is a node, but it isn't the node with the dd property
                         * until after this forced conversion
                         */
                        ul.dom().node.unplug('drop'); // have to lookup the _plugin property 
                    }
                });
            },
            addToSubGroup: function(subGroup, nodeList){
                subGroup.dom().subGr.move(nodeList);
                return;
            },
            removeFromSubGroup: function(dropTarget, nodeList){
                nodeList.each(function(n){
                    var subGroup = n.ancestor('ul.substitutionGroup');
                    subGroup.dom().subGr.remove(nodeList);
                });
                return;
            },
            _returnHome: function(nodeT){
                // find the photo-roll of the preceding dataElement
                var stack = nodeT.ancestor('div.stack-content').dom().stack;
                var afterEl = stack.nextElement(nodeT.dom().data);
                //                Y.one(afterEl.parentNode).insert(nodeT, afterEl);
                afterEl.ynode().insert(nodeT, 'before');
            }
        };
        
        
        
        var ThumbnailBehaviors = {
            unbind: function(){
                var boundTo = this.data.boundTo;
                if (boundTo) {
                    for (var i = 0; i < boundTo.length; i++) {
                        if (boundTo[i].id == this.id) {
                            boundTo.splice(i, 1);
                            i--;
                        }
                    }
                };
                this.ynode().remove();
            },
            getFocus: function(){
                Y.one(this).one('img').replaceClass('blur', 'focus');
                //                    this.showZoom();  // incomplete, disable for now
            },
            loseFocus: function(){
                Y.one(this).one('img').replaceClass('focus', 'blur');
                // hide Zoom if visible
                //			SNAPPI.Preview.hideZoom();
            },
            showZoom: function(size){
                if (SNAPPI.util.LoadingPanel.element.ynode().getStyle('visibility') == 'visible') 
                    return;
                size = size || 'bs';
                var src, oImg;
                if (Thumbnail.zoomBoxOverlay) {
                    // move overlay
                    oImg = Thumbnail.zoomBoxOverlay.bodyNode.one('img');
                    src = Thumbnail.getImgSrcBySize(this.data, 'bs', oImg);
                    oImg.set('src', src);
                    Thumbnail.zoomBoxOverlay.set('centered', '#' + this.id);
                    Thumbnail.zoomBoxOverlay.bodyNode.setStyle('visibility', 'visible');
                }
                else {
                    oImg = Y.Node.create("<img id='zoomBox'>");
                    src = Thumbnail.getImgSrcBySize(this.data, 'bs', oImg);
                    var overlay = new Y.Overlay({
                        width: "240px",
                        height: "240px",
                        bodyContent: oImg,
                        visible: true,
                        centered: '#' + this.id,
                        zIndex: 2
                    });
                    /* Render it to #overlay-align element */
                    overlay.render("#all-albums");
                    Thumbnail.zoomBoxOverlay = overlay;
                    Thumbnail.zoomBoxOverlay.show();
                    Thumbnail.zoomBoxOverlay.bodyNode.setStyle('visibility', 'visible');
                    var check;
                }
                
                
                
            },
            setRating: function(i, silent){
                silent = silent || false;
                if (silent) {
                    if (Thumbnail.showSizeByRating) {
                        var oldvalue = this.Rating.value;
                        this.img.replaceClass('rating' + oldvalue, 'rating' + i);
                        //                            Y.one(this).one('img').replaceClass('rating' + oldvalue, 'rating' + i);
                    }
                    this.Rating.render(i);
                }
                else 
                    this.Rating.onClick(i, silent);
            },
            setRatingDbValue: function(value){
                // should be a dataElement
                var t = Y.one(this).ancestor('.thumb-wrapper');
                /*
                 * I need to get a ref to the DataElement here
                 * from the Preview RatingGroup, which is NOT
                 * a child of thumb-wrapper
                 */
                t.dom().data.set({
                    rating: value
                });
            },
            setTagDbValue: function(value){
                var LI;
                if (this.data && this.data.tags !== undefined) {
                    LI = this;
                }
                else {
                    LI = Y.one(this).ancestor('.thumb-wrapper');
                }
                if (LI) {
                    var aTags, curTags = LI.data.tags;
                    if (curTags) {
                        aTags = curTags.split(';');
                        for (var i = 0; i < aTags.length; i++) {
                            if (aTags[i] == value) 
                                return;
                            
                            if (aTags[i] == '') 
                                aTags.splice(i, 1); // remove empty strings
                        }
                    }
                    else 
                        aTags = [];
                    aTags.push(value.trim());
                    var strTags = aTags.join(';');
                    LI.data.set({
                        tags: strTags + ';'
                    });
                }
            },
            _styleSubstituteGroup: function(){
                if (this.data.substitutes) {
                    // find subGroup
                    var subGr = this.parentNode.subGr;
                    if (subGr) {
                        // hide Repeats mode with SubstitutionGroups
                        subGr.findBest();
                        SNAPPI.SubstitutionGroup.styleElements(subGr);
                    }
                    else {
                        // normal mode, all elements in photo-roll
                        var subGrData = this.data.substitutes;
                        subGrData.findBest();
                        SNAPPI.SubstitutionGroup.styleElements(subGrData);
                    }
                    
                }
            },
            syncChanges: function(bindTo, change){
                if (change.rating !== undefined) {
                    this.setRating(bindTo.rating, 'silent');
                    var s = SNAPPI.Stack.getStackFromChild(this.dom());
                    if (s._dataElementSH.defaultSortCfg[0].property == 'rating') {
                        // if sort by Rating, do we resort??
                    }
                    this._styleSubstituteGroup(); // mark best after rating changes
                }
                if (change.substitutes !== undefined) {
                    this.data.substitutes = change.substitutes;
                }
            }
        };
        
        SNAPPI.Thumbnail = Thumbnail;
        
    }
})();

var check;

