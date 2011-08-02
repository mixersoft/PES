(function(){
	if (SNAPPI.Sort) return;
    SNAPPI.namespace("SNAPPI.Sort");
	
    var Sort = {};
    
    Sort.regex  = {
        /*
         * StringDelimNumber.exec(string) = [string, string prefix, last occurance of number]
         * with number delimited by ['(' ,'-', '_']
         */
        StringDelimNumber: /(.*)[\(_-]([\d]*).*$/,
        LastNumber: /([\d]*)$/
    };
    
    Sort.compare = {
        Alpha: function(a, b, cfg){
            var sA, sB;
            sA = a[cfg.property].toLowerCase();
            sB = b[cfg.property].toLowerCase();
            return (sA > sB ? 1 : sA < sB ? -1 : 0) * cfg.invert;
        },
        Numeric: function(a, b, cfg){
            var nA, nB, result;
            nA = a[cfg.property];
            nB = b[cfg.property];
            result = nA - nB;
            if (isNaN(result)) {
                if (parseInt(nA)) 
                    return 1 * cfg.invert;
                if (parseInt(nB)) 
                    return -1 * cfg.invert;
                return 0;
            }
            else 
                return result * cfg.invert;
        },
		// e.g. abc-11, efg-2, hij (33) etc., (see above)
        AlphaPrefix: function(a, b, cfg){
            var sA, sB;
            sA = a[cfg.property].toLowerCase();
            sB = b[cfg.property].toLowerCase();
            var rA, rB;
            rA = Sort.regex.StringDelimNumber.exec(sA) || [null, sA, 0];
            rB = Sort.regex.StringDelimNumber.exec(sB) || [null, sB, 0];
            return (rA[1] > rB[1] ? 1 : rA[1] < rB[1] ? -1 : 0) * cfg.invert;
        },
		// e.g. hig-2, abc-11, efg (33) etc., (see above)
        NumericSuffix: function(a, b, cfg){
            var rA, rB;
            rA = Sort.regex.StringDelimNumber.exec(a[cfg.property]) || [null, null, 0];
            rB = Sort.regex.StringDelimNumber.exec(b[cfg.property]) || [null, null, 0];
            return (rA[2] - rB[2]) * cfg.invert;
        },
        Time: function(a, b, cfg){
            // "2008-06-14 10:11:10", use Date.parse(d.replace(/-/g,'/'))
            var dA = 0, dB = 0;
            if (a[cfg.property]) 
                dA = Date.parse(a[cfg.property].replace(/-/g, '/'));
            if (b[cfg.property]) 
                dB = Date.parse(b[cfg.property].replace(/-/g, '/'));
            return (dA > dB ? 1 : dA < dB ? -1 : 0) * cfg.invert;
        },
        HashedAlpha: function(a, b, cfg){
            var sA, sB;
			try {
	            sA = a.hashcode().toLowerCase();
	            sB = b.hashcode().toLowerCase();				
			} catch (e) {
				return 0;
			}
            return (sA > sB ? 1 : sA < sB ? -1 : 0) * cfg.invert;
        },
        HashedNumeric: function(a, b, cfg){
            var nA, nB;
            nA = a.hashcode();
            nB = b.hashcode();
            result = nA - nB;
            if (isNaN(result)) {
                if (parseInt(nA)) 
                    return 1 * cfg.invert;
                if (parseInt(nB)) 
                    return -1 * cfg.invert;
                return 0;
            }
            else 
                return result * cfg.invert;
        },
        HashedAlphaPrefix: function(a, b, cfg){
            var sA, sB;
            sA = a.hashcode().toLowerCase();
            sB = b.hashcode().toLowerCase();
            var rA, rB;
            rA = Sort.regex.StringDelimNumber.exec(sA) || [null, sA, 0];
            rB = Sort.regex.StringDelimNumber.exec(sB) || [null, sB, 0];
            return (rA[1] > rB[1] ? 1 : rA[1] < rB[1] ? -1 : 0) * cfg.invert;
        },
        HashedNumericSuffix: function(a, b, cfg){
            var rA, rB;
            rA = Sort.regex.StringDelimNumber.exec(a.hashcode()) || [null, null, 0];
            rB = Sort.regex.StringDelimNumber.exec(b.hashcode()) || [null, null, 0];
            return (rA[2] - rB[2]) * cfg.invert;
        },
        makeSortFn: function(cfg){
            if (cfg.constructor != Array) 
                cfg = [cfg];
            function arraySortFn(a, b){
                var retval = 0, thisCfg = {};
                for (var i = 0; i < cfg.length; i++) {
                    // thisCfg = SNAPPI.util.mergeObjCopy(cfg[i], thisCfg);
                    thisCfg = Y.merge(thisCfg, Y.clone(cfg[i])); 
                    thisCfg.invert = ((thisCfg.order === 'desc') ? -1 : 1);
					try {
						retval = thisCfg.fn(a, b, thisCfg);	
					} catch (e) {
						retval = 0;
					}
                    if (retval) 
                        break;
                }
                return retval;
            }
            return arraySortFn;
        }
    };
    
    Sort.resequenceSortOrderPreserveSettings = function(aOldSortCfg, aNewSortSequence){
        /*
         * reset defaultSortCfg sort order to sequence specified by aNewSortSequence,
         * match on cfg.label of aNewSortSequence,
         * preserving cfg settings
         */
        var found, reorderedSortCfg = [];
        for (var j = 0; j < aNewSortSequence.length; j++) {
            found = false;
            for (var k = 0; k < aOldSortCfg.length; k++) {
                if (aOldSortCfg[k].label == aNewSortSequence[j].label) {
                    reorderedSortCfg.push(aOldSortCfg[k]);
                    found = true;
                    break;
                }
                
            }
            if (!found) 
                reorderedSortCfg.push(aNewSortSequence[j]);
        }
        return reorderedSortCfg;
    };
	
	/*
	 * make global
	 */
    SNAPPI.Sort = Sort;
})();