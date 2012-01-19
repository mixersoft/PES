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
 * Timeline adapted from http://www.simile-widgets.org/timeplot/
 *
 */
(function(){

    /*
     * protected methods and variables
     */
    var Y = SNAPPI.Y;
    
    
    var formatDateAsISO = SNAPPI.util.formatDateAsISO;
    var parseExifDateTimeString = SNAPPI.util.parseExifDateTimeString;
    var parseCameraTimeAsUTC = function(sDateTime){
        return parseExifDateTimeString(sDateTime, true);
    };
    
    
    /**
     *
     * @param string sDate
     * @param int timeSpanInMinutes in minutes of time slot
     *
     * NOTES: exifDateTaken is given without timezone data
     * 		- assume exifDateTaken is always in UTC time
     * 		- i.e. camera time == UTC
     *
     *
     */
    var _tzOffsetMins = new Date().getTimezoneOffset();
    var _getTimeslot = function(sDate, timespanDurationInMinutes){
        timespanDurationInMinutes = timespanDurationInMinutes || 60;
        var dateLocalTz = parseCameraTimeAsUTC(sDate); // sDate is EXIF datetime, assumes UTC timezone, converts to local timezone
        var unixtimeLocalTz = Math.round(dateLocalTz.getTime() / 1000);
        var unixtimeUTC = unixtimeLocalTz + _tzOffsetMins * 60;
        var tsIndexUTC = Math.floor((unixtimeUTC / 60) / timespanDurationInMinutes);
        //        var tsDateUTC = new Date(tsIndexUTC * timespanDurationInMinutes * 60 * 1000);
        var tsIndexLocalTz = Math.floor((unixtimeLocalTz / 60) / timespanDurationInMinutes);
        var tsDateLocalTz = new Date(tsIndexLocalTz * timespanDurationInMinutes * 60 * 1000);
        return {
            duration: timespanDurationInMinutes, // timespan interval in minutes
            index: tsIndexUTC, // count of timespan intervals since epoch.
            tsDateLocalTz: tsDateLocalTz, // javascript DATE object for timeslot adjusted to local timezone
            tsLabelUTC: SNAPPI.util.formatDateAsISO_UTC(tsDateLocalTz), // *Actual* EXIF time expressed as ISO date, (not adjusted to local timezone)
            unixtimeUTC: unixtimeUTC,
            _origDatStr: sDate // ignore
        };
    };
    
    var _getDateFromTimeslot = function(tsIndexUTC, timespanDurationInMinutes, asUTC){
        timespanDurationInMinutes = timespanDurationInMinutes || 60;
        if (!asUTC) 
            tsIndexUTC -= _tzOffsetMins / timespanDurationInMinutes;
        return new Date(tsIndexUTC * timespanDurationInMinutes * 60 * 1000);
    };
    
    
    /**
     * Timeline constructor
     * @param {Object} cfg
     */
    var Timeline = function(cfg){
        var _cfg = {
            containerId: 'timeline',
            previewId: 'timeline-preview',
            resizeTimerId: null,
            timeplot: null
        };
        /*
         * private properties and methods
         */
        // initializer
        _cfg = Y.merge(_cfg, cfg);
        this.init(_cfg);
    };
    
    
    /*
     * static methods
     */
    Timeline.showPreview = function(ev){
        var target = ev.target.dom();
        var auditions = target.auditions;
        var container = Y.one('#' + this.cfg.previewId);
        
        var auditionREFs = target.auditions;
        var parentNode = container;
        if (auditionREFs.length) {
            var cfg = {
                auditions: auditionREFs,
                parentNode: parentNode,
                name: 'Timeline Preview',
                label: 'Photo(s) taken: ' + target.timeslot,
                thumbPanelEl: 'timeline-thumb-panel',
                thumbListEl: 'timeline-thumb-list'
            };
            var stack = SNAPPI.Stack.renderSimple(cfg);
            stack.setFocus(null, true);
            parentNode.setStyle('display', 'block');
        };
        SNAPPI.Rating.toggleShowSizeByRating(false);
        var check;
        return;
    };
    /*
     * class methods
     */
    // add methods to prototype
    Timeline.prototype = {
        /*
         * general class methods
         */
        init: function(cfg){
            this.cfg = cfg;
            this.startListeners();
        },
        startListeners: function(){
            var canvas = Y.one('#' + this.cfg.containerId);
            if (canvas) 
                canvas.delegate('mouseenter', Timeline.showPreview, 'div.plot-point', this);
        },
        load: function(){
        },
        loadJs: function(fnContinue){
        },
        create: function(){
            /****************************
             * ENTRY POINT FOR MODULE
             */
            var cfg = {
                minRating: 1,
                duration: 15, // timespan duration, in minutes
                squashAfter: 4 // compress timeslots after N blank
            };
            var minRating = 1;
            var tsdata = this.mapToTimeslot(SNAPPI.auditions.auditionSH, cfg);
            var tsdata = this.countRatingsByTimeslot(SNAPPI.auditions.auditionSH, cfg);
            
            var paddedTsData = this.padTs(tsdata);
            //            paddedTsData = this.squashTs(paddedTsData, cfg);
            this.plot(paddedTsData, cfg);
            var check;
        },
        resize: function onResize(){ // change to Y.Later???
            if (resizeTimerID == null) {
                this.cfg.resizeTimerID = window.setTimeout(function(){
                    this.cfg.resizeTimerID = null;
                    this.timeplot.repaint();
                }, 100);
            }
        },
        /*
         * plot padded array of Timeslots
         */
        plot: function(paddedTs, cfg){
            var _cfg = {
                duration: 5, // timeslot duration, in minutes
                squashAfter: 5 // timeslots
            };
            _cfg = Y.merge(_cfg, cfg);
            var canvas = Y.one('#' + this.cfg.containerId);
            canvas.set('innerHTML', ''); // clear canvas
            var w, h, x, y, yScale, left, top, topleft, node;
            yScale = 5 + 2;
            w = canvas.get('clientWidth') || canvas.ancestor('div.yui-content').get('clientWidth') - 40; // margin
            h = canvas.get('clientHeight');
            left = canvas.get('offsetLeft');
            top = canvas.get('offsetTop');
            
            for (var i = 0; i < paddedTs.length; i++) {
                var tsOrNum = paddedTs[i];
                if (isNaN(tsOrNum)) { // plot point
                    for (var r = 1; r <= 5; r++) {
                        var count = tsOrNum[r].length;
                        if (count == 0) 
                            continue;
                        x = left + i / paddedTs.length * w;
                        y = top + (h * (1 - r / yScale));
                        // topleft = 'top:' + y + 'px; left:' + x + 'px;';
                        topleft = 'left:' + x + 'px;';
                        node = Y.Node.create("<div class='plot-point r" + r + "' style='" + topleft + "'>" + count + "</div>");
                        node.dom().auditions = tsOrNum[r];
                        var date = tsOrNum[0].tsDateLocalTz;
                        node.dom().timeslot = SNAPPI.util.formatUTCDateAsStringNoTZ(date);
                        canvas.append(node);
                    }
                }
                else {
                    // padded slots
                    if (tsOrNum > _cfg.squashAfter) {
                        // draw squashed slot
                        x = left + i / paddedTs.length * w;
                        topleft = 'top:' + top + 'px; left:' + x + 'px; height:' + h + 'px;';
                        node = Y.Node.create("<div class='plot-skipped' style='" + topleft + "'>" + tsOrNum + "</div>");
                        canvas.append(node);
                    }
                }
            }
        },
        squashTs: function(paddedData, cfg){
            var _cfg = {
                squashAfter: 5 // timeslots
            };
            _cfg = Y.merge(_cfg, cfg);
            var limit = _cfg.squashAfter;
            var end = false;
            for (var i = paddedData.length - 1; i >= 0; i--) { // splice backwards through array
                if (end == false && isNaN(paddedData[i]) == false && paddedData[i] > limit) {
                    end = i;
                }
                if (end && (paddedData[i] <= limit || isNaN(paddedData[i]))) {
                    paddedData.splice(i + 1, end - i);
                    paddedData[i] = end - i;
                    end = false;
                }
            }
            return paddedData;
        },
        
        /*
         * pad timeslots
         */
        padTs: function(data){
            var firstBlank = 9999999999, lastBlank = 0, max = [], blank = 0, padded = new Array();
            for (var i in data) {
                firstBlank = Math.min(firstBlank, i);
                lastBlank = Math.max(lastBlank, i);
                //                max[1] = Math.max(max[1], data[i][1].length);
                //                max[2] = Math.max(max[2], data[i][2].length);
                //                max[3] = Math.max(max[3], data[i][3].length);
                //                max[4] = Math.max(max[4], data[i][4].length);
                //                max[5] = Math.max(max[5], data[i][5].length);
            }
            for (var j = firstBlank; j <= lastBlank; j++) {
                if (data.hasOwnProperty(j)) {
                    padded.push(data[j]);
                    blank = 0;
                }
                padded.push(++blank);
            }
            return padded;
        },
        /*
         * Timeline data manipulation methods
         * 	actually, this process should be called "reduce", i.e. reduceToTimeslot
         */
        mapToTimeslot: function(sh, cfg){
            var _cfg = {
                minRating: 1,
                duration: 5, // deprecate
                period: 5
            };
            _cfg = Y.merge(_cfg, cfg);
            _cfg.period = _cfg.period || _cfg.duration; // deprecate. use period instead of duration
            sh = sh || SNAPPI.StackManager.getFocus()._dataElementSH;
            /*
             * create raw data for plotting along timeline
             */
            var dataRows = [];
            legend = {};
            var i, o, timeslot, max = 0, min = 999999999;
            // sort by time
            sh.sort([SNAPPI.Stack.sort.TIME, SNAPPI.Stack.sort.RATING, SNAPPI.Stack.sort.NAME]);
            for (i = 0; i < sh.count(); i++) {
                o = sh.get(i);
                if (o.rating == null || o.rating < _cfg.minRating) 
                    continue; // simple rating filter
                timeslot = _getTimeslot(o.exif_DateTimeOriginal, _cfg.period);
                //       timeslot = {
                //            duration: timeSpanInMinutes, // timespan interval in minutes
                //            index: tsIndex, // count of timespan intervals since epoch.
                //            tsDateLocalTz: tsDateLocalTz, // EXIF time adjusted to local timezone
                //            tsLabelUTC: SNAPPI.util.formatDateAsISO_UTC(tsDateLocalTz), // *Actual* EXIF time expressed as ISO date, (not adjusted to local timezone)
                //            _origDatStr: sDate, // ignore
                //            _serverUnixtime: Math.round(utc.getTime() / 1000) // ignore
                //        };	
                
                var row = [o.id, timeslot.index, o.exif_DateTimeOriginal, o.rating, o.urlbase + o.src];
                dataRows.push(row);
                min = Math.min(timeslot.index, min);
                max = Math.max(timeslot.index, max);
                //                legend[timeslot.index] = timeslot.tsLabelUTC;
                legend[timeslot.index] = timeslot.tsDateLocalTz;
            }
            var _padLegend = function(legend, min, max, period){
                var sortedLegend = {};
                for (var i = min; i < max + 1; i++) {
                    if (legend[i] == undefined) {
                        sortedLegend[i] = _getDateFromTimeslot(i, period);
                    }
                    else 
                        sortedLegend[i] = legend[i];
                }
                return sortedLegend;
            };
            
            legend = _padLegend(legend, min, max, _cfg.period);
            
            return {
                range: {
                    min: min,
                    max: max
                },
                duration: _cfg.period, // deprecate
                period: _cfg.period,
                legend: legend,
                rows: dataRows
            };
        },
        countRatingsByTimeslot: function(sh, cfg){
            var _cfg = {
                minRating: 1,
                duration: 5
            };
            _cfg = Y.merge(_cfg, cfg);
            sh = sh || SNAPPI.StackManager.getFocus()._dataElementSH;
            /*
             * create data for timeline
             */
            var dataRows = {};
            var i, o, timeslot;
            for (i = 0; i < sh.count(); i++) { // should we sort?
                o = sh.get(i);
                if (o.rating == null || o.rating < _cfg.minRating) 
                    continue; // simple rating filter
                timeslot = _getTimeslot(o.exif_DateTimeOriginal, _cfg.duration);
                if (!dataRows[timeslot.index]) {
                    delete timeslot._origDatStr;
                    delete timeslot._serverUnixtime;
                    dataRows[timeslot.index] = [timeslot, [], [], [], [], []]; // rating 1-5
                }
                dataRows[timeslot.index][o.rating].push(o.id);
            }
            //            var output = [];
            //            for (i in dataRows) {
            //                output.push(dataRows[i]);
            //            }
            //            var asCDF = '';
            //            for (i in dataRows) {
            //                asCDF += dataRows[i][0].tsLabelUTC + ',' + dataRows[i].slice(1).join(',') + " /n";
            //            }
            //            var check = asCDF;
            return dataRows;
        },
        getDateFromTimeslot: _getDateFromTimeslot
    };
    /*
     * Publish Global Reference
     */
    SNAPPI.timeline = new Timeline({
        containerId: 'timeline'
    }); // as singleton
    // NOTE: doesn't work if Y must GET yui3 scripts asynchronously. Y not in scope during init()	
    var check;
    
    
})();
