				<table class='thrift-devices'><tbody>
					<tr class='header'>
						<th class="label">Select</th>
						<th class="label">Name</th>
						<th class="folder-count">Folders</th>
						<th class="edit">Edit</th>
						<th class="remove">Remove</th>
					</tr>
					<?php  foreach ($devices as $i=> $device) : 
							$folder_count = $device['ThriftDevice']['thrift_folder_count'] ? $device['ThriftDevice']['thrift_folder_count'] : '?';
							$is_selected = isset($taskID['DeviceID']) && $device['ThriftDevice']['device_UUID'] === $taskID['DeviceID']; 
						?>
					<tr id='<?php echo "{$device['ThriftDevice']['device_UUID']}"; ?>' class='row device inline '>
							<td class='selected'><input name='data[ThriftFolder][id]' action='identify' value='<?php echo "{$device['ThriftDevice']['device_UUID']}";?>' type='radio' <?php if ($is_selected) echo " checked=yes"; ?>></td>
							<td class='label'><?php echo "{$device['ThriftDevice']['label']}"; ?></td>
							<td class='folder-count'><?php echo $folder_count; ?></td>
							<td class='edit'><input name='data[ThriftDevice][label]' action='rename' value='edit' type='button' class='orange'></td>
							<td class='remove'><input name='data[ThriftFolder][native_path_hash]'  action='remove' value='remove' type='button' class='orange'></td>
						</tr>
					<?php  endforeach; ?>
				</tbody></table>
			
