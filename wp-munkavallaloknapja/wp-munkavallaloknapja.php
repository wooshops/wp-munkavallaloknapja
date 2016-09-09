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
	echo "<pre>".var_export(array($entry, $form), true)."</pre>";
}
