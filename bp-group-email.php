<?php
/*
Plugin Name: BuddyPress Group Email
Version: 1.0.8
Plugin URI: https://premium.wpmudev.org/project/buddypress-group-email/
Description: This plugin adds group email functionality to BuddyPress allowing a group admin or moderator to send an email to all the other members in the group.
Author: WPMU DEV
Author URI: https://premium.wpmudev.org/
Network: true
Textdomain: groupemail
WDP ID: 110

Copyright 2009-2017 Incsub (http://incsub.com)
Author - Aaron Edwards
Contributors - 

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

$bp_group_email_current_version = '1.0.8';

/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */
function bp_group_email_init() {
	if (class_exists('BP_Group_Extension'))
		require_once( dirname( __FILE__ ) . '/includes/bp-group-email.php' );
}
add_action( 'bp_init', 'bp_group_email_init' );

function bp_group_email_localization() {
  // Load up the localization file if we're using WordPress in a different language
	// Place it in this plugin's "languages" folder and name it "groupemail-[value in wp-config].mo"
	load_plugin_textdomain( 'groupemail', FALSE, '/bp-group-email/languages' );
}
add_action( 'plugins_loaded', 'bp_group_email_localization' );

//load dashboard notice
global $wpmudev_notices;
$wpmudev_notices[] = array(
	'id'      => 110,
	'name'    => 'BuddyPress Group Email',
	'screens' => array(
		'toplevel_page_bp-groups',
		'settings_page_bp-components',
		'toplevel_page_bp-groups-network',
		'settings_page_bp-components-network'
	)
);
include_once( dirname( __FILE__ ) . '/dash-notice/wpmudev-dash-notification.php' );