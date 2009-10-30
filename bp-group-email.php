<?php
/*
Plugin Name: BP Group Email
Version: 1.0.0
Plugin URI: http://incsub.com
Description: Adds email sending functionality to Buddypress Groups. Must be activated site-wide.
Author: Aaron Edwards at uglyrobot.com (for Incsub)
Author URI: http://uglyrobot.com
Site Wide Only: true

Copyright 2009 Incsub (http://incsub.com)

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


$bp_group_email_current_version = '1.0.0';

//------------------------------------------------------------------------//

//---Config---------------------------------------------------------------//

//------------------------------------------------------------------------//



//------------------------------------------------------------------------//

//---Hook-----------------------------------------------------------------//

//------------------------------------------------------------------------//

add_action( 'wp', 'bp_group_email_menu');
add_action( 'admin_menu', 'bp_group_email_menu');
add_action( 'plugins_loaded', 'bp_group_email_menu' );
add_action( 'plugins_loaded', 'bp_group_email_localization' );
add_action( 'groups_screen_notification_settings', 'bp_group_email_notification_settings' );

//------------------------------------------------------------------------//

//---Functions------------------------------------------------------------//

//------------------------------------------------------------------------//

function bp_group_email_localization() {
  // Load up the localization file if we're using WordPress in a different language
	// Place it in this plugin's "languages" folder and name it "bp-group-email-[value in wp-config].mo"
	load_plugin_textdomain( 'groupemail', FALSE, '/bp-group-email/languages' );
}


function bp_group_email_menu() {

	global $bp, $current_blog, $group_obj;

	if ( $group_id = BP_Groups_Group::group_exists($bp->current_action) ) {

		/* This is a single group page. */
		$bp->is_single_item = true;
		$bp->groups->current_group = &new BP_Groups_Group( $group_id );

		/* Pre 1.1 backwards compatibility - use $bp->groups->current_group instead */
		$group_obj = &$bp->groups->current_group;

	}	

	//$groups_link = $bp->loggedin_user->domain . $bp->groups->slug . '/';
	$groups_link = $bp->root_domain . '/' . $bp->groups->slug . '/' . $bp->groups->current_group->slug . '/';

	/* Add the subnav item only to the single group nav item */
	if ( $bp->is_single_item && bp_group_email_get_capabilities())
    bp_core_new_subnav_item( array( 'name' => __( 'Send Email', 'groupemail' ), 'slug' => 'email', 'parent_url' => $groups_link, 'parent_slug' => $bp->groups->slug, 'screen_function' => 'bp_group_email', 'position' => 55, 'item_css_id' => 'group-email' ) );

}


function bp_group_email() {

	global $bp;

	add_action( 'bp_template_content', 'bp_group_email_output' );

	bp_core_load_template( 'plugin-template' );

}


function bp_group_email_send() {
  global $wpdb, $current_user, $bp;

  $email_capabilities = bp_group_email_get_capabilities();
  
  if (isset($_POST['send_email'])) {
    if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bp_group_email')) {
      bp_core_add_message( __('There was a security problem', 'groupemail'), 'error' );
      return false;
    }
    
    //reject unqualified users
    if (!$email_capabilities) {
      bp_core_add_message( __("You don't have permission to send emails", 'groupemail'), 'error' );
      return false;
    }
    
    //prepare fields
    $group_id = (int)$_POST['group-id'];
    
    $email_subject = strip_tags(stripslashes(trim($_POST['email_subject'])));
    
    //check that required title isset after filtering
    if (empty($email_subject)) {
      bp_core_add_message( __("A subject is required", 'groupemail'), 'error' );
      return false;
    }
    
    $email_text = strip_tags(stripslashes(trim($_POST['email_text'])));
    
    //check that required title isset after filtering
    if (empty($email_text)) {
      bp_core_add_message( __("Email text is required", 'groupemail'), 'error' );
      return false;
    }

    //send emails
  	$group = new BP_Groups_Group( $group_id, false, true );
    $group_link = bp_get_group_permalink( $bp->groups->current_group ) . '/';
    
  	//$subject = '[' . get_blog_option( BP_ROOT_BLOG, 'blogname' ) . '] ' . sprintf( __( 'New message from group: %s', 'groupemail' ), stripslashes( attribute_escape( $group->name ) ) );
    $email_count = 0;
  	foreach ( $group->user_dataset as $user ) {
  	  //skip opt-outs
  		if ( 'no' == get_usermeta( $user->user_id, 'notification_groups_email_send' ) ) continue;
  		
  		$ud = get_userdata( $user->user_id );
  		
  		// Set up and send the message
  		$to = $ud->user_email;
      
  		$group_link = site_url( $bp->groups->slug . '/' . $group->slug . '/' );
  		$settings_link = bp_core_get_user_domain( $user->user_id ) . 'settings/notifications/'; 
  
  		$message = sprintf( __( 
'%s


Sent by %s from the "%s" group: %s

---------------------
', 'groupemail' ), $email_text, get_blog_option( BP_ROOT_BLOG, 'blogname' ), stripslashes( attribute_escape( $group->name ) ), $group_link );
  
  		$message .= sprintf( __( 'To unsubscribe from these emails please log in and go to: %s', 'groupemail' ), $settings_link );
  
  		// Send it
  		wp_mail( $to, $email_subject, $message );
  		
  		unset( $message, $to );
  		$email_count++;
  	}
    
    //show success message
    bp_core_add_message( sprintf( __("The email was successfully sent to %d group members", 'groupemail'), $email_count) );
    return true;
  } else {
    return false;
  }
}



function bp_group_email_get_capabilities() {
  //check if user is admin or moderator
  if ( bp_group_is_admin() || bp_group_is_mod() ) {  
    return true;
  } else {
    return false;
  }
   
}


//------------------------------------------------------------------------//

//---Output Functions-----------------------------------------------------//

//------------------------------------------------------------------------//


function bp_group_email_output() {

	if ( file_exists( STYLESHEETPATH . '/groupemail/email.php' ) ) {

	    load_template( STYLESHEETPATH . '/groupemail/email.php' );

	} else {

    	load_template( WP_PLUGIN_DIR . '/bp-group-email/groupemail/email.php' );

	}

}

function bp_group_email_notification_settings() {
  global $current_user;
    
?>
		<tr>
			<td></td>
			<td><?php _e( 'An email is sent to the group by an admin or moderator', 'groupemail' ) ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_groups_email_send]" value="yes" <?php if ( !get_usermeta( $current_user->id, 'notification_groups_email_send') || 'yes' == get_usermeta( $current_user->id, 'notification_groups_email_send') ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_email_send]" value="no" <?php if ( 'no' == get_usermeta( $current_user->id, 'notification_groups_email_send') ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
<?php
}
//------------------------------------------------------------------------//

//---Page Output Functions------------------------------------------------//

//------------------------------------------------------------------------//

//widgets

function bp_group_email_form($email_success) {
  global $wpdb, $bp;
  
  $url = bp_get_group_permalink( $bp->groups->current_group ).'/email/';
  
  $group_id = bp_get_group_id();
  
  $email_capabilities = bp_group_email_get_capabilities();
  
  //don't display widget if no capabilities
  if (!$email_capabilities) {
    return false;
  }
  
  if (!$email_success) {
    $email_subject = strip_tags(stripslashes(trim($_POST['email_subject'])));
    $email_text = strip_tags(stripslashes(trim($_POST['email_text'])));
  }
  
  ?>
  <div class="bp-widget">
		<h4><?php _e('Send Email to Group', 'groupemail'); ?></h4>
    
    <form action="<?php echo $url; ?>" name="add-email-form" id="add-email-form" class="standard-form" method="post" enctype="multipart/form-data">
			<label for="email_subject"><?php _e('Subject', 'groupemail'); ?> *</label>
			<input name="email_subject" id="email_subject" value="<?php echo $email_subject; ?>" type="text">
			
			<label for="email_text"><?php _e('Email Text', 'groupemail'); ?>
      <small><?php _e('(No HTML Allowed)', 'groupemail'); ?></small></p>
      </label>
			<textarea name="email_text" id="email_text" rows="10"><?php echo $email_text; ?></textarea>
			
      <input name="send_email" value="1" type="hidden">
      <input name="group-id" id="group-id" value="<?php echo bp_get_group_id(); ?>" type="hidden">
      <?php wp_nonce_field('bp_group_email'); ?>
      
			<p><input value="<?php _e('Send Email', 'groupemail'); ?> &raquo;" id="save" name="save" type="submit">
			<small><?php _e('Note: This may take a while depending on the size of the group', 'groupemail'); ?></small></p>
	 </form>
		
  </div>
  <?php
}
//------------------------------------------------------------------------//

//---Support Functions----------------------------------------------------//

//------------------------------------------------------------------------//



?>