<?php
echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail']));
?>
<div id='neighbors' class='photo filmstrip placeholder' >
	<script type='text/javascript' >
		<?php 
			echo "PAGE.jsonData.filmstrip = {$this->Js->object($jsonData['filmstrip'])}; \n";
			echo "PAGE.jsonData.castingCall = {$this->Js->object($jsonData['castingCall'])} ; \n";				
		?>
			var initOnce = function() {
				var Y = SNAPPI.Y;
				SNAPPI.mergeSessionData();
				
				// NOTE: we must init the gallery to start the 'WindowOptionClick' listners
				var filmstripCfg = {
					type: 'NavFilmstrip',
					castingCall: PAGE.jsonData.castingCall,
					// uuid: PAGE.jsonData.controller.xhrFrom.uuid,	// sets .focus
					render: true,		
				};
				var fs = new SNAPPI.Gallery(filmstripCfg);
				SNAPPI.xhrFetch.init();
			};
			try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
			catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
	</script>	
</div>
