<?php
ob_start();
?>

<form name="casc_del" method="POST" action="" enctype="multipart/form-data">
    <fieldset>
        <legend>Cascade Delete Menu Modifiers</legend>
        <label for="name">Location ID</label>
        <input type="text" name="loc_id" id="loc_id" />
        <input type="submit" name="submit" value="Submit" />
    </fieldset>
</form>

<?php 
if (isset($_POST['loc_id'])) {
	$loc_id = $_POST['loc_id'];
}

require 'cascdel.php';

$cascdel = new CascDel($loc_id);