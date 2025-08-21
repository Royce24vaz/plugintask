<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'cem_default_events' );
delete_option( 'cem_show_past_events' );
delete_option( 'cem_default_organizer' );

// NOTE: Event posts (cem_event) remain so user data is safe.

