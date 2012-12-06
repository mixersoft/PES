
			<div class="wrap" >
				<ul class='thrift-folders'>
					<?php  foreach ($folders as $i=>$folder) : ?>
					<li>
						<ul id='<?php echo "fhash-{$folder['ThriftFolder']['native_path_hash']}"; ?>' class='folder inline '>
							<li><?php echo ($folder['ThriftFolder']['is_scanned']) ? "done" : "pending"; 
									if (!$folder['ThriftFolder']['is_scanned'] && $folder['ThriftFolder']['count']) echo " (".round($folder[0]['uploaded']/$folder['ThriftFolder']['count']*100)."% complete)";
								?></li>
							<li><input name='data[ThriftFolder][native_path_hash]' value='<?php echo "{$folder['ThriftFolder']['native_path_hash']}";?>' type='checkbox'<?php if ($folder['ThriftFolder']['is_watched']) echo " checked=yes"; ?>>watch</li>
							<li class='label'><?php
								 $count = $folder['ThriftFolder']['count'] ? $folder['ThriftFolder']['count'] : '?';
								 if ($folder[0]['uploaded']) $count = "{$folder[0]['uploaded']}/{$count}";
								 echo "{$folder['ThriftFolder']['native_path']}  ($count)"; ?></li>
							<?php if ($folder['ThriftFolder']['is_not_found']) echo "<li class='btn orange' title='We cannot find a folder at this location. It may have been moved or deleted. Click here to fix the location.'>Find Folder</li>"; ?></li>
							<li><input name='ThriftFolder[remove]'  type='checkbox'>remove</li>
						</ul></li>
					<?php  endforeach; ?>
				</ul>
			</div>
			
