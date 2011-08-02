<div  class='paging-control paging-sort'>
	<p>Sort by:</p>
	<ul class="inline">
		<li><?php echo $this->Paginator->sort('username');?></li>
		<li><?php echo $this->Paginator->sort('lastVisit');?></li>
		<li><?php echo $this->Paginator->sort('created');?></li>
	</ul>
</div>
<?php 
echo $this->element('/member/roll'); 
?>