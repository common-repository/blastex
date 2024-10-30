=== Blastex smtp client with SSL/TLS ===
Contributors: breakermind
Tags: smtp client, smtp, email, client, ssl smtp client, email client, wordpress smtp, wp smtp, breakermind
Donate link: PayPal: hello@breakermind.com
Requires at least: 4.7
Tested up to: 4.9
Requires PHP: 5.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SSL Smtp email client. Send html emails with attachments without smtp server from your blog.

== Description ==
Blastex SSL Smtp email client for wordpress.
Send html emails with attachments without smtp server from your blog.
You can send email from standard **wp_mail function**. 
Blastex gets recipient mx hostname from dns server and send email.
Send an email messages to gmail.com yahoo.com, outlook.com, ovh.com, hotmail.com.

About blastex:
* Php Ssl Smtp email client plugin for Wordpress
* Send emails without local smtp server from wordpress blog
* Send email form admin panel email form
* Languages: English, Polish

[Documentation](https://github.com/breakermind/Blastex_wp)
[Blastex plugin video](https://www.youtube.com/watch?v=ouAQpSdnNVA)

== Installation ==
1. Upload frolder from zip file to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Send emails from admin panel form Blastex SMTP >> Send email.
4. Place php function "wp_mail( 'email@email.com', 'Subject', 'Html message' );" in your templates and send email.
5. Place php function "wp_mail( 'email@email.com', 'Subject', 'Html message', '', array('path/to/file1', 'path/to/file2') );" in your templates and send email with attachment.
6. [Option] Set correct SPF (TXT) record in your domain dns: [TXT]  v=spf1 a mx ip4:1.2.3.4 a:your-blog-server.host -all (1.2.3.4 - your blog server ip address). If you don't want send messages as spam.

== Frequently Asked Questions ==
= Can I send email without local smtp server =
Yes, You can :).

== How to use ==
`
<?php
  !!! Enable first in php.ini config file socket extension. Read more in plugin admin panel after activation !!!
  
  $to = 'hello@emal.com, hello@boom.com';
  $subject 'Hello from email client';
  $html = '<h1>Hello message from smtp !!! </h1> <br> <p> Message from wordpress plugin! </p>';
  
  // Install and activate plugin and send emails
  $ok = wp_mail($to, $subject, $html);
  
  // Show error
  echo get_option('blastex_error');
  
?>
`
== Screenshots ==
1. Plugin admin panel.
2. How to use in php.
3. Send error message
4. Sent emails log

== Changelog ==
= 2.0 =
* Saving messages to database
* Display messages in admin panel

= 1.0 =
* SSL Smtp email client.
* Send attachments
* Multiple recipients
* Send form in admin panel for administrator

== Upgrade Notice ==
* Second version