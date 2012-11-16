
			<div class="wrap" >
				<ul class='thrift-folders'>
					<?php  foreach ($folders as $i=>$folder) : ?>
					<li>
						<ul id='<?php echo "{$folder['ThriftFolder']['thrift_device_id']}~{$folder['ThriftFolder']['native_path_hash']}"; ?>' class='folder inline'>
							<li><?php echo ($folder['ThriftFolder']['is_scanned']) ? "done" : "pending"; 
									if (!$folder['ThriftFolder']['is_scanned'] && $folder['ThriftFolder']['count']) echo " (".round($folder[0]['uploaded']/$folder['ThriftFolder']['count']*100)."% complete)";
								?></li>
							<li><input name='ThriftFolder[is_watched]'  type='checkbox'<?php if ($folder['ThriftFolder']['is_watched']) echo " checked=yes"; ?>>watch</li>
							<li><?php echo "{$folder['ThriftFolder']['native_path']}  ({$folder['ThriftFolder']['count']})"; ?></li>
							<?php if ($folder['ThriftFolder']['is_not_found']) echo "<li class='btn orange' title='We cannot find a folder at this location. It may have been moved or deleted. Click here to fix the location.'>Find Folder</li>"; ?></li>
							<li><input name='ThriftFolder[remove]'  type='checkbox'>remove</li>
						</ul></li>
					<?php  endforeach; ?>
				</ul>
			</div>
			
