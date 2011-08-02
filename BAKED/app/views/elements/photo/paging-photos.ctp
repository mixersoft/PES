<div  class='paging-control paging-sort'>
<p>Sort by:</p>
<ul class="inline">
	<li><?php // echo $this->Html->link('Photostream', array('action'=>'photostreams', $this->passedArgs + array('action'=>'photostreams')  ))?></li>
	<li><?php
	/*
	 * Generate Paginator->sort() urls when sortin on field in associated models
	 */
		$orderBy= '0.rating'; $default = 'desc';
		$url = $this->Paginator->sort('Rating', $orderBy);	// this will ALWAYS BE direction:asc
		// default = 'desc';
		$isActive = @ifed($this->passedArgs['sort'], null) == $orderBy;
		if ($isActive) {
			$url = (@ifed($this->passedArgs['direction'],null) == 'desc') ? $url : str_replace('asc', 'desc', $url);  
		} else {
			$url = ($default=='desc') ? str_replace('asc', 'desc', $url) : $url;
		}
		echo $url;
		?></li>
	<li><?php echo $this->Paginator->sort('Provider', 'provider_account_id');?></li>
	<li><?php echo $this->Paginator->sort('Owner', 'owner_id');?></li>
	<li><?php echo $this->Paginator->sort('Date Taken', 'dateTaken');?></li>
	<li><?php echo $this->Paginator->sort('Date Uploaded', 'batchId');?></li>
	<li><?php echo $this->Paginator->sort('caption');?></li>
	<li><?php echo $this->Paginator->sort('keyword');?></li>
</ul>
</div>	
<?php 
$options['isPreview']=false;
echo $this->element('/photo/roll', $options); 
?>