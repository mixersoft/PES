<div id="search" >
<script type="text/javascript">
PAGE.goSearch = function(o) {
	
	var value = SNAPPI.Y.one('#search input').get('value');
	if (value) {
		if (value.length>2) {
			o.href = SNAPPI.IO.setNamedParams(o.href, {q:value});
			return true;
		} else {
			alert('please enter at least 3 chars in your search');
		}
	}  
	return false;
};
</script>
<?php				
								
	/*
	 * "search" submenu items
	 */
	$items_search[] = array('label' => 'Search My',
							'href' => '/my/search',
							'onclick' => 'return PAGE.goSearch(this);'
						);
	
	$items_search[] = array('label' => 'Search Groups',
							'href' => '/groups/search',
							'onclick' => 'return PAGE.goSearch(this);'
						);
						
	$items_search[] = array('label' => 'Search People',
							'href' => '/person/search',
							'onclick' => 'return PAGE.goSearch(this);'
						);
	
	$items_search[] = array('label' => 'Search Photos',
								'href' => '/photos/search',
								'onclick' => 'return PAGE.goSearch(this);'
						);
	
	$items_search[] = array('label' => 'Search Tags',
							'href' => '/tags/search',
							'onclick' => 'return PAGE.goSearch(this);'
	);
	
	/*
	 * "discover" submenu items
	 */
	$items_discover[] = array('label' => 'Discover Groups',
							'href' => '/groups/all',
								
						);

	$items_discover[] = array('label' => 'Discover People',
								'href' => '/person/all',
								
						);
						
	$items_discover[] = array('label' => 'Discover Photos',
							'href' => '/photos/all',
								
						);
						
	$items_discover[] = array('label' => 'Discover Tags',
							'href' => '/tags/all',
								
						);
	// save in viewVars for later output in PAGE.jsonData						
	$this->viewVars['jsonData']['menu']['discover'] = $items_discover;
	$this->viewVars['jsonData']['menu']['search'] = $items_search;
?>
<?php 

	$controller = Configure::read('controller.alias');

	$passedArgs = $this->passedArgs;
	$next = array('plugin'=>'', 'action'=>'search');
	$controllerAttrs = Configure::read('controller');
	// add context
	$context = Session::read('lookup.context');
//	if ($context) $next[Inflector::singularize($context['class'])] = $context['uuid'];
	$titleName = $controllerAttrs['titleName']; // i.e. Me, Event, Wedding, Group, Person, etc.	
	if (empty($this->params['named']['q'])) {
		switch ($this->action) {
			case 'all': $defaultString = "search {$controllerAttrs['alias']}"; 
				break;
			case 'photos':
			case 'groups':
			case 'tags':
			case 'trends':	// alias for tags
				$defaultString = "search {$this->action}"; 
				$next['controller'] = $this->action;
				if ($next['controller'] == 'trends') $next['controller'] = 'tags';
				// add id to qs
				if ($passedArgs[0]) {
					$next[$controllerAttrs['class']] = $passedArgs[0];
					unset ($passedArgs[0]);
				}
				break;
			case 'members':
				$defaultString = "search {$this->action}"; 
				$next['controller'] = 'person';
				// add id to qs
				if ($passedArgs[0]) {
					$next[$controllerAttrs['class']] = $passedArgs[0];
					unset ($passedArgs[0]);
				}
				break;
			case 'home':
				// pass search to XHR divs as named param
				$label = Session::read("lookup.trail.{$controllerAttrs['keyName']}.label");
				if ($controllerAttrs['alias']=='my') {
					$defaultString = "search my items";
				} else $defaultString = "search this {$controllerAttrs['class']}";	
				$next['action'] = 'home';
				break;
			default:
				$defaultString = "search";			 
				break;
		}
	} else  {
		$defaultString = $this->params['named']['q'];
	}
//	debug($next);
	unset($passedArgs['q']);
?>
	<form id='search-form' accept-charset="utf-8" method="get" action="<?php echo Router::url($passedArgs + $next); ?>" onsubmit="if (this.value=='') return false;" >
		<input type='text' name='q' value='<?php echo $defaultString; ?>' maxlength='45' title='' onclick='if(this.style.color!="black"){this.value="";}this.style.color="black";' ></input>
		<img src="/img/snappi/search.gif">
	</form>
</div>