/*
 * SNAPPI custom event - hover
 */
(function(){
	var Y = SNAPPI.Y;
    /*
     * add 'snappi:hover' custom event
     * see: http://developer.yahoo.com/yui/3/examples/event/event-synth-hover.html
     * TODO: replace with yui3 hover in yui 3.4.0
     */
    var check = Y.Event.define("snappi:hover", {
        processArgs: function (args) {
            // Args received here match the Y.on(...) order, so
            // [ 'hover', onHover, "#demo p", endHover, context, etc ]
        	var endHover = null, selector = null, context = null;
            if (args.length > 3) {
            	endHover = args[3];
            	args.splice(3,1);
            }
            if (args.length > 3) {
            	context = args[3];
            	args.splice(3,1);
            } 
            if (args.length > 3) {
            	selector = args[3];
            	args.splice(3,1);
            }                

            // This will be stored in the subscription's '_extra' property
            return {endHover: endHover, context: context, selector: selector};
        },
        on: function (node, sub, notifier) {
        	var onHover = sub.fn;
            var endHover = sub._extra && sub._extra.endHover || null;
            var context = sub._extra && sub._extra.context || null;
            sub.context = context;
            // To make detaching the associated DOM events easy, use a
            // detach category, but keep the category unique per subscription
            // by creating the category with Y.guid()
            sub._evtGuid = Y.guid() + '|';
            
            node.on( sub._evtGuid + "mouseenter", 
                function (e) {
                    // Firing the notifier event executes the hover subscribers
            		sub.fn = onHover;
                    notifier.fire(e);
                }
            );
            
            node.on(sub._evtGuid + "mouseleave", 
	            function (e) {
                    // Firing the notifier event executes the hover subscribers
            		sub.fn = endHover;
            		notifier.fire(e);
                }
            );
        },
        detach: function (node, sub, notifier) {
            // Detach the mouseenter and mouseout subscriptions using the
            // detach category
            node.detach(sub._evtGuid + '*');
        },
        // add delegate support. it will be used in zoom or other places
        delegate: function (node, sub, notifier, filter) {
        	var onHover = sub.fn;
            var selector = sub._extra && sub._extra.selector || null;
            var context = sub._extra && sub._extra.context || null;
            sub._evtGuid = Y.guid() + '|';
            
        	node.delegate(sub._evtGuid + "mouseenter", 
        		sub.fn, selector, context
        	);
        	
        },

        // Delegate uses a separate detach function to facilitate undoing more
        // complex wiring created in the delegate logic above.  Not needed here.
        detachDelegate: function (node, sub, notifier) {
            sub._delegateDetacher.detach();
        }
    } );
 })();     