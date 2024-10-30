<?php
/*
Plugin Name:  Blastex Plugin
Plugin URI: https://wordpress.org/plugins/blastex/
Description: Blastex smtp client SSL/TLS
Version: 2.0
Author: Breakermind
Author URI: https://breakermind.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: blastex
Domain Path: /languages
*/

/*
License:
1) Commercial use only after 10USD Donation on PayPal account: hello@breakermind.com
2) Private use for Free (0.00 USD)
*/
ob_start();
// header('Content-Type: text/html; charset=utf-8');
defined( 'ABSPATH' ) or die( __('No script kiddies please!', 'blastex') );
// Define plugin path
define( 'blastex_PATH', plugin_dir_path( __FILE__ ) );

$blastex_minimalRequiredPhpVersion = '5.1';

function blastex_noticePhpVersionWrong() {
    global $blastex_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
    __('Error: plugin "Blastex" requires a newer version of PHP to be running.',  'blastex').
    '<br/>' . __('Minimal version of PHP required: ', 'blastex') . '<strong>' . $blastex_minimalRequiredPhpVersion . '</strong>' .
    '<br/>' . __('Your server\'s PHP version: ', 'blastex') . '<strong>' . phpversion() . '</strong>' .'</div>';
}

function blastex_PhpVersionCheck() {
    global $blastex_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $blastex_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'blastex_noticePhpVersionWrong');
        return false;
    }
    return true;
}

function blastex_i18n_init() {
	// Relative to WP_PLUGIN_DIR
    $pluginDir = dirname(plugin_basename(__FILE__));    
    load_plugin_textdomain('blastex', false, $pluginDir . '/languages/');
}
// Initialize i18n
add_action('plugins_loaded','blastex_i18n_init');

// Database create function
function create_db(){
	// get database global (connection to database)
	global $wpdb;

	// create table with prefix
	$table = $wpdb->prefix . 'sent_email_msg';
	// db charset
	$charset_collate = $wpdb->get_charset_collate();

	// create database table
	$sql = "CREATE TABLE IF NOT EXISTS $table (
	`id` bigint(22) NOT NULL AUTO_INCREMENT,
	`uid` bigint(22) NOT NULL DEFAULT 0,	
	`user` varchar(200),
	`efrom` text,	
	`eto` text,	
	`subject` text,	
	`msg` text,		
	`ip` varchar(200),
	`active` int(11) DEFAULT 1,
	`time` timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

// Add to database
function addEmailLog($eto,$subject,$msg,$sent = 1){
	// Get user data
	$info = get_userdata(1);

	global $wpdb;
	$table = $wpdb->prefix . 'sent_email_msg';
	$wpdb->insert( 
		$table, 
		array( 
			// 'time' => current_time( 'mysql' ), 
			'uid' => (int)$info->ID, 
			'user' => $info->user_login,
			'efrom' => $info->user_email,
			'eto' => $eto,
			'subject' => $subject,
			'msg' => $msg,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'active' => $sent			
		) 
	);
}

function getEmailLogs(){
	global $wpdb;
	$table = $wpdb->prefix . 'sent_email_msg';
	$rows = $wpdb->get_results( 'SELECT * FROM '.$table.' ORDER BY id DESC LIMIT 50' );
	return $rows;
}

// If it is successful, continue with initialization for this plugin
if (blastex_PhpVersionCheck()) {

	function blastex()
	{
	    // Get user info
		$info = get_userdata(1);

		//plugin url		
		$wp_url = home_url();
		$url = plugins_url().'/'.strtolower('blastex');
		$plugin_folder_path =  dirname(__FILE__);
		// load style css from plugin url
		wp_register_style( 'style', $url.'/style.css' );
		wp_enqueue_style('style');		

		register_setting( 'wp_mail_settings', 'wp_mail_blastex' );	        
	    add_option('blastex_error', '');
	    add_option('blastex_helo', 'local.host');
	}
	add_action( 'init', 'blastex' );
	 
	function blastex_install()
	{	
		// Create plugin table
		create_db();

	    // clear the permalinks after the post type has been registered
	    flush_rewrite_rules();
	}
	register_activation_hook(__FILE__,'blastex_install');


	function blastex_deactivation()
	{
	    // our post type will be automatically removed, so no need to unregister it	 
	    // clear the permalinks to remove our post type's rules
	    flush_rewrite_rules();
	}
	register_deactivation_hook(__FILE__,'blastex_deactivation');

		//Create a function called "wporg_init" if it doesn't already exist
	if ( !function_exists( 'wp_mail' ) ) {
	    function wp_mail( $to, $subject, $msg, $empty = '', $attachments = array() ) {
	    	// Here override wp_mail function to send email from my client !!!!		
	        
			// Include smtp class Blastex
			require_once( dirname( __FILE__ ) . '/blastex-dns.php' );

			// Create object
			$m = new Blastex();
			
			if($_POST['log'] != 0){		
				// Show logs
				$m->Debug(1);
			}

			// get hostname
			$helloHost = get_option('blastex_helo');

			// hello hostname
			$m->addHeloHost($helloHost);			
				
			// Send from email
			$from = get_userdata(1)->user_email;
			$fromName = get_userdata(1)->user_name;
			if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {				
				update_option('blastex_error', '<div class="err">'.__('Invalid From email!', 'blastex').'</div>');
				return 0;
			}

			// Add from
			if(!empty($fromName)){
				$m->addFrom($from, $fromName);
			}else{
				$m->addFrom($from);
			}
		
			if (empty($subject) || empty($msg) || empty($to)) {				
				update_option('blastex_error', '<div class="err">'.__('Add some text!', 'blastex').'</div>');				
				return 0;
			}
			// Add to blastex
			$m->addText("Change mode to html!");
			$m->addHtml($msg);
			$m->addSubject($subject);

			// Recipients			
			$toList = explode(',', $to);			
			foreach ($toList as $email) {
				if(!empty($email)){
					if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
						update_option('blastex_error', '<div class="err">'.__('Invalid email format!', 'blastex').'</div>');
						return 0;
					}		
					// Blastex add to email
					// Add to
					$m->addTo(trim($email));
				}
			}		

			// Add attachments
			foreach ($attachments as $file) {
				if(file_exists($file)){
					$m->addFile($file);
				}else{
					update_option('blastex_error', '<div class="err">'.__('File does not exists!', 'blastex').' '.$file.'</div>');
					return 0;
				}
			}

			// Send email			
			$ok = $m->Send();
			if($ok == 1){
				// Add to database sent emails
				addEmailLog($to,$subject,$msg,1);
				// Show error
				if(function_exists('mb_convert_encoding')){
					$err = mb_convert_encoding($m->lastError, "UTF-8", "auto");
				}else{
					$err = $m->lastError;
				}
				update_option('blastex_error', '<div class="err">'.__('Email has been sent!', 'blastex').' '.$err.' From: ' . $from . ' To: ' . $to . '</div>');
				return  1;
			}else{
				// Add to database error emails
				addEmailLog($to,$subject,$msg,0);
				// Show error
				if(function_exists('mb_convert_encoding')){
					$err = mb_convert_encoding($m->lastError, "UTF-8", "auto");
				}else{
					$err = $m->lastError;
				}
				update_option('blastex_error', '<div class="err">'.__('Email send error!', 'blastex').'! '.$err.'</div>');
				return 0;
			}

	    }
	}
	
	if ( is_admin() ) {
		// we are in admin mode
    	//require_once( dirname( __FILE__ ) . '/admin/file.php' );	    
	} else {
	    //include_once( plugin_dir_path( __FILE__ ) . 'includes/file.php' );
	}

	// Step 1
	add_action( 'admin_menu', 'blastex_plugin_menu' );

	// Step 2
	function blastex_plugin_menu() {
		// Page in Settings tab
		// add_options_page( 'Blastex', 'Blastex SMTP Plugin', 'manage_options', 'blastex', 'blastex_plugin_options' );		

		// Show only if administrator
	    if (current_user_can('administrator')) {		    
		    // add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' );
		    // create top level menu for administrator
		    add_menu_page('Blastex SMTP', 'Blastex SMTP', 'manage_options', 'mymenu', 'blastex_options_page' , 'https://cdn0.iconfinder.com/data/icons/customicondesignsocialmedia1shadow/16/email.png' );
		    // create sub menu page
		    add_submenu_page( 'mymenu', 'Send email', 'Send email', 'manage_options', 'mymenu', 'blastex_options_page');
		    add_submenu_page( 'mymenu', 'Send logs', 'Send logs', 'manage_options', 'mymenu2', 'blastex_logs_page');
		    add_submenu_page( 'mymenu', 'About plugin', 'About plugin', 'manage_options', 'mymenu3', 'blastex_about_page');
	    }
	}

	// Step 3
	function blastex_options_page() {

		echo '<div class="wrap">';

		if(!empty($_POST['send'])){	
			// Send email	
			$ok = wp_mail($_POST['to'], $_POST['subject'], $_POST['msg']);
			echo get_option('blastex_error');
		}

		if(!empty($_POST['update'])){	
			// Update
			update_option('blastex_helo', $_POST['hostname']);			
		}

		if ( !current_user_can( 'manage_options' ) && !current_user_can( 'administrator' ))  {
			wp_die( __( 'You do not have sufficient permissions to access this page (Allow manage_options for user).', 'blastex') );
		}
		
		echo $html = '
		<div class="box">
		<h1>Send email</h1>	
			<form method="post" action="" name="form">
			<label>'.__('Recipients', 'blastex').'</label>
			<input type="text" name="to" title="'.__('Recipient emails list (comma separated)', 'blastex').'" placeholder="email@domena.com,email@example.org">
			<label>'.__('Message subject', 'blastex').'</label>
			<input type="text" name="subject" title="'.__('Message subject', 'blastex').'" placeholder="'.__('Add subject', 'blastex').'">
			<label>'.__('Message', 'blastex').'</label>
			<textarea name="msg" title="'.__('Message content text or html', 'blastex').'" placeholder="'.__('Add text or html', 'blastex').'"></textarea>
			<label>'.__('Show smtp logs', 'blastex').'</label>
			<select name="log" title="'.__('Smtp server connection logs', 'blastex').'">
			<option value="0">No</options>
			<option value="1">Yes</options>
			</select>
			<input type="submit" name="send" value="'.__('Send message', 'blastex').'">	
			</form>
		</div>
		';

		if(!function_exists('stream_socket_enable_crypto')){
			echo '<div class="box">
			<h1>'.__('Blastex smtp email client plugin', 'blastex').'</h1>
			<p>'.__('Sending email without SMTP server. Enable in php.ini file sockets extension:', 'blastex').'</p>
				<code>
				;extension=php_sockets.dll <br>
				;extension=php_mbstring.dll
				</code> 
			<p> '.__('Change to', 'blastex').' </p>
				<code>
				extension=php_sockets.dll <br>
				extension=php_mbstring.dll
				</code> <br><br>	
			</div>';
		}

		// get hostname
		$helloHost = get_option('blastex_helo');

		echo $html = '
		<div class="box">
		<h1>Smtp client hostname</h1>	
			<form method="post" action="" name="form1">			
			<label>Ehlo hostname (your-domain.xx or smtp.your-domain.xx)</label>
			<input type="text" name="hostname" title="Your hostname" value="'.$helloHost.'">			
			<input type="submit" name="update" value="Update">	
			</form>
		</div>
		';

		echo '</div>';
	}

	function blastex_about_page(){
		echo '<div class="wrap">
		    <div class="box">
				<h1>Blastex smtp email client</h1>	
				
				<h2> Blastex SSL Smtp email client for wordpress </h2> <br>
				<p>
				Send html emails with attachments without smtp server from your blog. <br>
				You can send email from standard wp_mail function. <br>
				Blastex gets recipient mx hostname from dns server and send email. <br>
				Send an email messages to gmail.com yahoo.com, outlook.com, ovh.com, hotmail.com. <br>
				</p>
				<br>
				<h2> About blastex </h2>
				<p>
				* Php Ssl Smtp email client plugin for WordPress <br>
				* Send emails without local smtp server from wordpress blog <br>
				* Send email form admin panel email form <br>
				* Languages: English, Polish <br>
				</p>
				<br>
				<h2> How to use </h2>
				<code> 
					$to = \'hello@emal.com, hello@boom.com\'; <br>
					$subject \'Hello from email client\'; <br>
					$html = \''.htmlentities('<h1>Hello message from smtp!</h1> <br> <p>Message from wordpress plugin!</p>').'\'; <br>
					 <br>
					// Install and activate plugin and send emails (Return 1 if messages was sent or 0 if error) <br>
					$ok = wp_mail($to, $subject, $html); <br>
					 <br>
					// Show error <br>
					echo get_option(\'blastex_error\'); <br>
				</code>
				<br>
				<h2> Developer info </h2>
				<a href="https://wordpress.org/plugins/blastex/" class="btn" target="_blank"> Blastex Smtp email client </a>
				<a href="https://breakermind.com" class="btn" target="_blank"> Developer page </a>
			</div>
		</div>';
	}

	function blastex_logs_page(){
		$rows = getEmailLogs();
		echo '
		<div class="wrap">
		<div class="box">
		<h1>'.__('Sent emails (last 50)', 'blastex').'</h1>
			<table class="tg">
		  	<tr>
			    <th class="tg-yw4l">'.__('UserID','blastex').'</th>
			    <th class="tg-yw4l">'.__('Sender','blastex').'</th>
			    <th class="tg-yw4l">'.__('Recipient','blastex').'</th>
			    <th class="tg-yw4l">'.__('Subject','blastex').'</th>
			    <th class="tg-yw4l">'.__('Error','blastex').'</th>
		  	</tr>';
		  	foreach ($rows as $r) {
				$er = '<span class="ok">Sent</span>';
				if($r->active == 0){ $er = '<span class="er">Error</span>'; }
			  	echo '<tr>
				    <td class="tg-yw4l">'.$r->uid.' ('.$r->user.')</td>
				    <td class="tg-yw4l">'.$r->efrom.'</td>
				    <td class="tg-yw4l">'.$r->eto.'</td>
				    <td class="tg-yw4l">'.$r->subject.'</td>
				    <td class="tg-yw4l">'.$er.'</td>
			  	</tr>';
		  	}
		  	if(count($rows) == 0){
				echo __('No messages. Send couple messages frist.', 'blastex');
			}
		echo '</table></div></div>';
	}

	function blastex_logs_page_old(){
		$rows = getEmailLogs();
		echo '<div class="wrap">
		    <div class="box">
				<h1>'.__('Sent emails (last 50)', 'blastex').'</h1>					
				<ul class="table">
				<li class="ttitle">'.__('UserID','blastex').'</li><li class="ttitle">'.__('Sender','blastex').'</li><li class="ttitle">'.__('Recipient','blastex').'</li><li class="ttitle">'.__('Subject','blastex').'</li><li class="ttitle">'.__('Error','blastex').'</li>
				';
				foreach ($rows as $r) {
					$er = '<span class="ok">Sent</span>';
					if($r->active == 0){ $er = '<span class="er">Error</span>'; }
					echo '<li>'.$r->uid.' ('.$r->user.')</li><li>'.$r->efrom.'</li><li>'.$r->eto.'</li><li>'.$r->subject.'</li><li>'.$er.'</li>';
				}
				if(count($rows) == 0){
					echo __('No messages. Send couple messages frist.', 'blastex');
				}
		echo '</ul></div></div></div>';
	}	
}// Version check
?>