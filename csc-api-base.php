<?php
header("Access-Control-Allow-Origin: *");

function create_slug($string){
    $slug=preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
    return $slug;
 }
//  echo create_slug('<p>#%%_==~!@#$%^&*()_+does this thing work or not</p>');
 //returns 'does-this-thing-work-or-not'

include("db.php");

if(!isset($_REQUEST['api'])){
    if(isset($_REQUEST['items'])) include("csc-name-slug.php");
    if(isset($_REQUEST['brands'])) include("csc-brands-slug.php");
}else{
    $printDebug = false;
    if(isset($_REQUEST['debug']) && $_REQUEST['debug'] = true){
        $printDebug = true;
    }
    header('Content-Type: application/json');
    include("csc-api.php");
}

?>