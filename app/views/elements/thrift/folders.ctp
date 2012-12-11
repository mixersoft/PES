				<table class='thrift-folders'><tbody>
					<tr class='header'>
						<th class="status">Status</th>
						<th class="is-watched">Watch</th>
						<th class="label">Folder</th>
						<th class="remove">Remove</th>
					</tr>
					<?php  foreach ($folders as $i=>$folder) : 
							/*
							 * status=pending, all watched folders, count is 0||null
							 * status=done, is_scanned=1 
							 * unknown, count is null
							 */
							if (!empty($folder['ThriftFolder']['is_watched']))  $status = 'pending'; 
							else $status = ($folder['ThriftFolder']['is_scanned']) ? "done" : "pending";
							$percent = !empty($folder['ThriftFolder']['count'])
								? round($folder[0]['uploaded']/$folder['ThriftFolder']['count']*100)
								: 100;
							$unknown = $folder['ThriftFolder']['count']===null;	
							
						?>
					<tr id='<?php echo "fhash-{$folder['ThriftFolder']['native_path_hash']}"; ?>' class='row folder inline '>
							<td class='status'><div class="progress meter  <?php echo $status; ?>">
								<span class="fill" <?php 
										if ($unknown) {
											if (!empty($folder['ThriftFolder']['is_watched'])) 
												echo " title=\"We are watching this folder for new files\"";
											else if ($status=='done') echo " title=\"upload complete\""; 
											else echo " title=\"one moment...\""; 
										} else {
											if ($status=='done') echo " title=\"Upload complete\""; 
											else echo " title=\"{$percent}% complete\"";	
											if ($percent<100) echo "style=\"width:{$percent}%;\"" ; 
										}
									?>
									>
								</span></div></td>
							<td class='is-watched'><input name='data[ThriftFolder][native_path_hash]' action='watch' value='<?php echo "{$folder['ThriftFolder']['native_path_hash']}";?>' type='checkbox'<?php if ($folder['ThriftFolder']['is_watched']) echo " checked=yes"; ?>></td>
							<td class='label'><?php
								 $count = $folder['ThriftFolder']['count'] ? $folder['ThriftFolder']['count'] : '?';
								 if ($folder[0]['uploaded']) $count = "{$folder[0]['uploaded']}/{$count}";
								 echo "{$folder['ThriftFolder']['native_path']}  ($count)"; ?></td>
							<?php if ($folder['ThriftFolder']['is_not_found']) echo "<td class='btn orange' title='We cannot find a folder at this location. It may have been moved or deleted. Ctdck here to fix the location.'>Find Folder</td>"; ?></td>
							<td class='remove'><input name='data[ThriftFolder][native_path_hash]'  action='remove' value='remove' type='button' class='orange'></td>
						</tr>
					<?php  endforeach; ?>
					<?php if (empty($folders) && !empty($taskID['DeviceID'])) : ?>
						<tr class='empty-folders-message'><td colspan="4">
								<div class='message blue rounded-5 wrap-v'>
									<h1>Uploaded Folders</h1>
									<p>This is where you manage the folders that will upload photos to Snaphappi.
										Our uploader will scan the each of the folders listed above, including all subfolders,
										 for JPG files and upload a copy to Snaphappi.  
										</p>
									<p>Folders marked as <em>Watch</em> will periodically repeat this scan 
										and upload any new JPG files that are found.</p>
									<p>You have not selected any folders to upload. Please do so now.</p>
								</div></td>
						</tr>		
					<?php  endif; ?>					
				</tbody></table>
				<div id='device-label' value="<?php if (isset($device_label)) echo $device_label; ?>" class='hide'></div>
			
