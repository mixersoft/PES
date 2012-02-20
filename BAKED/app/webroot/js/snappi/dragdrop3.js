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
 */
(function(){
    var _Y = null;
    SNAPPI.namespace('SNAPPI.onYready');
    SNAPPI.onYready.DragDrop = function(Y){
		if (_Y === null) _Y = Y;
		SNAPPI.DragDrop = new DragDrop();		
	}
	
    var defaultCfg = {};
    
    var DragDrop = function(cfg){
    	if (DragDrop.instance) return DragDrop.instance;
    	this.init(cfg);
    	DragDrop.instance = this;
    };
    
    DragDrop.prototype = {
        init : function(cfg){
            this._cfg = _Y.merge(defaultCfg, cfg);
            // this.startListeners();
        },    
        listen: {},
        startListeners : function(){
            /*
             * parent = Stack.thumbListEl (<UL>), or list of <LI>
             */
            //Stop the drag with the escape key
            var body = _Y.one(document.body);
            if (!this.listen['keypress']) {
	            this.listen['keypress'] = _Y.on('keypress', function(e){
	                //The escape key was pressed
	                if ((e.keyCode === 27) || (e.charCode === 27)) {
	                    //We have an active Drag
	                    if (_Y.DD.DDM.activeDrag) {
	                        //Stop the drag
	                        _Y.DD.DDM.activeDrag.stopDrag();
	                    }
	                }
	            }, document.body);
            }
            
            
            //On the drag:mouseDown add the selected class
//            handle = _Y.DD.DDM.on('drag:mouseDown', function(e){
//            });
//            listeners.push(handle);
            
            
            /*
             * on drag
             */
            if (!this.listen['drag:start']) {
	            this.listen['drag:start']= _Y.DD.DDM.on('drag:start', function(e){
	                //On drag start, get all the selected elements
	                //Add the count to the proxy element and offset it to the cursor.
	                var target = e.target.get('node');
	                switch (target.get('id')) {
		                case 'snappi-zoomBox':
		                	// do no additional processing
		                	return;
		                default: // thumbnail
		                	try {
		                		
				                target.ancestor('.FigureBox').addClass('selected');
				                
				                // try {
				                	// var CSSID = 'contextmenu-photoroll-markup';
				                	// var cmenu = SNAPPI.MenuAUI.find[CSSID];
				                	// if (cmenu.get('disabled')==false) SNAPPI.MenuAUI.toggleEnabled(CSSID, false)
				                // } catch(e) {
				                // }
				                
				                
				                //How many items are selected
				                var g, count;
			                	g = target.ancestor('section.gallery.photo').Gallery;
			                    if (SNAPPI.STATE.selectAllPages && target.ancestor('section.gallery')) {
			                    	try {
			                    		count = g.castingCall.CastingCall.Auditions.Total;
			                    	} catch (e) { 
			                    		count = g.node.all('.FigureBox.selected').size();
			                    	}
			                    }
			                    else {
			                        count = g.node.all('.FigureBox.selected').size();
			                    }
				                e.target.Gallery = g; // reference for drag:drophit
				                
				                //Set the style on the proxy node, the count badge
				                e.target.get('dragNode').setStyles({
				                    height: '25px',
				                    width: '25px'
				                }).set('innerHTML', '<span>' + count + '</span>');
				                
				                //Offset the dragNode
				                e.target.deltaXY = [25, 5];
				                
				                
		                	} catch (ex) {
		                		e.halt();
		                	}
	                }
	                
	                // activate targets
	            }, this);
            }
            
            
            /*
             * on drop
             */
            if (!this.listen['drag:drophit']) {
	            this.listen['drag:drophit'] = _Y.DD.DDM.on('drag:drophit', 
	            function(e){
	            	try {
	            		/*
	            		 * drop selected from Gallery
	            		 */
		            	var toXY, gallery, imgs, dropTarget;
		            	gallery = e.target.Gallery;
		            	imgs = gallery.container.all('.FigureBox.selected > figure > img');
		            	dropTarget = e.drop.get('node');
		                /*
		                 * process the drop
		                 */
		                dropTarget = this.processDrop(imgs, dropTarget);
		                if(dropTarget.getXY){
		                    this.animateDrop(imgs, dropTarget.getXY());
		                }
		                // gallery.clearAll();		// clearAll in processDrop()
	            	} catch (e) {}
	            }, this);
			}
        },	
        processDrop : function(nodeList, dropTarget){
            // note: nodeList ".FigureBox.selected > figure > img" , dropTarget is .drop
            var done = false,
            	dragNode, lastLI;
            var _clearSelected = function(nodeList){
                nodeList.each(function(n, i, l){
                    n.ancestor('.FigureBox').removeClass('selected');
                });
            };
            
            if (dropTarget.Lightbox || (dropTarget.Gallery && dropTarget.Gallery._cfg.type=='Lightbox')) {
            	// try {
            		// dropTarget = dropTarget.lightbox.Gallery.container.all('.FigureBox').pop();
            	// }catch(e){
            	// }
            	done = SNAPPI.Lightbox.instance.processDrop(nodeList, _clearSelected);
            } else if (dropTarget.Gallery) {
            	// still need to initialize shot-gallery.Gallery
            	done = dropTarget.Gallery.processDrop(nodeList, _clearSelected);
            } 
			if (!done) Helpers.sectionBadge_ProcessDrop(dropTarget, nodeList);
			if (!done) Helpers.tags_ProcessDrop(dropTarget, nodeList);
			if (!done) Helpers.shotGroup_ProcessDrop(dropTarget, nodeList);
            if (done) {
                _clearSelected(nodeList);
            }
            return dropTarget;
        },
        animateDrop : function(nodeList, toXY){
            // nodeList of IMGs
            nodeList.each(function(node){
                //Clone the image, position it on top of the original and animate it to the drop target
                var clone = node.cloneNode().set('id', '').setStyle('position', 'absolute');
                _Y.one('body').appendChild(clone);
                clone.setXY(node.getXY());
                var a = new _Y.Anim({
                    node: clone,
                    to: {
                        height: 20,
                        width: 20,
                        opacity: 0,
                        top: toXY[1],
                        left: toXY[0]
                    },
                    from: {
                        width: node.get('offsetWidth'),
                        height: node.get('offsetHeight')
                    },
                    duration: 0.5
                });
                var detach = a.on('end', function(){
                    this.remove();
                    detach.detach();
                }, clone);
                a.run();
            });
        },
        /*
         * add Drag Plugin to element
         */
        pluginDrag : function(node){
            //Plugin the Drag plugin
            node.plug(_Y.Plugin.Drag, {
                offsetNode: false
            });
            //Plug the Proxy into the DD object
            node.dd.plug(_Y.Plugin.DDProxy, {
                resizeFrame: false,
                moveOnEnd: false,
                borderStyle: 'none'
            });
            var check;
        },
        /*
         * add Drag Plugin to element, use delegated event listener
         */        
        pluginDelegatedDrag : function (container, selector) {
            var delegate = new _Y.DD.Delegate({
                container: container,
                nodes: selector
            });
            delegate.dd.plug(_Y.Plugin.DDProxy, {
                resizeFrame: false,
                moveOnEnd: false,
                borderStyle: 'none'
            });
            var check;
        },
        /*
         * add Drop Plugin to element
         */
        pluginDrop : function(node){
            //Add drop support to the albums
            node.plug(_Y.Plugin.Drop);
            return node;
        }
	}
	
	var Helpers = function(){};	
    /**
     * drop thumbnail on section-header badge photo, sets cover photo for current item
     */
    Helpers.sectionBadge_ProcessDrop = function(dropTarget, nodeList) {
    	if (dropTarget.ancestor('.item-header') && dropTarget.hasClass('thumbnail')) {
	      	dragNode = nodeList.get('node')[0];
	    	try {
	        	var controllerJSONData = SNAPPI.STATE.controller;
	        	if (controllerJSONData.name == 'Groups') {
	        		 
	            	if(nodeList.size() > 1){  // check if user drags more than one img.
	            		alert("please drag one picture to set your icon. use ctrl to un-select photo");
	            		return false;
	            	}
	            	var isOwner = controllerJSONData.isOwner;
	            	if(!isOwner){  // check if user is the owner
	            		alert("you don't have permission to change cover of this group");
	            		return false;
	            	}
	            	
	            	var currentGroupId = controllerJSONData.xhrFrom.uuid,
	            		imgId = dragNode.ancestor('.FigureBox').uuid;
	            	
	
	            	dropTarget.one('img').setAttribute('src', dragNode.get('src'));
	            	
	            	var uri = '/photos/set_as_group_cover/' + imgId + '/' + currentGroupId;
	    			var callback = {
	    				complete : function(id, o, args) {
	    					var check;
	    				}
	    			};
	    			SNAPPI.io.get(uri, callback, '', '', '');
	        	} else { // not in group page
	        		// check if user drags more than one img.
	            	if(nodeList.size() > 1){
	            		alert("please drag one picture to set your icon.");
	            		return false;
	            	}
	            	
	            	var imgId = dragNode.ancestor('.FigureBox').uuid;
	            	
	            	dragNode = nodeList.get('node')[0];
	            	dropTarget.one('img').setAttribute('src', dragNode.get('src'));
	            	
	            	var uri = '/photos/set_as_photo/' + imgId;
	    			var callback = {
	    				complete : function(id, o, args) {
	    					var check;
	    				}
	    			};
	    			SNAPPI.io.get(uri, callback, '', '', '');
	        	}
	    	} catch (e) {}  
	    	return true;
	    } else return false;  	
    };	
	    // legacy
    Helpers.tags_ProcessDrop = function(dropTarget, nodeList){
		if(dropTarget.get('parentNode').hasClass('tags')) {
			// tags
			SNAPPI.Tags.addList(nodeList, dropTarget);
			return true;
		} else
			return false;
	};
    // legacy    
    Helpers.shotGroup_ProcessDrop = function(dropTarget, nodeList){
		if (dropTarget.test('section.gallery.photo > div.substitutionGroup')) {
			nodeList.get('parentNode').removeClass('selected');
			SNAPPI.Thumbnail.addToSubGroup(dropTarget, nodeList);
		} else if (dropTarget.test('section.gallery.photo > div')) {
			nodeList.get('parentNode').removeClass('selected');
			SNAPPI.Thumbnail.removeFromSubGroup(dropTarget, nodeList);
		
		}
	};
	
})();    


    