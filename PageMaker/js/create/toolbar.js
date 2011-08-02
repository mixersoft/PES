		/*
		 * Silambu: the photo toolbar/edit mode gets activated together with the page 
		 * toolbar. Both are part of the "backstage mode". Also, while you have spearated the toolbar
		 * code, you should really make it a JS class. the main page should call var X = new EditMode(cfg)
		 * to create an instance of the EditMode code (both page and photo toolbars)
		 */
		/* photo tool bar */



/*
 * the sample scripts below require this HTML on the page. use Y.Node.create() to test.
<div class="submenu-main">
	<div class="yui3-b">
	   <div id="productsandservices" class="yui3-menu yui3-menu-horizontal yui3-splitbuttonnav hide" style="margin:0.25em;float:left; margin-top:0; margin-left:20px; left: 2px; top:18px; position:absolute;">
			<div class="yui3-menu-content">
				
				<ul class="first-of-type">						
					<li>
						<span class="yui3-menu-label">
							<a href="#snappi"><span>Rate here</span></a>
	
							<a href="#flickr-options" class="yui3-menu-toggle">Flickr Options</a>
						</span>									
						
						<div id="flickr-options" class="yui3-menu">
							<div class="yui3-menu-content">
								<ul>
									<li class="yui3-menuitem"><a class="yui3-menuitem-content"><span>AVG:</span></a></li>
									<li id="_parent" class="yui3-menuitem"><a class="yui3-menuitem-content"><div id="response" class="ratingGroup"><h1></h1></div></a></li>
								</ul>
							</div>
						</div>
					</li>
				</ul>
				            
			</div>
		</div>
	</div>	
</div> 
 *
 */
		
		YUI().use(
		"event-delegate",
		"node",
		function(Y) {
			
			
			
			/*
			 * YUI handlers seperated from methods
			 */
			/*
			 * Silambu: my cpu jumps to 50% when I load this page - even when I do nothing. 
			 * this probably because of the 
			 * way you use mouseover - it calls activateTools all the time. Perhaps you  need to find a way
			 * to use CSS:hover instead of mouseover. maybe mouseEnter/mouseLeave??
			 * Read the YUI3 docs on mouseover for more. 
			 */
			 
			Y.delegate("mouseover", activateTools, "#content", "img");
			Y.delegate("mouseover", showSubmenu, "#editlayer", ".replace");
			
			Y.delegate("mouseover", removeSubmenu, "#content", "img");
			Y.delegate("click", toolsAction, "#editlayer","div");
			
			//Y.delegate("click", rate, "#editlayer", ".options");
			
			Y.delegate("click", pgtoolsAction, "#page-edit-container","div");		
			Y.on('click', pgtoolsClick, '#page-edit-handler');
		
			//Y.on("click", rate, "#response");
		
			Y.on("domready", initiate);
			
			// SNAPPI.appendRatings();
			
			/*
			 * Y.Node/DOM Element Helper Functions
			 */
			Y.Node.prototype.dom = function() {
				return Y.Node.getDOMNode(this);
			};
			Y.Node.prototype.ynode = function() {
				return this;
			};
			HTMLElement.prototype.dom = function() {
				return this;
			};
			HTMLElement.prototype.ynode = function() {
				return Y.one(this);
			};
			

			// global variables
		
			var editmode = false;
			var editmousecheck = false;
			var src = "";
			var img_id = "";
			var if_append = false;
			
			function editmodeCheck(){
				if(!editmode){
					editmode = true;
					Y.one('#edithandler').set('innerHTML', "Normal Mode");
				}else{
					editmode = false;
					Y.one('#edithandler').set('innerHTML', "Edit Mode");
					Y.one('#editlayer').setStyles( {display: 'none'});
				}

			}
			
			function activateTools(e){
				target = e.target;	
				if(editmode){
					/*
					 * silambu: what are you trying to do here, and does it really ahve to be tied to an 
					 * onmouseover event? it is killing the CPU
					 */
					Y.one('#editlayer').setStyles( {
						width : Y.one(target).getStyle('width'),
						height : Y.one(target).getStyle('height'),
						top : Y.one('#content').get('offsetTop')+Y.one(target).get('offsetTop'),
						left : getcurpageoffset+Y.one(target).get('offsetLeft'),
						display: 'block'
						
					});
				}
				
				src = target.get('src');
				img_id= target.get('id');
				
				Y.one('#response').dom().el_id = img_id;
				
				/*
				 * if_append is a boolean variable, to identify if we have already initialized data from controller.
				 * if so, we will not do the same thing again.
				 * if not, we will render data from controller.
				 */
				if(!if_append){
					SNAPPI.appendRatings();
					if_append = true;
				}
				
			}
			
			function showSubmenu(e){	
			
				// SNAPPI.appendRatings();
				
				Y.one("#productsandservices").removeClass('hide');

				var submenu = Y.one("#productsandservices");

				var _sub = Y.one("#editlayer #gen_sub");

				_sub.append(submenu);
				
				/*
				 * here we get data from dom properties which we have already added from the controller
				 * and then render them
				 * 
				 * When we POST the data to db and get value back, we set the new data on this dom too.
				 * so when we showSubmenu(), we all grab latest data from dom property,
				 * we dont even have to use io to GET from db
				 * 
				 */
				var init_rating = Y.one('#' + img_id).dom().initRating;
				var init_avg = Y.one('#' + img_id).dom().initAvg;
				
				Y.one("#response").setStyle('backgroundPosition', '-' + (70 - 14 * init_rating) + 'px 0px');
				Y.one(".yui3-menuitem-content span").setContent("AVG: " + init_avg);

			}
			
			function removeSubmenu(e){
				target = e.target;
				Y.one("#productsandservices").addClass('hide');
			}
			
			function toolsAction(e){
				target = e.target;
				if(Y.one(target).hasClass("pin")){
					// Y.one(".editicon .drop_select").removeClass('hidden');
					alert("pin");
				}else if(Y.one(target).hasClass("replace")){
					alert("replace");
				}else if(Y.one(target).hasClass("remove")){
					alert("remove");
				}
				
			}
			function initiate(e) {
				/*
				 * Silambu: this is too much in one statement. 
				 * It makes it too difficult to add/remove/change toolbar buttons and actions.
				 * You need to build this markup step by step so it can be easily modified.
				 */
				var pgtoolhandcont = "<div id='page-edit-handler'>backstage</div>";
				var pgtoolcont = "<div id='page-edit-container'><div class='pgediticon pin'>Pin |</div><div class='pgediticon replace'>Replace |</div><div class='pgediticon remove'>Remove</div></div>";
				var phtoolhandcont = "<div id='edithandler'>Edit Mode</div>";
				var phtoolcont = "<div id='editlayer'><div class='editicon pin'>P</div><div id='gen_sub' class='editicon replace'>R</div><div class='editicon remove'>X</div></div>";
				 
				Y.one('body').prepend(pgtoolhandcont+pgtoolcont+phtoolhandcont+phtoolcont);
				Y.one('#page-edit-handler').setStyles( {left: Y.one('body').get('winWidth')-100});

				identify_src();
				
				init_rating();
				
			}
			
			/*
			 * we set its photo src(name) as id to store in the dom class property.
			 * we assumed that there's no the same class in css file.
			 * 
			 */
			function identify_src(){
				var all_imgs = Y.one('#content').all('img');
				
				var src = "";
				var length = 0;
				all_imgs.each(function(node){
					
					/*
					 * to get the part behind the last "/"
					 * /pg_designer/ .... /bpb1040120.jpg
					 */
					src = node.get('src');
					src = src.split('/');
					
					length = src.length - 1
					
					/*
					 * issue: somehow we can't process the class name with a dot(.)
					 * so we have to replace '.' with a '-'
					 */
					src = src[length];
					src = src.replace('.', '-');
					node.addClass(src);
					
				});
			}
			
			/*
			 * The data from controller here is stored in the specific HTML element which has style of "display:none"
			 * in the div 'init_data'
			 * so nothing would show up
			 * 
			 * initialization
			 * i) find class name, and then find dom object by this class name
			 * ii) add data on the custom dom properties
			 * 
			 */
			function init_rating(){

				var init_data = Y.one('.init_data').all('span');
				var img_one;
				var className = "";
				var score = "";
				var avg = "";
				var last = "";
				var _avg = "";
				var _last ="";
				init_data.each(function(node){
					className = node.get('className');

					className = className.replace('.', '-');
					img_one = Y.one('.' + className);
					score = node.get('innerHTML');
					score = score.split(",");
					last = score[0];
					avg = score[1];
					
					img_one.dom().initRating = last;
					img_one.dom().initAvg = avg;

				});

			}

			function pgtoolsClick(e){
				target = e.target;	
				editmodeCheck();
				if(Y.one('#page-edit-container').getStyle('display') == 'block'){
					Y.one('#page-edit-container').setStyles({display:'none'});
					Y.one(target).set('innerHTML','backstage');
				}else{
					Y.one('#page-edit-container').setStyles({display:'block'});
					Y.one(target).set('innerHTML','close');
				}
			}
			function pgtoolsAction(e){
				target = e.target;
				if(Y.one(target).hasClass("pin")){
					alert("Page pin");
				}else if(Y.one(target).hasClass("replace")){
					alert("Page replace");
				}else if(Y.one(target).hasClass("remove")){
					alert("Page remove");
				}
				
			}
		});