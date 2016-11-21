<?php
/*
BP Group Email
*/

//------------------------------------------------------------------------//

//---Config---------------------------------------------------------------//

//------------------------------------------------------------------------//


//------------------------------------------------------------------------//

//---Hook-----------------------------------------------------------------//

//------------------------------------------------------------------------//


add_action( 'groups_screen_notification_settings', 'bp_group_email_notification_settings' );

//------------------------------------------------------------------------//

//---Functions------------------------------------------------------------//

//------------------------------------------------------------------------//



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
		//$this->nav_item_position = 35;
		$this->enable_nav_item = $this->bp_group_email_get_capabilities();
	}
  
	function display() {
		/* Use this function to display the actual content of your group extension when the nav item is selected */
		global $wpdb, $bp;
  
    $url = untrailingslashit( bp_get_group_permalink( $bp->groups->current_group ) ) . '/email/';
    
    $email_capabilities = $this->bp_group_email_get_capabilities();

    //don't display widget if no capabilities
    if (!$email_capabilities) {
      bp_core_add_message( __("You don't have permission to send emails", 'groupemail'), 'error' );
      do_action( 'template_notices' );
      return false;
    }
    
    $email_success = $this->bp_group_email_send();
    
    if (!$email_success) {
      $email_subject = strip_tags(stripslashes(trim(@$_POST['email_subject'])));
      $email_text = strip_tags(stripslashes(trim(@$_POST['email_text'])));
    } else {
			$email_subject = '';
      $email_text = '';
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
      
      $user_ids = BP_Groups_Member::get_group_member_ids($bp->groups->current_group->id);
      
      $email_count = 0;
    	foreach ($user_ids as $user_id) {
    	  //skip opt-outs
    		if ( 'no' == get_user_meta( $user_id, 'notification_groups_email_send', true ) ) continue;
    		
    		$ud = get_userdata( $user_id );
    		
    		// Set up and send the message
    		$to = $ud->user_email;

    		$group_link = site_url( $bp->groups->root_slug . '/' . $bp->groups->current_group->slug . '/' );
    		$settings_link = bp_core_get_user_domain( $user_id ) . 'settings/notifications/';
    
    		$message = sprintf( __( 
  '%s
  
  
  Sent by %s from the "%s" group: %s
  
  ---------------------
  ', 'groupemail' ), $email_text, get_blog_option( BP_ROOT_BLOG, 'blogname' ), stripslashes( esc_attr( $bp->groups->current_group->name ) ), $group_link );
    
    		$message .= sprintf( __( 'To unsubscribe from these emails please log in and go to: %s', 'groupemail' ), $settings_link );

    		// Send it
    		wp_mail( $to, $email_subject, $message );

    		unset( $message, $to );
    		$email_count++;
    	}
      
      //show success message
      if ($email_count) {
        bp_core_add_message( sprintf( __("The email was successfully sent to %d group members", 'groupemail'), $email_count) );
        return true;
      }
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
			<td class="yes"><input type="radio" name="notifications[notification_groups_email_send]" value="yes" <?php if ( !get_user_meta( $current_user->id, 'notification_groups_email_send', true) || 'yes' == get_user_meta( $current_user->id, 'notification_groups_email_send', true) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_groups_email_send]" value="no" <?php if ( 'no' == get_user_meta( $current_user->id, 'notification_groups_email_send', true) ) { ?>checked="checked" <?php } ?>/></td>
		</tr>
<?php
}

?>