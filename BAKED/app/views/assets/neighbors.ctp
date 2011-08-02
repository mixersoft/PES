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
				// TODO: bind members to MemberRoll
				SNAPPI.domJsBinder.bindAuditions2Filmstrip();
				SNAPPI.ajax.init();
			};
			try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
			catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
	</script>	
</div>
