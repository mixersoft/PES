/*
 * SNAPPI dojo timeline module
 */
(function(){

    SNAPPI.namespace('SNAPPI.dojo');
    
    dojo.require("dijit.form.HorizontalSlider");
    dojo.require("dijit.form.HorizontalRule");
    dojo.require("dijit.form.HorizontalRuleLabels");
    dojo.require("dojo.parser"); // scan page for widgets
    dojo.require("dojox.charting.Chart2D");
    dojo.require("dojox.charting.themes.PlotKit.purple");
    
    dojo.require("dojox.lang.functional.object");
    dojo.require("dojox.charting.themes.Distinctive");
    dojo.require("dojox.charting.themes.GreySkies");
    
    dojo.require("dojox.charting.action2d.Highlight");
    //dojo.require("dojox.charting.action2d.Magnify");
    //dojo.require("dojox.charting.action2d.MoveSlice");
    //dojo.require("dojox.charting.action2d.Shake");
    dojo.require("dojox.charting.action2d.Tooltip");
    
    //    var df = dojox.lang.functional;
    
    dojo.addOnLoad(function(e){
        // dojo libs loaded
        SNAPPI.dojo.loaded = true;
    });
    
    /*
     * format labels
     * 		display in UTC timezone
     */
    var _lpad = function(n){
        return n.toString().length == 1 ? '0' + n : n;
    };
    var _formatDateLabels = function(date, fmt){
        //		var str = date.getUTCFullYear() + '-' + _lpad(date.getUTCMonth() + 1) + '-' + _lpad(date.getUTCDate()) + 'T' + _lpad(date.getUTCHours()) + ':' + _lpad(date.getUTCMinutes()) + ':' + _lpad(date.getUTCSeconds());
        
        switch (fmt) {
            case "MM":
                // format showing minutes
                o = {
                    date: _lpad(date.getUTCMonth() + 1) + '/' + _lpad(date.getUTCDate()) + '/' + date.getUTCFullYear().toString().substr(2),
                    time: _lpad(date.getUTCHours()) + ':' + _lpad(date.getUTCMinutes())
                };
                break;
            default:
                o = {
                    date: date.getUTCFullYear() + '-' + _lpad(date.getUTCMonth() + 1) + '-' + _lpad(date.getUTCDate()),
                    time: _lpad(date.getUTCHours()) + ':' + _lpad(date.getUTCMinutes()) + ':' + _lpad(date.getUTCSeconds())
                };
                break;
        }
        return o;
    };
    
    var MAX_RATING = 5;
    
    
    var TimelineChart = function(cfg){
        Y = SNAPPI.Y;
        var defaultCfg = {
            scaleX: 4,
            scaleY: 1,
            offsetX: 9999999,
            offsetY: 0
        };
        
        
        this.clickInit = null;
        this.chart = null;
        this.movable = null;
        this.dataset = {};
        
        this.init = function(cfg){
            var chart, moveable;
            this.cfg = Y.merge(defaultCfg, cfg);
            if (this.cfg.dataset) 
                this.setDataset(this.cfg.dataset);
            this.position = Y.merge(this.cfg);
            
            // create chart object
            this.chart = new dojox.charting.Chart2D("rating");
            this.chart.setTheme(dojox.charting.themes.PlotKit.purple);
            this.chart.addPlot("default", {
                type: "Bubble",
                shadows: {
                    dx: 2,
                    dy: 2,
                    dw: 2
                }
            });
            // add plot actions
            var ratingHightlight = new dojox.charting.action2d.Highlight(this.chart, 'default');
            var ratingTooltip = new dojox.charting.action2d.Tooltip(this.chart, "default");
            
            this.chart.addPlot("grid", {
                type: "Grid",
                hMinorLines: true
            });
            
            // volume chart
            this.chartVol = new dojox.charting.Chart2D("volume");
            this.chartVol.setTheme(dojox.charting.themes.PlotKit.purple);
            this.chartVol.addPlot("default", {
                type: "MarkersOnly",
                //                lines: false,
                //                markers: false,
                gap: 1,
                hAxis: 'volX',
                vAxis: 'volY'
            });
            
            // add plot actions
            var hiliteVol = new dojox.charting.action2d.Highlight(this.chartVol, 'default', {
                highlight: '#FF0000'
            });
            var tooltipVol = new dojox.charting.action2d.Tooltip(this.chartVol, "default");
            
            
            this.chartVol.addPlot("grid", {
                type: "Grid",
                hAxis: 'volX',
                vAxis: 'volY',
                hMinorLines: true
            });
        };
    };
    
    
    
    
    TimelineChart.prototype = {
        setDataset: function(dataset){
            this.dataset = dataset;
        },
        setXAxis: function(cfg){
        
            /*
             * format x axis time labels
             */
            var labels = this.dataset.legend;
            var series = [];
            var label, i = this.dataset.range.min;
            for (var i in labels) {
                if (labels[i]) {
                    label = _formatDateLabels(labels[i], cfg.labelFmt);
                    //                    label = labels[i].replace('T', '<br>');
                    // label = labels[i].substr(labels[i].indexOf('T'));
                    series.push({
                        value: parseInt(i),
                        text: label.date + "<br>" + label.time
                    });
                }
            }
            this.xAxisLabels = series;
            
            // add X axis
            this.chart.addAxis("x", {
                labels: this.xAxisLabels,
                min: this.dataset.range.min,
                max: this.dataset.range.max,
                //                majorTickStep: Math.min(10,Math.round(this.position.scaleX)),
                majorTick: {
                    stroke: "black",
                    length: 3
                },
                minorTicks: 1,
                majorLabels: true,
                minorLabels: false
            });
            
            // add volume X axis
            var plotCount = this.dataset.range.max - this.dataset.range.min + 1;
            this.chartVol.addAxis("volX", {
                labels: this.xAxisLabels,
                //                fixUpper: 'major',
                //                fixLower: 'major',
                //                leftBottom: false,
                min: this.dataset.range.min,
                max: this.dataset.range.max,
                majorTickStep: Math.round(plotCount / 10),
                majorTick: {
                    stroke: "black",
                    length: 10
                },
                minorTicks: 1,
                majorLabels: true,
                minorLabels: false
            });
            
        },
        setYAxis: function(minRating){
            var i = minRating || 0;
            
            // add Y axis
            this.chart.addAxis("y", {
                vertical: true,
                min: 0,
                max: MAX_RATING + 1,
                maxLabelSize: 30,
                labelFunc: this.yAxisRatingLabelFunc,
                majorTick: {
                    stroke: "black",
                    length: 3
                },
                minorTicks: false
            });
            
            // add volume Y axis
            this.chartVol.addAxis("volY", {
                fixUpper: 'major',
                includeZero: true,
                vertical: true,
                //                leftBottom: false,
                //                min: 0,
                //                max: 20,
                maxLabelSize: 30,
                labelFunc: function(i){
                    return i + '';
                },
                majorTickStep: Math.ceil(this.plotStats.volume.max / 10 / 4) * 10,
                majorTick: {
                    stroke: "black",
                    length: 3
                },
                minorTicks: false
            });
            var check;
        },
        parseDataset: function(dataset){
            //            var series, volume, ts, rows = dataset.rows;
            //            for (var i in rows) {
            //            }
        },
        getVolumeSeries: function(rows){
            var volume = [];
            // markers
            for (var i in rows) {
                volume.push({
                    x: i,
                    y: rows[i]
                });
            }
            //			// columns chart, must be padded for dojo < v1.5
            //			for (var i in this.dataset.legend) {
            //				if (rows[i]) volume.push(rows[i]);
            //				else volume.push(0); 
            //			}
            return volume;
        },
        groupByTimeslot: function(){
            var start = (new Date().valueOf());
            
            /*
             * summarize plot for each timeslot rating
             * calculate size for plot
             */
            var summaryPassRating = function(series, rating){
                var plotSeries = [];
                if (series[rating]) {
                    var rows = series[rating];
                    var count, size, ts;
                    for (ts in rows) {
                        count = rows[ts].length;
                        size = (Math.LOG10E * Math.log(count) * 3 + 1) * 0.3;
                        plotSeries.push({
                            x: parseInt(ts),
                            y: rating,
                            size: size,
                            tooltip: count + (count > 1 ? " photos" : " photo")
                        });
                    }
                }
                return plotSeries;
            };
            
            /*
             * first pass, group dataset into timeslot-ratings
             * get volume
             */
            var series = {}, stats = {
                volume: {},
                rating: {}
            };
            var volume = {};
            var ID = 0, TIMESLOT = 1, DATETAKEN = 2, RATING = 3, SRC = 4;
            var ts, rows = this.dataset.rows;
            var oldTs, count, size;
            
            for (var i in rows) {
                var rating = rows[i][RATING];
                ts = rows[i][TIMESLOT];
                if (series[rating] == undefined) {
                    series[rating] = {
                        plot: []
                    };
                }
                
                if (series[rating][ts] == undefined) {
                    series[rating][ts] = [];
                }
                series[rating][rows[i][TIMESLOT]].push(parseInt(i));
                if (volume[ts] == undefined) {
                    volume[ts] = 1;
                }
                else 
                    volume[ts]++;
                stats.volume.max = Math.max(stats.volume.max || 0, volume[ts]);
                stats.volume.min = Math.min(stats.volume.min || 9999999999, volume[ts]);
            }
            for (var r in series) {
                series[r].plot = summaryPassRating(series, r);
            }
            this.series = series;
            this.volume = volume;
            this.plotStats = stats;
        },
        plotSeries: function(rating, color, label){
            try {
                label = label || "Rating=" + rating;
                color = color || 'black';
                this.chart.addSeries(label, this.series[rating].plot, {
                    stroke: {
                        color: color
                    },
                    //				marker: SQUARE,
                    fill: color
                
                });
            } 
            catch (e) {
            }
        },
        plotVolume: function(label){
            try {
                label = label || "Volume";
                color = 'black';
                var data = this.getVolumeSeries(this.volume);
                this.chartVol.addSeries(label, data, {
                    //                    plot: 'volume',
                    stroke: {
                        color: color
                    },
                    fill: color
                });
            } 
            catch (e) {
            }
        },
        render: function(){
            this.chart.setAxisWindow("x", this.position.scaleX, this.position.offsetX);
            //			this.chart.setAxisWindow("x", 1, 0);
            this.chartVol.setAxisWindow("volX", 1, 0);
            this.chart.render();
            var check = this.chart.offsets;
            //            var naturalPadding = {
            //                l: 59,
            //                r: 32
            //            }; // natural padding for chart (rating)
            //            this.chartVol.margins.l = this.chart.offsets.l - naturalPadding.l + 10;
            //            this.chartVol.margins.r = this.chart.offsets.r - naturalPadding.r + 10;
            this.chartVol.render();
            
            if (!this.chartVol.plotX) {
                this.chartVol.plotX = SNAPPI.Y.one('div#volume svg > rect').getXY().shift();
            }
            
            
            // dojo.connect(dijit.byId("scaleXSlider"), "onChange", scaleXEvent);
            // dojo.connect(dijit.byId("scaleYSlider"), "onChange", scaleYEvent);
            // dojo.connect(dijit.byId("offsetXSlider"), "onChange", offsetXEvent);
            // dojo.connect(dijit.byId("offsetYSlider"), "onChange", offsetYEvent);
			
            var ratingPlotArea = this.chart.surface.rawNode.children[1];
            dojo.connect(ratingPlotArea, "onmousedown", this, this.onMouseDown);
            dojo.connect(ratingPlotArea, "onmousemove", this, this.onMouseMove);
            dojo.connect(ratingPlotArea, "onmouseup", this, this.onMouseUp);
            
            this.chartVol.connectToPlot('default', this, this.volumeOnMouse);
            this.chart.connectToPlot('default', this, this.ratingOnMouse);
            dojo.connect(dojo.byId("volume"), "onclick", this, this.onVolumeClick);
            
            dojo.connect(ratingPlotArea, "onmousemove", this, this.showRatingHighlight);
            dojo.connect(ratingPlotArea, "onmouseout", this, this.hideRatingHighlight);
            
        },
        reflect: function(){
            dojox.lang.functional.forIn(this.chart.axes, function(axis){
                var scale = axis.getWindowScale(), offset = Math.round(axis.getWindowOffset() * axis.getScaler().bounds.scale);
                if (axis.vertical) {
                    //                    this.position.scaleY = scale;
                    //                    this.position.offsetY = offset;
                }
                else {
                    this.position.scaleX = scale;
                    this.position.offsetX = offset;
                    //console.log("offset="+offset+", windowScale="+axis.getWindowOffset()+", axis.getScaler().bounds.scale "+axis.getScaler().bounds.scale);						
                }
            }, this);
            //            setTimeout(function(){
            //                dijit.byId("scaleXSlider").setValue(this.position.scaleX);
            //                dijit.byId("offsetXSlider").setValue(this.position.offsetX);
            //                dijit.byId("scaleYSlider").setValue(this.position.scaleY);
            //                dijit.byId("offsetYSlider").setValue(this.position.offsetY);
            //            }, 25);
        },
        update: function(){
            this.chart.setWindow(this.position.scaleX, this.position.scaleY, this.position.offsetX, this.position.offsetY).render();
            TimelineChart.prototype.reflect.call(this);
        },
        scaleXEvent: function(value){
            this.position.scaleX = value;
            dojo.byId("scaleXValue").innerHTML = value;
            TimelineChart.prototype.update();
        },
        scaleYEvent: function(value){
            this.position.scaleY = value;
            dojo.byId("scaleYValue").innerHTML = value;
            TimelineChart.prototype.update();
        },
        offsetXEvent: function(value){
            this.position.offsetX = value;
            dojo.byId("offsetXValue").innerHTML = value;
            TimelineChart.prototype.update();
        },
        offsetYEvent: function(value){
            this.position.offsetY = value;
            dojo.byId("offsetYValue").innerHTML = value;
            TimelineChart.prototype.update();
        },
        onMouseDown: function(e){
            this.clickInit = {
                x: e.clientX,
                y: e.clientY,
                ox: this.position.offsetX,
                oy: this.position.offsetY
            };
            dojo.stopEvent(e);
        },
        onMouseUp: function(e){
            if (this.clickInit) {
                this.clickInit = null;
                this.reflect();
                dojo.stopEvent(e);
            }
        },
        onMouseMove: function(e){
            if (this.clickInit) {
                var dx = e.clientX - this.clickInit.x, dy = e.clientY - this.clickInit.y;
                this.position.offsetX = this.clickInit.ox - dx;
                this.position.offsetY = this.clickInit.oy + dy;
                this.chart.setWindow(this.position.scaleX, this.position.scaleY, this.position.offsetX, this.position.offsetY).render();
                dojo.stopEvent(e);
            } else {
				this.showRatingHighlight(e);
			}
        },
        
        yAxisRatingLabelFunc: function(i){
            i = parseInt(i);
            if (i == 0) 
                label = 'unrated';
            else 
                if (i == MAX_RATING + 1) 
                    label = '';
                else 
                    label = i;
            return label;
        },
        timeAxisLabelFunc: function(ts){
            ts = parseInt(ts);
            var date = SNAPPI.timeline.getDateFromTimeslot(ts, this.dataset.period, false);
            var o = _formatDateLabels(date, 'MM');
            var label = o.date + '<br>' + o.time;
            return label;
        },
        hideRatingHighlight: function(e){
            var r = e.target.getBoundingClientRect();
            if (e.clientX < r.left +2 || e.clientX > r.right -2 || e.clientY > r.bottom-2 || e.clientY < r.top+2 ) {
                var n = Y.one('#rating-highlight-label');
                if (n) {
                    n.addClass('hidden');
                    n.dom().line.removeShape();
                    n.dom().line = null;
                }
            }
            dojo.stopEvent(e);
        },
        showRatingHighlight: function(e){
            /*
             *
             */
            var _getModelXCoord = function(x, y){
                var chart = this.chart;
                var coord = chart.getCoords();
                x = x - (coord.l + coord.x);
                var scalerX = chart.stack[0]._hScaler; // preferred, 0=default, 1=grid
                var transformX = scalerX.scaler.getTransformerFromPlot(scalerX);
                var modelX = Math.round(transformX(x));
                return modelX;
            };
            /*
             *
             */
            var _renderHighlightLabel = function(chartCoord, plotArea, x, str){
                var plotTop = parseInt(plotArea.getAttribute('y'));
                var plotHeight = parseInt(plotArea.getAttribute('height'));
                var t = chartCoord.y + plotTop;
                var b = t + plotHeight;
                var lineX = x - chartCoord.y - 6; // don't put the line directly under the mouse
                var n = Y.one('#rating-highlight-label');
                if (!n) {
                    n = Y.Node.create("<div id='rating-highlight-label'><span></span></div>");
                    Y.one('#content').append(n);
                    
                }
                if (!n.dom().line) {
                    var line = this.chart.surface.createLine({
                        x1: lineX,
                        y1: plotTop,
                        x2: lineX,
                        y2: plotHeight + plotTop
                    }).setStroke({
                        color: 'yellow',
                        width: 2
                    });
                    n.dom().line = line;
                };
                
                var offsetW = n.get('clientWidth') / 2;
                
                n.one('span').set('innerHTML', str);
                n.setStyles({
                    top: b,
                    left: (x - offsetW)
                }).removeClass('hidden');
                var line = n.dom().line.rawNode;
                line.setAttribute('x1', lineX);
                line.setAttribute('x2', lineX);
                var check;
            };
            
            
            
            x = e.clientX;
            var timeslot = _getModelXCoord.call(this, x);
            var date = SNAPPI.timeline.getDateFromTimeslot(timeslot, this.dataset.period, false);
            var o = _formatDateLabels(date, 'MM');
            var label = o.date + ' ' + o.time;
            var check;
            
            /*
             * render date highlight at mouseX
             */
            var chart = this.chart;
            var chartCoord = chart.getCoords();
            var middle = x;
            var plotArea = chart.surface.rawNode.children[1];
            
            _renderHighlightLabel.call(this, chartCoord, plotArea, x, label);
            
        },
        onVolumeClick: function(e){
            var clickX = e.clientX;
            // get px from chart left
            
            this.chartVol.plotX = SNAPPI.Y.one('div#volume svg > rect').getXY().shift();
            var dx = clickX - (this.chartVol.plotX);
            // get offset, 
            var chartW = this.chart.plotArea.width;
            var scale = this.position.scaleX;
            offsetX = offsetX < 0 ? 0 : offsetX;
            offsetX = offsetX > chartW * (scale - 1) ? chartW * (scale - 1) : offsetX;
            var offsetX = (dx * 2 * scale - chartW) / 2;
            
            this.position.offsetX = offsetX;
            this.chart.setWindow(this.position.scaleX, this.position.scaleY, this.position.offsetX, this.position.offsetY).render();
            dojo.stopEvent(e);
            
            
            
            
            
            // sample data from ratings chart
            var value = 4;
            var seriesGroup = this.chart.series[this.chart.runs['Rating=' + value]].group.rawNode;
            var seriesShapes = this.chart.series[this.chart.runs['Rating=' + value]].group.children;
            
            // in chartVol Plot coordinate space
            var volume = this.chartVol;
            var coord = volume.getCoords();
            var axisX = volume.getGeometry().volX; // name of x axis
            var scalerX = volume.stack[0]._hScaler; // preferred, 0=default, 1=grid
            var plotArea = volume.surface.rawNode.children[1];
            var transformX = scalerX.scaler.getTransformerFromModel(scalerX);
            // transformX: transfrom x plot value -> plot x coordinate, 0-670
            var plotX = transformX(1043890);
            
            
            
            
            this.renderVolumeMask(dx);
            
            
        },
        renderVolumeMask: function(dx){
            dx = dx || this.position.offsetX; // click coordinates, default far right
            /*
             * draw rect showing rating window on volume plotArea
             */
            var volume = this.chartVol;
            var volSurface = volume.surface;
            
            // apply bounds for offsetX
            var ratingPlotDim = this.chart.plotArea;
            var scaleX = this.position.scaleX;
            var chartW = ratingPlotDim.width;
            var offsetX = (dx * 2 * scaleX - chartW) / 2;
            
            offsetX = offsetX < 0 ? 0 : offsetX;
            offsetX = offsetX > chartW * (scaleX - 1) ? chartW * (scaleX - 1) : offsetX;
            this.position.offsetX = offsetX;
            
            // calc projection or ratings chart viewport onto volume chart
            var volPlotArea = volSurface.rawNode.children[1];
            var plotAreaRect = volPlotArea.getBoundingClientRect();
            var volSurfaceRect = volSurface.rawNode.getBoundingClientRect(); // plotAreaRect + margins/padding for labels/axis
            if (volume.focusRect && !volume.focusRect.l.rawNode.parentNode) {
                volume.focusRect.l.removeShape();
                volume.focusRect.r.removeShape();
                volume.focusRect = null; // orphaned focusRects
            }
            if (volume.focusRect) {
                //                volume.focusRect.removeShape();
                var focusRect = {
                    x: plotAreaRect.left - volSurfaceRect.left + offsetX / scaleX,
                    width: ratingPlotDim.width / scaleX
                };
                var lRect = {
                    x: plotAreaRect.left - volSurfaceRect.left,
                    width: offsetX / scaleX
                };
                //				lRect = Y.merge(focusRect,lRect);
                var rRect = {
                    x: focusRect.x + focusRect.width,
                    width: plotAreaRect.left - volSurfaceRect.left + plotAreaRect.width - (focusRect.x + focusRect.width)
                };
                //				rRect = Y.merge(focusRect,rRect);				
                volume.focusRect.l.rawNode.setAttribute('width', lRect.width);
                volume.focusRect.r.rawNode.setAttribute('x', rRect.x);
                volume.focusRect.r.rawNode.setAttribute('width', rRect.width);
            }
            else {
                var focusRect = {
                    x: plotAreaRect.left - volSurfaceRect.left + offsetX / scaleX,
                    y: plotAreaRect.top - volSurfaceRect.top,
                    width: ratingPlotDim.width / scaleX,
                    height: volPlotArea.getBoundingClientRect().height // ??? not sure this is correct
                };
                var lRect = {
                    x: plotAreaRect.left - volSurfaceRect.left,
                    width: offsetX / scaleX
                };
                lRect = Y.merge(focusRect, lRect);
                var rRect = {
                    x: focusRect.x + focusRect.width,
                    width: plotAreaRect.left - volSurfaceRect.left + plotAreaRect.width - (focusRect.x + focusRect.width)
                };
                rRect = Y.merge(focusRect, rRect);
                var lShape = volSurface.createRect(lRect).setFill('white');
                lShape.rawNode.setAttribute('fill-opacity', 0.6);
                var rShape = volSurface.createRect(rRect).setFill('white');
                rShape.rawNode.setAttribute('fill-opacity', 0.6);
                volume.focusRect = {
                    l: lShape,
                    r: rShape
                };
            }
            
            var check;
        },
        volumeOnMouse: function(e){
            var check;
            if (e.type == 'onclick') {
                var check;
            }
        },
        ratingOnMouse: function(e){
            var check;
            if (e.type == 'onmouseover') {
            
                var ts = e.x;
                var rating = parseInt(e.y);
                var value = e.run.data[e.index]; // same as e.y
                var arrKeys = this.series[rating][ts];
                var ids = [];
                var ID = 0, TIMESLOT = 1, DATETAKEN = 2, RATING = 3, SRC = 4;
                for (var i in arrKeys) {
                    ids.push(this.dataset.rows[arrKeys[i]][ID]);
                }
                var check;
                
                renderPhotoroll(ids);
                
                var target = e.event.originalTarget;
                
                function getElemFromPlot(plot, chart){
                    var plotSeries = chart.series[plot.y - 1];
                    var i = plotSeries.group.children.length / 2 + plot.index; // skip first half. why???
                    var circle = plotSeries.group.children[i];
                    return circle.rawNode;
                }
                
                if (this.focus) {
                    var circle = getElemFromPlot.call(this, this.focus, this.chart);
                    var origFill = circle.getAttribute('origFill');
                    if (origFill) 
                        circle.setAttribute('fill', origFill);
                    //                    if (this.focus.parentNode) 
                    //                        this.focus.parentNode.removeChild(this.focus);
                    this.focus = null;
                }
                
                /*
                 * find focus/plot by xy
                 */
                var plot = {
                    x: ts,
                    y: rating,
                    index: e.index
                };
                this.focus = plot;
                // find plot on render() and set fill color
                var circle = getElemFromPlot.call(this, this.focus, this.chart);
                circle.setAttribute('origFill', circle.getAttribute('fill'));
                circle.setAttribute('fill', 'rgb(255,0,0)');
                
                
                //                var focus = dojo.clone(target);
                //                var attr = focus.getAttribute('fill');
                //                focus.setAttribute('fill', 'rgb(256,0,0)');
                //                focus.id = 'focus-highlight';
                //                this.focus = focus;
                //                target.parentNode.appendChild(this.focus);
                //                var check;
            
            
            }
        }
    };
    
    var renderPhotoroll = function(ids){
        var Y = SNAPPI.Y;
        var auditionsSH = SNAPPI.timelineFeed.auditionsSH;
        var highlightSH = new SNAPPI.SortedHash();
        for (var i in ids) {
            highlightSH.add(auditionsSH.get(ids[i]));
        }
        var check;
        SNAPPI.DATASOURCE = {
            schemaParser: SNAPPI.AuditionParser_Snappi
        };
        var container = Y.one('div.element-roll.photo ul.photo-roll');
        /*
         * unbind auditions
         */
        container.all('li').each(function(n, i, l){
            try {
                var audition = n.dom().audition;
                var j = audition.bindTo.indexOf(n);
                if (j >= 0) 
                    audition.bindTo.splice(j, 1);
                n.remove();
            } 
            catch (e) {
            
            }
        }, this);
        
        container.set('innerHTML', '');
        var pr = new SNAPPI.PhotoRoll({
            container: container,
            sh: highlightSH
        });
        pr.render({
            perpage: 20,
            page: 1
        });
        pr.listen(true);
        pr.container.dom().PhotoRoll = pr; // back reference		
    };
    
    
    
    
    /*
     * make Global
     */
    SNAPPI.TimelineChart = TimelineChart;
    
})();
