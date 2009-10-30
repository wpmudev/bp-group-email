<?php 
$email_capabilities = bp_group_email_get_capabilities();

//don't display widget if no capabilities
if (!$email_capabilities) {
  bp_core_add_message( __("You don't have permission to send emails", 'groupemail'), 'error' );
  return false;
}

$email_success = bp_group_email_send();
?>

<?php do_action( 'template_notices' ); ?>

<?php if ( bp_has_groups() ) : while ( bp_groups() ) : bp_the_group(); ?>



<div class="left-menu">

	<?php load_template( TEMPLATEPATH . '/groups/single/menu.php' ); ?>

</div>



<div class="main-column">

	<div class="inner-tube">

        <div id="group-name">

            <h1><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></h1>

            <p class="status"><?php bp_group_type() ?></p>

        </div>

		<?php if ( bp_group_is_visible() ) : ?>

    <?php

      bp_group_email_form($email_success);
    
    ?>
  
		<?php endif;?>

	</div>

</div>

<?php endwhile; endif; ?>