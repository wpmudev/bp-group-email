<?php
/*
Plugin Name: BP Group Email
Version: 1.0.2
Plugin URI: http://incsub.com
Description: Adds email sending functionality to Buddypress Groups. Must be activated site-wide.
Author: Aaron Edwards at uglyrobot.com (for Incsub)
Author URI: http://uglyrobot.com
Site Wide Only: true

Copyright 2009-2010 Incsub (http://incsub.com)

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

$bp_group_email_current_version = '1.0.2';

//------------------------------------------------------------------------//

//---Hook-----------------------------------------------------------------//

//------------------------------------------------------------------------//

add_action( 'plugins_loaded', 'bp_group_email_localization' );
add_action( 'groups_screen_notification_settings', 'bp_group_email_notification_settings' );

//------------------------------------------------------------------------//

//---Functions------------------------------------------------------------//

//------------------------------------------------------------------------//

function bp_group_email_localization() {
  // Load up the localization file if we're using WordPress in a different language
	// Place it in this plugin's "languages" folder and name it "groupemail-[value in wp-config].mo"
	load_plugin_textdomain( 'groupemail', FALSE, '/bp-group-email/languages' );
}

//1.1 compatibility: http://buddypress.org/forums/topic/class-bp_group_extension-not-found-installing-plugin
function bp_group_email_load_buddypress() {
	//buddypress is loaded
	if ( function_exists( 'bp_core_setup_globals' ) )
		return false;

	// Get the list of active sitewide plugins
	$active_sitewide_plugins = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
	$bp_activated = $active_sitewide_plugins['buddypress/bp-loader.php'];

	//bp is not activated
	if ( !$bp_activated ){
		return false;
	}

	//bp is activated but not yet loaded
	if ( $bp_activated ) {
		return true;
	}

	return false;
}
//load bp if its not activated
if ( bp_group_email_load_buddypress() ){
	require_once( WP_PLUGIN_DIR . '/buddypress/bp-loader.php' );
}

//extend the group
class BP_Groupemail_Extension extends BP_Group_Extension {
  
  var $visibility = 'private'; // 'public' will show your extension to non-group members, 'private' means you have to be a member of the group to view your extension.
  var $enable_create_step = false; // If your extension does not need a creation step, set this to false
  //var $enable_nav_item = false; // If your extension does not need a navigation item, set this to false
  var $enable_edit_item = false; // If your extension does not need an edit screen, set this to false
  
	function bp_groupemail_extension() {
		$this->name = __( 'Send Email', 'groupemail' );
		$this->slug = 'email';

		//$this->create_step_position = 21;
		$this->nav_item_position = 35;
		$this->enable_nav_item = $this->bp_group_email_get_capabilities();
	}
  
	function display() {
		/* Use this function to display the actual content of your group extension when the nav item is selected */
		global $wpdb, $bp;
  
    $url = bp_get_group_permalink( $bp->groups->current_group ).'/email/';
    
    $group_id = bp_get_group_id();
    
    $email_capabilities = $this->bp_group_email_get_capabilities();

    //don't display widget if no capabilities
    if (!$email_capabilities) {
      bp_core_add_message( __("You don't have permission to send emails", 'groupemail'), 'error' );
      do_action( 'template_notices' );
      return false;
    }
    
    $email_success = $this->bp_group_email_send();
    
    if (!$email_success) {
      $email_subject = strip_tags(stripslashes(trim($_POST['email_subject'])));
      $email_text = strip_tags(stripslashes(trim($_POST['email_text'])));
    }
    
    do_action( 'template_notices' );
    ?>
    <div class="bp-widget">
  		<h4><?php _e('Send Email to Group', 'groupemail'); ?></h4>
      
      <form action="<?php echo $url; ?>" name="add-email-form" id="add-email-form" class="standard-form" method="post" enctype="multipart/form-data">
  			<label for="email_subject"><?php _e('Subject', 'groupemail'); ?> *</label>
  			<input name="email_subject" id="email_subject" value="<?php echo $email_subject; ?>" type="text">
  			
  			<label for="email_text"><?php _e('Email Text', 'groupemail'); ?> *
        <small><?php _e('(No HTML Allowed)', 'groupemail'); ?></small></p>
        </label>
  			<textarea name="email_text" id="email_text" rows="10"><?php echo $email_text; ?></textarea>
  			
        <input name="send_email" value="1" type="hidden">
        <?php wp_nonce_field('bp_group_email'); ?>
        
  			<p><input value="<?php _e('Send Email', 'groupemail'); ?> &raquo;" id="save" name="save" type="submit">
  			<small><?php _e('Note: This may take a while depending on the size of the group', 'groupemail'); ?></small></p>
  	 </form>
  		
    </div>
    <?php
	}
  
  function create_screen() {}
	function create_screen_save() {}
	function edit_screen() {}
	function edit_screen_save() {}
	function widget_display() {}
	
	function bp_group_email_get_capabilities() {
    //check if user is admin or moderator
    if ( bp_group_is_admin() || bp_group_is_mod() ) {  
      return true;
    } else {
      return false;
    }
  }
  
  //send the email
  function bp_group_email_send() {
    global $wpdb, $current_user, $bp;
  
    $email_capabilities = $this->bp_group_email_get_capabilities();
    
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
      $group_id = bp_get_group_id();
      
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
      $group_link = bp_get_group_permalink( $bp->groups->current_group ) . '/';
  
      $email_count = 0;
    	foreach ( $bp->groups->current_group->user_dataset as $user ) {
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
  
}
bp_register_group_extension( 'BP_Groupemail_Extension' );


//------------------------------------------------------------------------//

//---Output Functions-----------------------------------------------------//

//------------------------------------------------------------------------//

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

?>