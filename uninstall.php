<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
// Delete hostname 
$option_name = 'blastex_helo'; 
delete_option($option_name); 
// for site options in Multisite
// delete_site_option($option_name); 
// drop a custom database table
global $wpdb;
$table = $wpdb->prefix . 'sent_email_msg';
$wpdb->query("DROP TABLE IF EXISTS " . $table);
//$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mytable");
?>