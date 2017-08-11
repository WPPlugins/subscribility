<div class="wrap">
  
	<br />Reset Troly log file by clicking <a href="admin.php?page=wp99234&wp99234_reset_log=1" target="_blank">here</a>
	
	<br/><br/>
	<?php
	
		$csv_log_file = content_url() . '/subs_log.csv';
		
		if (($csv_log = fopen($csv_log_file, 'r')) !== FALSE) {
			
			echo "<table class='subs_log_table'>";
			echo "<tr>";
			echo "<th>Time (UTC)</th>";
			echo "<th>Type</th>";
			echo "<th>What</th>";
			echo "<th>Details</th>";          
			echo "</tr>";
			
			while (($data = fgetcsv($csv_log)) !== FALSE) {
				echo "<tr>";
				foreach ($data as $fields) {
					$content = str_replace('\n', '<br>', $fields);
					
					echo "<td>$content</td>";
				}
				echo "</tr>";
			}
			
			echo "</table>";
		}
	
	?>

</div>