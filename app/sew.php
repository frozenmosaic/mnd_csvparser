<?php
ob_start();
?>
<form name="upload_menu" method="POST" action="" enctype="multipart/form-data">
	<input type="file" name="file" id="file" /> 
	<br />
	<input type="submit" name="submit" value="Submit" />
</form>

<?php
$path = "./";
// require($path . "includes/config.php");
// require($path . "includes/connect.php");

if( isset( $_POST['submit']))
{	
	if( $_FILES['file']['type'] == 'text/csv' ||
		$_FILES['file']['type'] == 'application/vnd.ms-excel' || 
		$_FILES['file']['type'] == 'text/plain'
		)
	{
		if( $_FILES['file']['error'] > 0)
		{
			echo 'Return Code: ' . $_FILES['file']['error'] . '<br />';
		}
		else
		{
			//echo 'Upload: ' . $_FILES['file']['name'] . '<br />';
			//echo 'Type: ' . $_FILES['file']['type'] . '<br />';
			//echo 'Size: ' . ($_FILES['file']['size'] / 1024) . ' Kb<br />';
			//echo 'Temp file: ' . $_FILES['file']['tmp_name'] . '<br />';
			
			
			$file_name = $_FILES['file']['tmp_name'];
			$file = fopen($file_name, 'rb');
			$count = 0;
			while( !feof( $file ))
			{
				$profile_row = fgets($file);
				
				// Don't do anything for lines that are carriage returns.
				if( $profile_row != "\n" && $count != 0)
				{
					$profile_cell = explode(',', $profile_row );
					//print_r($profile_cell);
					
					// Clean URL and Create Save Path
					$web_address_cleaned = strtolower(trim($profile_cell[6]));

					if( !empty($web_address_cleaned))
					{
					
						/* need to remove the trailing / in an address like 
								www.angelicopizzeria.com/ so that when we get two entries like
							www.angelicopizzeria.com/ and www.angelicopizzeria.com only one will be inserted still
						*/
						
						$string_length = strlen( $web_address_cleaned );
						if( substr($web_address_cleaned, -1, 1) == '/')
						{							
							$web_address_cleaned = substr($web_address_cleaned, 0, $string_length-1);
						}
						
						// Found out there is actually a real elipse [ … vs ...]
						if( !preg_match('/(…)/',$web_address_cleaned))
						{
							// Make sure they are no elispses
							if( !preg_match('/([.]{3})/',$web_address_cleaned))
							{
								$web_address_wget_cleaned_part1 = str_replace('https://','http://', $web_address_cleaned);
								$web_address_wget = str_replace('http://','', $web_address_wget_cleaned_part1);
								//echo $web_address_wget . '<br/>';
							
								// since we removed the http://, wget needs the www. preface otherwise it will grab the first page only... (maybe revise wget), but for now, lets add www. if www. doesn't exist
								if( substr($web_address_wget, 0,4) != 'www.')
								{
									//echo 'we need to contactenate ' . $web_address_wget . '<br />';
									$web_address_wget = 'www.'.$web_address_wget;
								}
								
							
								
								
								
								
								echo $web_address_wget . '<br />';
							
								$web_address_save_path = str_replace('/','_', $web_address_wget);
								echo $web_address_save_path . '<br/>';					
								
								
								if( strlen($web_address_wget) <= 255 )
								{
									$query = "INSERT INTO cb_addresses(
									web_address_wget,
									web_address_save_path,
									restaurant_name,
									restaurant_address,
									restaurant_city,
									restaurant_state,
									restaurant_zip,
									restaurant_phone
									) 
									VALUES
									(
										'".mysql_real_escape_string($web_address_wget)."',
										'".mysql_real_escape_string($web_address_save_path)."',
										'".mysql_real_escape_string($profile_cell[0])."',
										'".mysql_real_escape_string($profile_cell[1])."',
										'".mysql_real_escape_string($profile_cell[2])."',
										'".mysql_real_escape_string($profile_cell[3])."',
										'".mysql_real_escape_string($profile_cell[4])."',
										'".mysql_real_escape_string($profile_cell[5])."'
									)";
									//echo $query;
									//echo '<br />';
									$result = mysql_query( $query );
								}
								else
								{
										echo '<b>Length of web address exceeds 255 characters and will not be inserted: ' .$web_address_cleaned . '</b><br />';
								}
							}
							else
							{
								echo '<b>Found an elipse, throw from result set.</b><br />';
							}
						}
						else
						{
							echo '<b>Found an actual elipse<br/><br/></b>';
						}

					}
					else
					{
						echo '<b>No website, throw from result set.</b><br />';
					}
					
				}
				else
				{
					echo '<b>Skipped</b> <br /><br/>';
				}
				
				$count++;
			}

			echo 'List successfully uploaded. <br />';
			echo 'Records processed: ' . $count . '<br />';
		}
	}
	else
	{
		echo 'File type: ' . $_FILES['file']['type'] . '<br/>';
		echo 'Invalid file type. <br ?';
	}
}


?>