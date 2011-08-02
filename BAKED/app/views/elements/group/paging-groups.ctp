<div  class='paging-control paging-sort'>
	<p>Sort by:</p>
	<ul class="inline">
		<li><?php echo $this->Paginator->sort('Title', 'title');?></li>
		<li><?php echo $this->Paginator->sort('Owner', 'owner_id');?></li>
		<li><?php echo $this->Paginator->sort('Created', 'created');?></li>
		<li><?php echo $this->Paginator->sort('Photos', 'assets_group_count');?></li>
		<li><?php echo $this->Paginator->sort('Members', 'groups_user_count');?></li>
	</ul>
</div>	
<?php echo $this->element('/group/roll'); ?>