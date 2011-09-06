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
    var Y = SNAPPI.Y;
    var defaultCfg = {};
    var listeners = [];
    
    var DragDrop = function(cfg){
        this._cfg = null;
        /*
         * start DD listners
         */
        this.startListeners = function(){
            if (listeners.length) 
                return;
            var Y = SNAPPI.Y;
            /*
             * parent = Stack.thumbListEl (<UL>), or list of <LI>
             */
            //Stop the drag with the escape key
            var body = Y.one(document.body);
            var handle = Y.on('keypress', function(e){
                //The escape key was pressed
                if ((e.keyCode === 27) || (e.charCode === 27)) {
                    //We have an active Drag
                    if (Y.DD.DDM.activeDrag) {
                        //Stop the drag
                        Y.DD.DDM.activeDrag.stopDrag();
                    }
                }
            }, document.body);
            listeners.push(handle);
            
            
            //On the drag:mouseDown add the selected class
//            handle = Y.DD.DDM.on('drag:mouseDown', function(e){
//            });
//            listeners.push(handle);
            
            
            /*
             * on drag
             */
            handle = Y.DD.DDM.on('drag:start', function(e){
                //On drag start, get all the selected elements
                //Add the count to the proxy element and offset it to the cursor.
                var target = e.target.get('node');
                switch (target.get('id')) {
	                case 'snappi-zoomBox':
	                	// do no additional processing
	                	return;
	                default: // thumbnail
	                	try {
			                target.ancestor('section').addClass('selected');
			                
			                //How many items are selected
			                var count;
			                try {
			                    if (SNAPPI.STATE.selectAllPages && target.ancestor('section.gallery')) {
			                    	try {
			                    		var pr=target.ancestor('section.gallery.photo').Gallery;
			                    		count = pr.castingCall.CastingCall.Auditions.Total;
			                    	} catch (e) {
			                    		count = target.ancestor('section.gallery').all('.FigureBox.selected').size();
			                    	}
			                    }
			                    else {
			                        count = target.ancestor('section.gallery').all('.FigureBox.selected').size();
			                    }
			                } 
			                catch (e) {
			                    count = target.ancestor('section.gallery').all('.FigureBox.selected').size();
			                }
			                
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
            listeners.push(handle);
            
            
            /*
             * on drop
             */
            handle = Y.DD.DDM.on('drag:drophit', function(e){
            	var toXY;
            	var parent = e.target.get('node').ancestor('section.gallery');
                //get the imgs of the selected LIs.
                var imgs = parent.all('.FigureBox.selected > figure > img');
                var dropTarget = e.drop.get('node');
                
                /*
                 * process the drop
                 */
                dropTarget = this.processDrop(imgs, dropTarget);
                if(dropTarget.getXY){
                    /*
                     * animate drop
                     */
                    this.animateDrop(imgs, dropTarget.getXY());
                }else {
                	SNAPPI.multiSelect.clearAll(parent);
                }
                
            }, this);
            listeners.push(handle);
        };
        
        this.processDrop = function(nodeList, dropTarget){
            // note: nodeList of LI wrapping IMG, dropTarget is LI
            if (dropTarget) {
                var done = true,
                	dragNode, lastLI;
                var _clearSelected = function(nodeList){
                    nodeList.each(function(n, i, l){
                        n.ancestor('.FigureBox').removeClass('selected');
                    });
                };
                
                if (dropTarget.Lightbox) {
                	// add to Lightbox
                	var lightbox = dropTarget.Lightbox;
                	try {
                		dropTarget = lightbox.Gallery.container.all('.FigureBox').pop();
                	}catch(e){
                	}
                	done = lightbox.processDrop(nodeList, _clearSelected);
                } else if (dropTarget.ancestor('.section-header') && dropTarget.hasClass('thumbnail')){
                	// change icon
                	// TODO: move to helpers file
                	dragNode = nodeList.get('node')[0];
                	try {
	                	var controllerJSONData = PAGE.jsonData.controller;
	                	if(controllerJSONData.keyName == 'group'){
	                		
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
	                    		imgId = dragNode.ancestor('li').dom().audition.id;
	                    	

	                    	dropTarget.one('img').setAttribute('src', dragNode.get('src'));
	                    	
	                    	var uri = '/photos/set_as_group_cover/' + imgId + '/' + currentGroupId;
	            			var callback = {
	            				complete : function(id, o, args) {
	            					var check;
	            				}
	            			};
	            			SNAPPI.io.get(uri, callback, '', '', '');
	                	}
	                	else { // not in group page
	                		// check if user drags more than one img.
	                    	if(nodeList.size() > 1){
	                    		alert("please drag one picture to set your icon.");
	                    		return false;
	                    	}
	                    	
	                    	var imgId = dragNode.ancestor('li').dom().audition.id;
	                    	
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
                }
                
                //                if (dropTarget.get('parentNode').hasClass('tags')) {
                //                    // tags
                //                    SNAPPI.Tags.addList(nodeList, dropTarget);
                //                }
                //                if (dropTarget.test('section.gallery.photo > div.substitutionGroup')) {
                //                    // shots
                //                    nodeList.get('parentNode').removeClass('selected');
                //                    SNAPPI.Thumbnail.addToSubGroup(dropTarget, nodeList);
                //                }
                //                else 
                //                    // NOT .substitutionGroup!
                //                    if (dropTarget.test('section.gallery.photo > div')) {
                //                        nodeList.get('parentNode').removeClass('selected');
                //                        SNAPPI.Thumbnail.removeFromSubGroup(dropTarget, nodeList);
                //                        
                //                    }
                if (done) {
                    _clearSelected(nodeList);
                }
                return dropTarget;
            }
        };
        
        this.animateDrop = function(nodeList, toXY){
            // nodeList of IMGs
            nodeList.each(function(node){
                //Clone the image, position it on top of the original and animate it to the drop target
                var clone = node.cloneNode().set('id', '').setStyle('position', 'absolute');
                Y.one('body').appendChild(clone);
                clone.setXY(node.getXY());
                var a = new Y.Anim({
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
        };
        
        /*
         * add Drag Plugin to element
         */
        this.pluginDrag = function(node){
            //Plugin the Drag plugin
            node.plug(Y.Plugin.Drag, {
                offsetNode: false
            });
            //Plug the Proxy into the DD object
            node.dd.plug(Y.Plugin.DDProxy, {
                resizeFrame: false,
                moveOnEnd: false,
                borderStyle: 'none'
            });
            
        };
        /*
         * add Drag Plugin to element, use delegated event listener
         */        
        this.pluginDelegatedDrag = function (container, selector) {
            var delegate = new Y.DD.Delegate({
                container: container,
                nodes: selector
            });
            delegate.dd.plug(Y.Plugin.DDProxy, {
                resizeFrame: false,
                moveOnEnd: false,
                borderStyle: 'none'
            });
        };
        
        /*
         * add Drop Plugin to element
         */
        this.pluginDrop = function(node){
            //Add drop support to the albums
            node.plug(Y.Plugin.Drop);
            return node;
        };
        
        this.init = function(cfg){
			var Y = SNAPPI.Y;
            this._cfg = Y.merge(defaultCfg, cfg);
            // plugin dropppables
        };
        this.init();
    };
    SNAPPI.DragDrop = new DragDrop(); // singleton    
})();
