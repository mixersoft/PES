<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();	
?>
<div class="users photostream">
	<?php echo $this->element('context')?>
	<p>Total of <?php echo count($data['ProviderAccount']) ?> photostreams
	</p>
		
	<?php 
//		class Callback {
//			static $paid;
//			static $batchId;
//			public static function inPhotostream($row){
//				return ($row['provider_account_id']==Callback::$paid && $row['batchId']==Callback::$batchId);
//			}
//		}
		
		foreach ($data['ProviderAccount'] as $stream) {
			// filter Assets by photostream/batch
//			Callback::$paid = $stream['id'];
//			Callback::$batchId = $stream['Assets'][0]['batchId'];
//			// TODO: rewrite callback to array_filter in one pass
//			$photos = array_filter($stream['Assets'], array('Callback','inPhotostream'));

			/*
			 * Callback::inPhotostream no longer needed now that results are organized by provider_account
			 */ 	
			$photos = $stream['Assets']; 
			// make owner as link, set context to Group
			$fields = array();
			$fields['stream'] = $stream['display_name'].'@'.$stream['provider_name'];
			$fields['owner_id'] = $stream['Assets'][0]['owner_id'];
			
//			$context = Callback::$paid.':'.Callback::$batchId;
			$url = Router::url(array('controller' => (($fields['owner_id'] == AppController::$ownerid) ? 'my' : 'person'), 'action'=>'photostreams', $stream['id']));
			$ownerLink = $this->Html->link($fields['stream'], $url);
			echo "<h4> {$ownerLink} ({$stream['found_rows']} photos)</h4>";
			echo $this->element('/photo/photostream_roll', array('photos'=>(array)$photos, 'labelField'=>'owner_id', 'lookupField'=>Session::read('lookup.owner_names')));
		}
	?>
</div>

<?php	// tagCloud
	$ajaxSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset'));
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' delay='8000'></div>";
?>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init(); 
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>	