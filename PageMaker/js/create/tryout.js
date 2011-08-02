/**
 * 
 * Copyright (c) 2009, Snaphappi.com. All rights reserved.
 * 
 * 
 * 
 * @author Michael Lin, info@snaphappi.com
 * 
 * Tryout
 * 
 * A stack and/or filtered set of of Auditions, (i.e. a collection of photos to
 * be placed in a production) Typically a subset of a full CastingCall
 * 
 */
(function() {
	/*
	 * shorthand
	 */
	var PM = SNAPPI.namespace('SNAPPI.PM');
	var Y = PM.Y;

	/*
	 * protected
	 */
	var _defaultCfg = {
		oStack : undefined,
		xmlSrc : undefined,
		defaultSort : null
	};

	Tryout = function(cfg) {
		cfg = SNAPPI.util.mergeObj(cfg, _defaultCfg);
		/*
		 * properties
		 */
		this.init(cfg);
	};

	/*
	 * Static Methods
	 */
	Tryout._pmAuditionSH = new SNAPPI.SortedHash(); // master list of all
	// pagemaker auditions
	/*
	 * prototype functions, shared by all instances of object
	 */
	Tryout.prototype = {
		init : function(cfg) {
			var T = this;
			// /*
		// * an Tryout can be for 1 or more Productions
		// */
		// if (T.productionSH === undefined)
		// T.productionSH = new SNAPPI.SortedHash();
		// if (cfg.production)
		// this.addProduction(cfg.production);

		/*
		 * init Audition SortedHash
		 */
		if (T.pmAuditionSH) {
			if (T.dataSource instanceof String)
				T.pmAuditionSH.clear(); // clear ref to xmlSrc DataElements
		} else
			T.pmAuditionSH = new SNAPPI.SortedHash( {
				'isDataElement' : true
			// listen for changes to audition
					});

		/*
		 * configure Sort Functions
		 */
		T.defaultSort = cfg.defaultSort
				|| [ PM.Audition.sort.RATING, PM.Audition.sort.TIME,
						PM.Audition.sort.NAME ];
		T.pmAuditionSH.setDefaultSort(T.defaultSort);

		/*
		 * load Auditions from the correct datasource
		 */
		if (cfg && cfg.sortedhash) {
			// preferred method. for Lightbox, Baked project
			this.getAuditionsFromSortedHash(cfg.sortedhash, cfg.masterTryout);
			return;
		}
		if (cfg && cfg.oStack) {
			this.stack = cfg.oStack; // DEPRECATE. USE T.datasource
			this.getAuditionsFromStack(cfg);
			return;
		}
		if (cfg && cfg.xmlSrc) {
			this.INC_getAuditionsFromXml(cfg); // NOT DONE
			return;
		}

		/*
		 * init Tryout Sort config, and Sort
		 */
	},
	/*
	 * get datasource for BAKED, lightbox project
	 */
	getAuditionsFromSortedHash : function(sortedhash, masterTryoutSH) {

		var T = this;
		T.sh = null;
		if (sortedhash) {
			T.dataSource = sortedhash;
			T.pmAuditionSH.clear();
		}
		masterTryoutSH = masterTryoutSH || Tryout._pmAuditionSH;

		/*
		 * for now, let's just copy stack dataElements into auditions
		 */

		var pmAudition, masterPMAudition, dataElement; // for loop doesn't
		// change focus
		sortedhash.each(function(o) {
			var pmAudition = masterTryoutSH.get(o);
			if (!pmAudition) {
				// does not already exist, create new one
				pmAudition = new PM.Audition( {
					dataElement : o,
					previewOnly : false
				// (wo >3 ? true: false), adding scale to cropSpec
						});
				masterTryoutSH.add(pmAudition); // add to master copy
			}
			T.pmAuditionSH.add(pmAudition); // T.pmAuditionSH == copy of
			// T.dataSource
		}, this);
		// sort
		T.sort();
		return T.pmAuditionSH; // for convenience, only
	},
	/*
	 * get datasource for Gallery project - DEPRECATE
	 */
	getAuditionsFromStack : function(cfg) {
		var T = this;
		if (cfg && cfg.oStack) { // from gallery project
			this.stack = cfg.oStack; // DEPRECATE. USE T.datasource
			T.dataSource = cfg.oStack._dataElementSH;
		}
		/*
		 * for now, let's just copy stack dataElements into auditions
		 */

		var audition, dataElement; // for loop doesn't change focus
		for ( var i = 0; i < T.dataSource.count(); i++) {
			o = T.dataSource.get(i);
			audition = new PM.Audition( {
				dataElement : o,
				previewOnly : false
			// (wo >3 ? true: false), adding scale to cropSpec
					});
			T.pmAuditionSH.add(audition);
		}
		// sort
		T.sort();
	},
	INC_getAuditionsFromXml : function(cfg) {
		var T = this;
		T.dataSource = cfg.xmlSrc;

		/*
		 * getAuditionsFromXml is still incomplete
		 */
		/*
		 * make sure to set stack when loaded !!!!!!!!!!!!!!!!!!!!!!!!!!
		 */
		this.stack = T.stack;
	},
	/**
	 * gets a chunk of auditions from T.pmAuditionSH, original auditions in
	 * T.dataSource
	 * 
	 * @param {Object}
	 *            start
	 * @param {Object}
	 *            chunkSize
	 * @param {Object}
	 *            hideRepeats
	 */
	getChunk : function(start, chunkSize, hideRepeats) {
		/*
		 * sort by time
		 */
		this.sort( [ SNAPPI.PM.Audition.sort.TIME ]);
		var a, chunk, pmAuditionSH = this.pmAuditionSH;

		if (hideRepeats == false) {
			chunk = pmAuditionSH.slice(start, start + chunkSize);
		} else {
			chunk = new SNAPPI.SortedHash();
			var count = pmAuditionSH.count();
			for ( var i = start; i < count; i++) {
				a = pmAuditionSH.get(i);
				if (a.parsedAudition.isCast == true) {
					continue;
				}
				var subGrp = a.parsedAudition.Audition.Substitutions;
				if (subGrp && !subGrp.isBest(a.parsedAudition)) {
					continue;
				} else {
					a.isCast = false;
					chunk.add(a);
				}
				if (chunk.count() >= chunkSize)
					break;
			}
		}
		return chunk;
	},
	addAudition : function(audition) {
		this.pmAuditionSH.add(audition);
	},
	sort : function(cfg) {
		this.pmAuditionSH.sort(cfg);
	},
	clearCast : function(except) {
		if (except === undefined)
			except = [];
		/***********************************************************************
		 * TODO: for some crazy reason, this loop causes Tryout.js script to
		 * fail on load in FF
		 */
		this.pmAuditionSH.each(function(Aud) {
			if (except.indexOf(Aud.id) >= 0) {
				Aud.isCast = true;
				Aud.parsedAudition.isCast = true;
				return;
			}
			if (Aud.isCast == true)
				Aud.isCast = false;
			if (Aud.parsedAudition.isCast == true)
				Aud.parsedAudition.isCast = false;
		}, this);
		return;
		var count = pmAuditionSH.count();
		for ( var i = 0; i < count; i++) {
			var Aud = pmAuditionSH.get(i);
			if (except.indexOf(Aud.id) >= 0) {
				Aud.isCast = true;
				Aud.parsedAudition.isCast = true;
				continue;
			}
			if (Aud.isCast == true)
				Aud.isCast = false;
			if (Aud.parsedAudition.isCast == true)
				Aud.parsedAudition.isCast = false;
		}
	}
	};

	/*
	 * publish
	 */
	SNAPPI.PM.Tryout = Tryout;

})();
