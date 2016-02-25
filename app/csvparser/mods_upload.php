<?php
ob_start();
?>

<form name="upload_menu" method="POST" action="" enctype="multipart/form-data">
    <fieldset>
        <legend>Modifiers Parser</legend>
        <label for="name">Modifiers File</label>
        <input type="file" name="file" id="file" />
        <input type="submit" name="submit" value="Submit" />
    </fieldset>
</form>

<?php
$upload_dir = 'uploads/';
require 'modsparser.php';
if (isset($_POST['submit'])) {
    if ($_FILES['file']['type'] == 'text/csv') {
        if ($_FILES['file']['error'] > 0) {
            echo 'Error Code: ' . $_FILES['file']['error'] . '<br />';
            switch ($_FILES['upfile']['error']) {
                case UPLOAD_ERR_NO_FILE:
                    echo 'No file sent.' . '<br />';
                case UPLOAD_ERR_INI_SIZE:
                    echo 'Exceeds size limit.' . '<br />';
                case UPLOAD_ERR_FORM_SIZE:
                    echo 'Exceeded filesize limit.' . '<br/>';
                default:
                    echo 'Unknown errors.';
            }
        } else {
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $_FILES['file']['name'])) {
                die('Error uploading file - check destination is writeable.' . '<br/>');
            } else {
                echo 'Successfully uploaded file.' . '<br/>';
                $parser = new Parser\ModsParser($upload_dir . $_FILES['file']['name']);
            }
        }
    } else {
        echo 'Invalid file type: ' . $_FILES['file']['type'] . '<br/>';
    }
}