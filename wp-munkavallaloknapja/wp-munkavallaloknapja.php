<?php
/**
 * Plugin Name: WP munkavallaloknapja
 * Plugin URI:  http://www.expedit.hu
 * Description: munkavallaloknapja
 * Author:      Expedit
 * Author URI:  http://www.expedit.hu
 * Version:     1.0
 * Licence:     MIT
 */

add_action( 'gform_after_submission', 'after_submission', 10, 2 );
function after_submission( $entry, $form ) {
	require("szamlazz.class.php");
	$trs = array(
		"Id"=>$entry["id"],
		"ProductName"=>$entry[14],
		"Count"=>$entry[16],
		"TotalAmount"=>$entry[15],
		"Date"=>$entry["date_created"],
		"BillingName"=>$entry[18],
		"BillingZipCode"=>$entry["5.5"],
		"BillingCity"=>$entry["5.3"],
		"BillingAddress"=>$entry["5.1"],
		"Name"=>$entry["6.3"]." ".$entry["6.6"],
		"ZipCode"=>$entry["5.5"],
		"City"=>$entry["5.3"],
		"Address"=>$entry["5.1"],
		"EmailAddress"=>$entry[10],
		"Telephone"=>$entry[9]
	);
	szamlazz::generateXML($trs);
	szamlazz::postXML($trs["Id"]);
}

add_action( 'admin_menu', 'exp_plugin_menu' );

function exp_plugin_menu() {
	add_options_page( 'Szamlazz.hu adatok', 'Szamlazz.hu adatok', 'manage_options', 'exp', 'exp_plugin_options' );
}

function exp_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	if(isset($_POST["user"]) && isset($_POST["pass"])) {
		update_option( "exp_szamlazz_user", $_POST["user"] );
		update_option( "exp_szamlazz_pass", $_POST["pass"] );
		update_option( "exp_szamlazz_et", $_POST["et"] );
		echo "<div class=\"updated\"><p><strong>".__( 'Settings saved.' )."</strong></p></div>";
	}
	$user = get_option( "exp_szamlazz_user" );
	$pass = get_option( "exp_szamlazz_pass" );
	$et = get_option( "exp_szamlazz_et" );
echo <<<EOF
<div class="wrap">
<h1>Szamlazz.hu adatok</h1>
<form method="post" action="">
<table class="form-table"><tr>
<th scope="row"><label for="blogname">Felhasználónév</label></th>
<td><input name="user" id="user" value="{$user}" class="regular-text" type="text"></td>
</tr><tr>
<th scope="row"><label for="blogdescription">Jelszó</label></th>
<td><input name="pass" id="pass" value="{$pass}" class="regular-text" type="password"><p class="description" id="tagline-description"></p></td>
</tr><tr>
<th scope="row"><label for="blogname">Előtag</label></th>
<td><input name="et" id="et" value="{$et}" class="regular-text" type="text"></td>
</tr></table>
<p class="submit"><input name="submit" id="submit" class="button button-primary" value="Mentés" type="submit"></p></form></div>
EOF;
}
