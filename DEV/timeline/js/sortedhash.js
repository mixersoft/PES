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
 * SortedHash: a hashtable of objects with additional Array navigation methods and ordered retrieval functions
 * hashkeys should implement o.hashcode() to provide a unique id.
 *
 */
(function(){

    /*
     * dependencies
     *  - SNAPPI.Sort.compare.
     */
    var Y = SNAPPI.Y;
    
    /*
     * protected functions
     */
    var hash = function(key){
        return (key.id) ? key.id : key.hashcode ? key.hashcode() : key;
    };
    
    
    /*
     * class prototype definitions, by category
     */
    var hashtable = {
        /**
         * NOTE: does NOT change focus of sorted hash
         * @param {mixed} m, either integer or object
         */
        get: function(m){
            if (m === undefined) 
                m = this._focus;
            /*
             * m = index or object
             * NOTE: if you want to get '2' as a key, not index, use '2' or new Number(2)
             */
            try {
                if (typeof(m) == "number") {
                    return this._data[hash(this._keys[m])];
                }
                else // m is a key object, find the key index 
                {
                    return this._data[hash(m)];
                }
            } 
            catch (e) {
                // fix index out of range
                if (this._focus < 0) 
                    this._focus = 0;
                if (this._focus >= this._keys.length) 
                    this._focus = this._keys.length - 1;
                return null;
            }
        },
        item: function(m){
            return this.get(m); // alias
        },
        first: function(){
            return this.get(0);
        },
        last: function(){
            return this.get(this._keys.length - 1);
        },
        /**
         * add or replace key->value to hashtable
         * @param {Object} key
         * @param {Object} value
         * return true if hashtable changed, false if adding same value to existing key
         */
        add: function(key, value){
            var changed = false;
            value = value || key;
            // add onChange custom event to objects in sortedHash
            if (this.isDataElement && value && value.dataElementId === undefined) {
                /// ??? why don't I add the DataElement to the stack??? 
                new SNAPPI.DataElement(value, 'snappi-sh-obj');
            }
            var hashedKey = hash(key);
            // add key if new to end of _keys
            if (this._data[hashedKey] === undefined) {
                this._keys.push(key);
                changed = 'added';
            }
            // add or replace value
            if (changed) 
                this._data[hashedKey] = value;
            else {
                if (this._data[hashedKey] !== value) {
                    this._data[hashedKey] = value;
                    changed = 'replaced';
                }
            }
            return !(changed === false);
        },
        push: function(key, value){
            return this.add(key, value); // alias
        },
        remove: function(key){
            var hashedKey = hash(key);
            delete this._data[hashedKey];
            for (var i = 0; i < this._keys.length; i++) {
                if (this._keys[i] && hash(this._keys[i]) === hashedKey) {
                    this._keys.splice(i, 1); // remove element i, and renumber
                    break;
                }
            }
        },
        clear: function(){
            this._keys = [];
            this._data = {};
        },
        isEmpty: function(){
            return this._keys.length === 0;
        },
        
        hasKey: function(key){
            if (this._data[hash(key)] === undefined) 
                return false;
            return true;
        },
        indexOfKey: function(key){
            var match = hash(key);
            if (this._data[match] === undefined) 
                return -1;
            for (var i = 0; i < this._keys.length; i++) {
                if (hash(this._keys[i]) === match) {
                    return i;
                }
            }
            return -1;
        },
        getKeys: function(){
            return this._keys.slice();
        },
        hasValue: function(value){
            return this.indexOf(value) > -1;
        },
        indexOfValue: function(value){
            for (var i = 0; i < this._keys.length; i++) {
                if (this._data[hash(this._keys[i])] === value) {
                    return i;
                }
            }
            return -1;
        },
        indexOf: function(value){
            return this.indexOfValue(value);
        },
        getValues: function(){
            var v = [];
            for (var i = 0; i < this._keys.length; i++) {
                v.push(this._data[hash(this._keys[i])]);
            }
            return v;
        },
        
        count: function(){
            return this._keys.length;
        },
        length: function(){
            return this.count(); // alias
        },
        size: function(){
            return this.count(); // alias
        },
        
        slice: function(start, end){
            var count = this.count();
            var sliced = new SNAPPI.SortedHash();
            if (start < count) {
                end = (end === undefined) ? count : Math.min(end, count);
                for (var i = start; i < end; i++) {
                    sliced.add(this._keys[i], this.get(i));
                }
            }
            return sliced;
        },
        
        each: function(fn, context){
            for (var i = 0; i < this.count(); i++) {
                fn.call(context || this, this.get(i));
            }
        },
        some: function(fn){
            for (var i = 0; i < this.count(); i++) {
                var ret = fn.call(o || this, this.get(i));
                if (ret) 
                    return;
            }
        }
    };
    
    
    
    var navigation = {
        getFocus: function(){
            return this.get(this._focus);
        },
        /*
         * set Focus to index/key, or
         * if no value provided, default=last focus or 0
         */
        setFocus: function(m){
            if (typeof(m) == "number") {
                if (0 <= m && m < this._keys.length) 
                    this._focus = m;
            }
            else // m is a key object, find the key index 
            {
                if (m && this.hasKey(m)) {
                    var found = false;
                    /*
                     * NOTE: setFocus has to iterate through all keys
                     * to set the focus index, i.
                     * is there a more efficient way to do this?
                     */
                    for (var i = 0; i < this._keys.length; i++) {
                        if (hash(this._keys[i]) === hash(m)) {
                            found = true;
                            break;
                        }
                    }
                    if (found) 
                        this._focus = i;
                }
            }
            return this.getFocus();
        },
        head: function(){
            return this.setFocus(0);
        },
        tail: function(){
            return this.setFocus(this._keys.length - 1);
        },
        /**
         * next/prev
         *
         * Returns the next matching element. Returns the next element node sibling if no method provided.
         * @param {Object} fn A boolean method for testing elements. If a function is used, it receives the
         * 		current node being tested as the only argument.
         */
        next: function(fn){
            if (fn === undefined) {
                this._focus++;
                return this.getFocus();
            }
            else {
                for (var i = this._focus || 0; i < this._keys.length; i++) {
                    if (fn(this._keys[i])) {
                        this._focus = i;
                        return this.getFocus();
                    }
                }
                return null;
            }
        },
        prev: function(key){
            if (fn === undefined) {
                this._focus--;
                return this.getFocus();
            }
            else {
                for (var i = this._focus || 0; i >= 0; i--) {
                    if (fn(this._keys[i])) {
                        this._focus = i;
                        return this.getFocus();
                    }
                }
                return null;
            }
        },
        /**
         * peek ahead, does NOT change focus
         */
        peekAhead: function(key, fn){
            var i;
            if (key) {
                i = this.indexOfKey(key);
                if (i == -1) 
                    return null;
            }
            else 
                i = this._focus;
            if (fn === undefined) {
                return this.get(i++);
            }
            else {
                for (i; i < this._keys.length; i++) {
                    if (fn(this._keys[i])) {
                        return this.get(i);
                    }
                }
                return null;
            }
        },
        /**
         * peek behind, does NOT change focus
         */
        peekBehind: function(key, fn){
            var i;
            if (key) {
                i = this.indexOfKey(key);
                if (i == -1) 
                    return null;
            }
            else 
                i = this._focus;
            if (fn === undefined) {
                return this.get(i--);
            }
            else {
                for (i; i >= 0; i++) {
                    if (fn(this._keys[i])) {
                        return this.get(i);
                    }
                }
                return null;
            }
        }
    };
    
    
    
    
    var sort = {
        defaultSortFn: null,
        defaultSortCfg: null,
        setDefaultSort: function(cfg, sortNow){
            var Y = SNAPPI.Y;
            // pass by value
            this.defaultSortCfg = cfg; // should be an array
            this.defaultSortFn = SNAPPI.Sort.compare.makeSortFn(this.defaultSortCfg);
            if (sortNow) 
                this.defaultSort();
        },
        defaultSort: function(){
            this._keys.sort(this.defaultSortFn);
        },
        sort: function(cfg){
            if (cfg === undefined) 
                cfg = this.defaultSortCfg;
            this._keys.sort(SNAPPI.Sort.compare.makeSortFn(cfg));
        }
    };
    
    
    /*
     * class definition
     */
    SNAPPI.SortedHash = function(cfg){
        /*
         * private
         */
        this._keys = [];
        this._data = {};
        this._focus = 0; // number, index of this._keys for value with focus
        this.init = function(cfg){
            var Y = SNAPPI.Y;
            var _cfg = {
                'isDataElement': false
            };
            _cfg = Y.merge(_cfg, cfg);
            
            this.isDataElement = _cfg.isDataElement;
            
            /*
             * init runtime data from other modules
             */
            this.defaultSortCfg = [{
                fn: SNAPPI.Sort.compare.HashedAlphaPrefix
            }, {
                fn: SNAPPI.Sort.compare.HashedNumericSuffix
            }];
        }
        this.init(cfg);
    };
    
    //    SNAPPI.SortedHash.prototype = {};
    SNAPPI.SortedHash.prototype = Y.merge(hashtable, navigation, sort)

    
    SNAPPI.SortedHash.test = function(){
        var len = 100;
        var sh = new SNAPPI.SortedHash();
        for (var i = 0; i < len; i++) {
            var guid = Y.guid();
            sh.add({
                id: guid,
                v: i
            });
        }
        var check;
    }
    
    
    
})();
