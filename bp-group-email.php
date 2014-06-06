<?php
/*
Plugin Name: BP Group Email
Version: 1.0.6
Plugin URI: http://premium.wpmudev.org/project/buddypress-group-email/
Description: This plugin adds group email functionality to BuddyPress allowing a group admin or moderator to send an email to all the other members in the group.
Author: WPMU DEV
Author URI: http://uglyrobot.com
Network: true
Textdomain: groupemail
WDP ID: 110

Copyright 2009-2014 Incsub (http://incsub.com)
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

//------------------------------------------------------------------------//

//---Config---------------------------------------------------------------//

//------------------------------------------------------------------------//

$bp_group_email_current_version = '1.0.6';

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

///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
      echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
  }
}
/* --------------------------------------------------------------------- */
?>