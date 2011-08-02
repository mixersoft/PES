(function(){
	
	// TODO: nothing on the outline view
	var Y = SNAPPI.Y;
	
	var isObject = Y.Lang.isObject,
		isArray  = Y.Lang.isArray,
		createNode = Y.Node.create;
	
	var markup = {
		li : '<li>{label}</li>',
		li_A : '<li><a>{label}</a></li>',
		dt : '<dt class="grid_2">{title}</dt>',
		dd : '<dd class="grid_14">{label}</dd>',
		dd_A : '<dd class="grid_14"><a>{label}</a></dd>',
		ul : '<ul></ul>',
		dl : '<dl></dl>'
	};
	
	function Property(o){
		Property.superclass.constructor.apply(this, arguments);
	};
	
	Y.mix(Property, {
		NAME : 'property',
		
		ATTRS : {
			data : {
				validator : function(data){
					var check = isObject(data) || isArray(data);
					return isObject(data) || isArray(data);
				}
			},
			type : {
				value : 'ul',
				validator : function(type){
					// return false if type is neither 'ul' nor 'li'
					return ((type != 'ul') && (type != 'dl')) ? false : true
				}
			}
		}
	});
	
	Y.extend(Property, Y.Base, {
	
		renderAsDialog : function(){
			SNAPPI.dialogbox.render(this.get('data'));
		},
		
		renderAsAsyncLoading : function(){
			var async = new SNAPPI.AsyncLoading(this.get('data'));
			async.execute();
			
		},
		
		render : function(){ // node
			var type = this.get('type'),
				data = this.get('data');
			
			var parent, parentNode, child, node;

			switch(type){
			case 'ul':
				parent = 'ul', child_1 = 'li', child_2 = null;
				parentNode = createNode(markup.ul);
				break;
			case 'dl':
				parent = 'dl', child_1 = 'dd', child_2 = 'dt';
				parentNode = createNode(markup.dl);
				break;
			}
			
			if(isArray(data)){
				var list = [], n_1, n_2;
				for (var i in data){
					var odd = i%2>0;
					if(data[i].element == 'a'){
						if(child_2 != null){
							n_2 = createNode(Y.substitute(markup[child_2], data[i]));
							parentNode.append(n_2);
						}
						n_1 = createNode(Y.substitute(markup[child_1 + '_A'], data[i]));
						parentNode.append(n_1);
						
						this._extractProperty(n_1, data[i]);
					}else {
						if(child_2 != null){
							n_2 = createNode(Y.substitute(markup[child_2], data[i]));
							parentNode.append(n_2);
						}
						n_1 = createNode(Y.substitute(markup[child_1], data[i]));
						parentNode.append(n_1);
						
						this._extractProperty(n_1, data[i]);
					}
					switch(type){
					case 'ul':
						if (odd) n_1.addClass('altrow');
						break;
					case 'dl':
						if (odd) n_2.addClass('altrow');
						break;
					}
					
				}
			}else {
				node = createNode(Y.substitute(markup[child]), data[i]);
			}
			
			var check = parentNode;
			return parentNode;
		},
		
		_extractProperty: function(node, data){
			
			if(data.className){
				node.addClass(data.className);
			}
			if(data.href){
				node.one('a').setAttribute('href', data.href);
			}
		}
		
	});
	
	SNAPPI.Property = Property;
	
})();


























