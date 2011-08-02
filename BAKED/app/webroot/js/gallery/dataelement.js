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
 * DataElement
 *
 * implements simple hashcode and set methods for an object to be used in SortedHash
 * add onChange custom event, triggered through set() method
 *
 */
(function(){


    var Y = SNAPPI.Y;
    
    
    var _retry = [];
    /*
     * protected
     */
    var _count = 0;
    
    SNAPPI.DataElement = function(o, sType, sUid){
        /*
         * constructor
         */
        //		Y.mix(o, SNAPPI.DataElement);
        //		o.dataElementType = sType || 'dataElement';
        //		o.dataElementId = sUid || (o && o.dataElementId) || _count++;
        
        this.dataElementType = sType || 'dataElement';
        this.dataElementId = sUid || (o && o.dataElementId) || _count++;
        Y.mix(o, this);
		Y.mix(o, SNAPPI.DataElement.prototype);	// TODO: WARNING, we should augment the o CLASS with SNAPPI.DataElement.
		var check;
    };
    
    /*
     * static functions
     */
    SNAPPI.DataElement.onChange = new YAHOO.util.CustomEvent("onChange");
    
    /*
     * prototype functions, shared by all instances of object
     */
    SNAPPI.DataElement.prototype = {
        hashcode: function(){
            return this.id;
            // return this.dataElementType + '_' + this.id;
        },
        //        onChange: new YAHOO.util.CustomEvent("onChange"),
        
        
        /*
         * changing DataElement values using set will fire onChange event
         */
        set: function(o){
            var changed = false, updated = false;
            for (var p in o) {
                if (this[p] !== undefined && this[p] !== o[p]) 
                    updated = true;
                if (o[p] !== undefined && this[p] !== o[p]) 
                    changed = true;
                this[p] = o[p];
            }
            if (changed) {
                if (o.id === undefined) 
                    o.id = this.id;
                SNAPPI.DataElement.onChange.fire([this, o]);
                if (true) {
					this.updateDesktop(o);
                    this.updateServer(o);
                }
            }
        },
        /**
         * Update change on AIR local/desktop db
         */
        updateDesktop: function(o){
            if (SNAPPI.isAIR == false) 
                return;
            
            var pr = function(o){
                var out = [];
                for (var p in o) {
                    out.push(p + '->' + o[p]);
                }
                console.log(out);
            };
            pr(o);
            SNAPPI.DATASOURCE.updatePhoto(o);
        
        },
        
        /**
         * Update change on server using a PUT method
         * @param {Object} o
         */
        updateServer: function(o){
            // TODO: what about undo infomation?????
            
            //                var action = '/snappi/update_asset';
            var action = SNAPPI.cfg.uri[SNAPPI.DATASOURCE.HOST].update;
            var qs = [];
            for (var p in o) {
                if (o.hasOwnProperty(p)) {
                    qs.push('data[Fix][' + p + ']=' + o[p]);
                }
            }
            qs.push('data[Asset][datasource]=' + SNAPPI.DATASOURCE.HOST);
            var requestCfg = {
                on: {
                    success: this.handlePOSTSuccess,
                    failure: this.handlePOSTFailure
                },
                method: "POST",
                data: qs.join('&'),
                arguments: {
                    success: {
                        self: this,
                        change: o
                    }
                }
            };
            /*
             * POST request to server
             */
            var request = Y.io(action, requestCfg);
        },
        handlePOSTSuccess: function(status, resp, arguments){
            if (resp.status == 200 || resp.status == 204) {
                var self = arguments.success.self;
            }
            else {
                this.addToRetryQueue(resp, arguments.success.change);
            }
            
        },
        handlePOSTFailure: function(status, resp, arguments){
            this.addToRetryQueue(resp, arguments.success.change);
            var check;
        },
        addToRetryQueue: function(resp, change){
            if (500 <= resp.status && resp.status < 599) {
                // retry in a little bit?
                // TODO: use asynch queue??
                if (change.retry == undefined) {
                    change.retry = true;
                    _retry.push(change);
                }
            }
        }
    };
})();

