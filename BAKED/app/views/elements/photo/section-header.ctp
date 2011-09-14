<?php 
	$primary = Session::read("nav.primary");
?>
<nav class="section-header container_16">
	<h1 class="grid_3">My Snaphappi</h1>
    <ul class="inline grid_9">
		<li class="disabled"><a>Montage</a></li>                    
		<li class="focus"><a>Gallery</a></li>
		<li class="disabled"><a>Timeline</a></li>
		<li class="disabled"><a>Invites</a></li>
		<li class="disabled"><a>Activity Feed</a></li>
		<li class="focus preference"><a>Preferences</a></li>
	</ul>
    <aside class="grid_4">
      	<?php echo $this->element('nav/search')?>
    </aside>
</nav>