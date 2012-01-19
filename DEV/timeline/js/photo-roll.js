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
 * PhotoRoll - render/reuse thumbnails from JSON
 *
 */
(function(){

    /*
     * dependencies
     */
    var defaultCfg = {}
    var charCode = {
        nextPatt: /(^110$)|(^39$)|(^32$)|(^54$)/, // n,right,space,
        // keypad right
        prevPatt: /(^112$)|(^37$)|(^8$)|(^52$)/, // p,left,backspace,
        // keypad left
        closePatt: /(^27$)/,
        // escape
        selectAllPatt: /(^65$)|(^97$)/,
        // ctrl-a		
        groupPatt: /(^103$)/
        // ctrl-g		
    };
    // find only visible elements
    var isVisible = function(n){
        return !(n.hasClass('hidden') || n.hasClass('hide'));
    }
    var Y = SNAPPI.Y;
    
    
    
    var PhotoRoll = function(cfg){
        /*
         * protected methods
         */
        var isVisible = function(n){
            return !(n.hasClass('hidden') || n.hasClass('hide'));
        }
        /*
         * Key press functionality of next & previous buttons
         */
        this.handleKeypress = function(e){
            //            console.log(this.container.get('parentNode'));
            if (!this.container.ancestor('#content')) {
                // no container is longer visible, detach all listeners
                // probably replaced by new ajax fragment
                this.listen(false);
                return;
            }
            var charStr = e.charCode + '';
            if (charStr.search(charCode.nextPatt) == 0) {
                e.preventDefault();
                this.next();
            }
            else 
                if (charStr.search(charCode.prevPatt) == 0) {
                    e.preventDefault();
                    this.prev();
                }
                else 
                    if (e.ctrlKey) {
                        if (charStr.search(charCode.selectAllPatt) == 0) {
                            e.preventDefault();
                            this.selectAll();
                        }
                        if (charStr.search(charCode.groupPatt) == 0) {
                            e.preventDefault();
                            this.groupSubstitutions();
                        }
                    }
                    else 
                        return;
            //                else 
            //                    if (charStr.search(charCode.closePatt) == 0) {
            //							e.preventDefault();
            //                        this.closeLightBox();
            //                    }
        }
        this.container = null;
        this.auditionSH = null;
        this.listener = {};
        this.init(cfg);
        
    }
    
    PhotoRoll.prototype = {
        init: function(cfg){
            this._cfg = Y.merge(defaultCfg, cfg);
            if (this._cfg.sh) 
                this.auditionSH = this._cfg.sh;
            if (this._cfg.container) {
                this.container = this._cfg.container.ynode ? this._cfg.container.ynode() : Y.one('#' + this._cfg.container);
            }
			this.header = this.container.ancestor('div.photo.element-roll').previous("ul.photo-roll-header");
            
        },
        setAudition: function(sh){
            this.auditionSH = sh;
        },
        render: function(cfg){
            if (cfg && cfg.container) 
                this.container = cfg.container;
            
            var offset = 0;
            if (!this.auditionSH) 
                return;
            
            if (!this.container) 
                return;
            
            /*
             * infer paging method
             */
            var offset, page, perpage, nlist = this.container.all('li');
            if (cfg && cfg.page && cfg.perpage && nlist.size() <= cfg.perpage) {
                offset = 0;
                page = cfg.page;
                perpage = cfg.perpage;
            }
            else {
                // guess from UL list size
                perpage = nlist.size() || cfg && cfg.perpage;
                page = cfg && cfg.page || SNAPPI.displayPage.page || 1;
                offset = (page - 1) * perpage;
                SNAPPI.displayPage.perpage = perpage; // update guess
            }
            /*
             * reuse or create LIs
             */
            if (nlist.size()) {
                // if UL>LIs exist, reuse
                nlist.each(function(li, i, l){
                    var audition = this.auditionSH.get(offset + i);
                    if (audition) 
                        this.reuseLI(li, audition);
                }, this);
            }
            else {
                // otherwise create new LIs	
                var audition, i = offset, limit = offset + perpage;
                while (i < limit) {
                    audition = this.auditionSH.get(i++);
                    if (audition == null) 
                        break;
                    this.createLI(this.container, audition);
                }
            }
            var check;
        },
        reuseLI: function(li, audition){
            li.removeClass('hide').removeClass('selected');
            var old = li.dom().audition;
            //            unbind(old, li);
            if (old && old.bindTo) 
                old.bindTo.splice(old.bindTo.indexOf(li), 1);
            li.set('id', audition.id);
            audition.bindTo = (audition.bindTo && audition.bindTo.push) ? audition.bindTo.push(li) : [li];
            li.dom().audition = audition;
            var src = SNAPPI.DATASOURCE.schemaParser.getImgSrcBySize(audition.urlbase + audition.src, 'sq');
            var linkTo = '/photos/home/' + audition.id;
            var label = audition.label;
            var title = label;
            li.one('img').set('src', src).setAttribute('linkTo', linkTo).set('title', title).set('alt', title);
            li.one('div.thumb-label').set('innerHTML', label);
            if (li.dom().Rating) {
                // ??? do we need to change the ratingGroup id???
                li.dom().Rating.render(audition.rating);
            }
        },
        createLI: function(ul, audition){
            var li = Y.Node.create('<li class="thumbnail sq"><div class="thumb"><img></div><div class="thumb-label"></div></li>');
            li.set('id', audition.id);
            audition.bindTo = Y.Lang.isArray(audition.bindTo) ? audition.bindTo.push(li) : [li];
            li.dom().audition = audition;
            var src = SNAPPI.DATASOURCE.schemaParser.getImgSrcBySize(audition.urlbase + audition.src, 'sq');
            var linkTo = '/photos/home/' + audition.id;
            var label = audition.label;
            var title = label;
            li.one('img').set('src', src).setAttribute('linkTo', linkTo).set('title', title).set('alt', title);
            li.one('div.thumb-label').set('innerHTML', label);
            ul.append(li);
        },
        /*
         * NOTE: new listener created for every ajax page. not what happens to old listener
         */
        listen: function(status){
            status = (status == undefined) ? true : status;
            if (status) {
                if (this.listener.keypress == undefined) {
                    this.listener.keypress = Y.on('keypress', this.handleKeypress, document, this);
                    this.container.get('parentNode').focus();
                }
                this.next();
            }
            else {
                for (var i in this.listener) {
                    this.listener[i].detach();
                }
                this.listener = {};
            }
        },
        
        
        
        /*
         * focus, keyboard methods
         */
        next: function(){
            var next, prev = this.container.one('li.focus');
            if (prev) {
                prev.removeClass('focus');
                next = prev.next(isVisible);
            }
            else {
                next = this.container.one('li');
            }
            if (next) {
                next.addClass('focus');
                this.auditionSH.setFocus(next.get('id'));
            }
        },
        prev: function(){
            var next, prev = this.container.one('li.focus');
            if (prev) {
                prev.removeClass('focus');
                next = prev.previous(isVisible);
            }
            else {
                next = this.container.one('li');
            }
            if (next) {
                next.addClass('focus');
                this.auditionSH.setFocus(next.get('id'));
            }
        },
        selectAll: function(){
            SNAPPI.multiSelect.selectAll(this.container);
        },
        selectAllPages: function(){
            SNAPPI.multiSelect.selectAll(this.container);
            var ajaxParent = this.container.ancestor('div#paging-photos') || this.container.ancestor('div.photo');
            if (ajaxParent) {
				// TODO: we may need to store all-pages-selected in Session or Cookie, because 
				// we lose this attribute value on a page reload, i.e. from /home to /photos
//				ajaxParent.setAttribute('all-pages-selected', 1);
				SNAPPI.STATE.selectAllPages = true;
			}
        },
        applySelectAllPages: function(){
			if (SNAPPI.STATE.selectAllPages) this.selectAll();
        },
        clearAll: function(){
            SNAPPI.multiSelect.clearAll(this.container);
            var ajaxParent = this.container.ancestor('div#paging-photos');
            if (ajaxParent) {
//				ajaxParent.setAttribute('all-pages-selected', 0);	// deprecate, use SNAPPI.STATE.selectAllPages
				SNAPPI.STATE.selectAllPages = false;
			}
        },
		toggleRatings: function(n, force){
			var label;
			if (n && n.ynode) {
				n = n.ynode();
			} else {
				n = this.header.one('#show-ratings');
			}
			switch (force) {
				case 'show': label = 'show Ratings'; break;
				case 'hide': label = 'hide Ratings'; break;
				default: label = n.get('innerHTML'); break;
			}
			
			switch (label) {
				case 'show Ratings':
					n.set('innerHTML', 'hide Ratings');	
					SNAPPI.ratingManager.showRatings();			
					break;
				case 'hide Ratings':
					n.set('innerHTML', 'show Ratings');
					SNAPPI.ratingManager.hideRatings();
					break;
			}
		},
		toggleSubstitutes: function(n, force){
			var label;
			if (n && n.ynode) {
				n = n.ynode();
			} 			
			switch (force) {
				case 'show': label = 'show Substitutes'; break;
				case 'hide': label = 'hide Substitutes'; break;
				default: label = n.get('innerHTML'); break;
			}
			
			switch (label) {
				case 'show Substitutes':
					n.set('innerHTML', 'hide Substitutes');	
					SNAPPI.substituteController.show();
					break;
				case 'hide Substitutes':
					n.set('innerHTML', 'show Substitutes');
					SNAPPI.substituteController.hide();
					break;
			}			
		},
        applyShowSubstitutes: function(){
			this.toggleSubstitutes(SNAPPI.Y.one('#show-substitutes'), SNAPPI.STATE.showSubstitutes);
        },		
        groupSubstitutions: function(){
            var aids = [], auditionREF = [];
            // temp unique string for local processing (castingCall, auditions)
            var local_UID = new Date;
            local_UID = local_UID.getTime() + '';
            var set = this.container.all('li.selected');
            var self = this;
            var callback = {
                complete: function(subGroup){
                    self.markSubstitutes(subGroup);
                }
            };
            SNAPPI.substituteController.postSubstitutes(set, callback);
            return;
        },
        markSubstitutes: function(subGroup){
            subGroup.each(function(audition){
                if (subGroup.isBest(audition)) {
                    // render as best of subGroup 
                    for (var i in audition.bindTo) {
                        var n = audition.bindTo[i];
                        if (n.get('id').indexOf('lightbox') == -1) 
                            n.replaceClass('substitute-hide', 'substitute-show');
                    }
                }
                else {
                    // hide
                    for (var i in audition.bindTo) {
                        var n = audition.bindTo[i];
                        if (n.get('id').indexOf('lightbox') == -1) 
                            n.replaceClass('substitute-show', 'substitute-hide');
                    }
                }
            });
        }
    }
    
    
    /*
     * make global
     */
    SNAPPI.PhotoRoll = PhotoRoll;
})();
