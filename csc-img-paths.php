<?php
// REF: https://www.php.net/manual/en/mysqli.affected-rows.php
// preview: SELECT id,name, upload_date,image_path FROM catalogue WHERE id_xtra=0 ORDER BY id ASC LIMIT 10

$disabled = false;//LIVE must be true
if($disabled){
	echo 'disabled';
	exit;
}

// CHECK folder exists...
function checkFolderExists($getSize,$newDir){
    global $foldersCreated,$tableTitle;

    if($getSize){
        $newDir = $newDir."/".$getSize."/";//thumb/primary/large
    }
    // CHECK / folders exist..
    if(file_exists( $newDir ) ){
        $foldersCreated ++;
        // $tableTitle .= '<p class="info">['.$newDir.'] FOLDER EXISTS</p>';
    }else{//if not, mkdir
        if(!file_exists( $newDir ) && mkdir($newDir, 0755, true)){
            $foldersCreated ++;
            $tableTitle .= '<p class="success">['.$newDir.'] FOLDER CREATED</p>';
        }else{
            $tableTitle .= '<p class="fatal">BASE FOLDER NOT CREATED</p>';
        }                       
    }
}
// (END) CHECK folder exists...

// CHECK file exists...
function checkFileExists( $getSize, $getFilename){
    global $imgPath,$newDir,$filesMoved,$table;

    switch($getSize){
        case "thumb":
            $imgPathFrom = $imgPath.'thumbs/'.$getFilename;
            $imgPathTo = $newDir.'/thumb/'.$getFilename;
            break;
        case "large":
            $imgPathFrom = $imgPath.'large/'.$getFilename;
            $imgPathTo = $newDir.'/large/'.$getFilename;
            break;
        default:
            $imgPathFrom = $imgPath.$getFilename;
            $imgPathTo = $newDir.'/primary/'.$getFilename;
            break;
    }

    if(file_exists( $imgPathFrom )){
        $table .= '<span class="info">moving...'.$imgPathTo.'</span>';
        if(rename($imgPathFrom, $imgPathTo)) $filesMoved ++;
    }else{
        if(file_exists( $imgPathTo )){
            $filesMoved ++;
            $table .= '<span class="good">['.$getSize.'] file already moved</span>';
        }else{
            $table .= '<span class="error">['.$getSize.'] cannot find file</span>';
        }
    }
}
// (END) CHECK file exists...

$echo = '';

if(isset($_REQUEST['id'])){
    $itemId = $_REQUEST['id'];
}

if(isset($_REQUEST['year']) && isset($_REQUEST['month'])){
    $getYear = $_REQUEST['year'];
    $getMonth = $_REQUEST['month'];
}

if($_REQUEST['reset']){
    $isReset = true;
    $echo .= "<h1>IMG PATHS: RESET</h1>";
    $echo .= '<p><a href="'.$_SERVER['php_self'].'?paths=true&reset=0">reset</a></p>';
    $sql = "UPDATE catalogue SET image_path='' WHERE (image_path != '')";
    $result = $mysqli->query($sql);
    // $echo .= '???'.$mysqli->affected_rows;
    $echo .= "Affected rows (UPDATE): %d\n".$mysqli->affected_rows;

    // $sql = "UPDATE catalogue SET image_path='' WHERE (id=1 OR id=8 OR id=11)";
    // $result = $mysqli->query($sql);
    // echo '>>>>>'.$mysqli->affected_rows;
}else{
    $echo .= "<h1>IMG PATHS: images_catalogue -> images/[year]/[months]</h1>";
    $echo .= '<p><a href="'.$_SERVER['php_self'].'?paths=true&reset=1">reset</a></p>';
    // Now, let's fetch five random items and output their names to a list.
    // We'll add less error handling here as you can do that on your own now
    $s = "SELECT id,name,image_large,upload_date FROM catalogue";
    $w = " WHERE id_xtra=0 AND image_large!=''";
    if($itemId) $w .= " AND id=$itemId";
    if($getYear && $getMonth) $w .= " AND (upload_date >= '".$getYear."-".$getMonth."-01' AND upload_date <= '".$getYear."-".$getMonth."-31')";
    $w .= " AND (image_path='' OR image_path='x')";
    $o = " ORDER BY id ASC";
    $l = " LIMIT 10";
    if($itemId) $l = " LIMIT 1";
    $sql = $s.$w.$o.$l;
}
$echo .= '<p class="sql">'.$sql.'</p>';

if (!$result = $mysqli->query($sql)) {
    $echo .= '<p class="fatal">Sorry, the website is experiencing problems.</p>';
    exit;
}else{
    if($isReset){

    }else{
        if(mysqli_num_rows($result) === 0 ){
            $echo .= '<p class="info">Nothing to do</p>';
            exit;
        }
    }
    
}


if(!$isReset){
    // Print items in a list, and link to each item
    $arrDirs = array();

    $table = "<table>\n";
    $tableTitle = "";
    while ($item = $result->fetch_assoc()) {
        $filesCount = 3;//thumb,primary,large
        $filesMoved = 0;
        $foldersExist = false;
        $foldersCount = 4;
        $foldersCreated = 0;
        $id = $item['id'];
        $imgFilename = $item['image_large'];
        $imgPath = "../images_catalogue/";        

        $date = $item['upload_date'];
        $dateX = explode("-",$date);
        $newDirStr = $dateX[0]."/".$dateX[1];

        $newDir = "../images/".$newDirStr;
        $imgTagThumb = '<img src="'.$newDir."/thumb/".$imgFilename.'" class="thumb">';
        $imgTagPrimary = '<img src="'.$newDir."/primary/".$imgFilename.'" class="primary">';
        $imgTagLarge = '<img src="'.$newDir."/large/".$imgFilename.'" class="large">';     

        // CHECK / folders exist.. if not, mkdir
        checkFolderExists("",$newDir);
        checkFolderExists("thumb",$newDir);
        checkFolderExists("primary",$newDir);
        checkFolderExists("large",$newDir);

        if ($newDirStr && !in_array($newDirStr, $arrDirs)) {
            array_push($arrDirs,$newDirStr);
            print_r($arrDirs);

            if($foldersCreated == $foldersCount){
                $foldersExist = true;
                $tableTitle .= '<p class="good">Folder \''.$newDirStr.'\' ready to receive...</p>';
            }
    
            if(!$foldersExist){
                $tableTitle .= '<p class="fatal">FAIL: Check folders for \''.$newDirStr.'\'</p>';
                exit;
            }
        }        
        // (END) CHECK

        $table .= "<tr>";
        $table .= "<td><a href='" . $_SERVER['SCRIPT_FILENAME'] . "?slug=" . $item['subcategory'] . "'>".$id."</a></td>\n";
        $table .= '<td class="img">'.$imgTagThumb.$imgTagPrimary.$imgTagLarge.'</td>';
        $table .= "<td>".$item['name']."</td>";
        $table .= "<td>";
        $table .= $date;
        $table .= '<br><strong>'.$dateX[0]." - ".$dateX[1].'</strong>';
        $table .= "</td>";
        $table .= '<td class="feedback">';
        // CHECK file exists... if it does, move it to the new directory
        checkFileExists("thumb",$imgFilename);
        checkFileExists("primary",$imgFilename);
        checkFileExists("large",$imgFilename);      

        if($filesMoved){
            $table .= '<span class="success">FILES MOVED '.$filesMoved.'/'.$filesCount.'</span>';
        }
        if($filesMoved == $filesCount){
            $sqlUpdate = "UPDATE catalogue SET image_path='$newDirStr' WHERE id=$id";
            if ($r = $mysqli->query($sqlUpdate)) {
                $table .= '<span class="success">DB updated '.$filesMoved.'/'.$filesCount.'</span>';
            }
        }
        $table .= "</td>";
        $table .= "</tr>\n";
    }
    $table .= "</table>\n";
}

if($tableTitle) $echo .= $tableTitle;
if($table) $echo .= $table;
echo $echo;

// The script will automatically free the result and close the MySQL
// connection when it exits, but let's just do it anyways
if(!$isReset) $result->free();
$mysqli->close();
?>