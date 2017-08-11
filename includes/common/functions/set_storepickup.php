<?php 

/** (SEB) sounds fishy... */
session_start();
if($_POST['action']=='set_store_pickup'){
	$_SESSION['pickup'] = 1;
}else{
	unset($_SESSION['pickup']);
}
?>