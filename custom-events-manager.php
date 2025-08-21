<?php
/*
Plugin Name: Custom Events Manager
Description: Manage Events (CRUD) with date, location, organizer; admin settings; and frontend shortcode to list upcoming events.
Version: 1.0.0
Author: Royce Vaz
Text Domain: custom-events-manager
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ------------------------------
 * Constants
 * ------------------------------ */
define( 'CEM_VERSION', '1.0.0' );
define( 'CEM_TEXTDOMAIN', 'custom-events-manager' );

/* ------------------------------
 * Register Custom Post Type
 * ------------------------------ */
function cem_register_cpt_event() {
    $labels = array(
        'name'               => __( 'Events', CEM_TEXTDOMAIN ),
        'singular_name'      => __( 'Event', CEM_TEXTDOMAIN ),
        'add_new'            => __( 'Add New', CEM_TEXTDOMAIN ),
        'add_new_item'       => __( 'Add New Event', CEM_TEXTDOMAIN ),
        'edit_item'          => __( 'Edit Event', CEM_TEXTDOMAIN ),
        'new_item'           => __( 'New Event', CEM_TEXTDOMAIN ),
        'view_item'          => __( 'View Event', CEM_TEXTDOMAIN ),
        'search_items'       => __( 'Search Events', CEM_TEXTDOMAIN ),
        'not_found'          => __( 'No events found', CEM_TEXTDOMAIN ),
        'not_found_in_trash' => __( 'No events found in Trash', CEM_TEXTDOMAIN ),
        'menu_name'          => __( 'Events', CEM_TEXTDOMAIN ),
    );

    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite'       => array( 'slug' => 'events' ),
    );

    register_post_type( 'cem_event', $args );
}
add_action( 'init', 'cem_register_cpt_event' );

/* Flush rewrite rules on activation/deactivation */
function cem_activate_plugin() {
    cem_register_cpt_event();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cem_activate_plugin' );

function cem_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cem_deactivate_plugin' );

/* ------------------------------
 * Meta Boxes (Date, Location, Organizer)
 * ------------------------------ */
function cem_add_meta_boxes() {
    add_meta_box(
        'cem_event_details',
        __( 'Event Details', CEM_TEXTDOMAIN ),
        'cem_render_event_meta_box',
        'cem_event',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cem_add_meta_boxes' );

function cem_render_event_meta_box( $post ) {
    wp_nonce_field( 'cem_save_event_meta', 'cem_event_meta_nonce' );

    $date      = get_post_meta( $post->ID, 'cem_event_date', true );
    $location  = get_post_meta( $post->ID, 'cem_event_location', true );
    $organizer = get_post_meta( $post->ID, 'cem_event_organizer', true );

    // Simple inline styles so it looks decent in admin
    ?>
    <p>
        <label for="cem_event_date"><strong><?php _e( 'Event Date', CEM_TEXTDOMAIN ); ?></strong></label><br>
        <input type="date" id="cem_event_date" name="cem_event_date" value="<?php echo esc_attr( $date ); ?>">
    </p>

    <p>
        <label for="cem_event_location"><strong><?php _e( 'Location', CEM_TEXTDOMAIN ); ?></strong></label><br>
        <input type="text" id="cem_event_location" name="cem_event_location" class="regular-text" value="<?php echo esc_attr( $location ); ?>">
    </p>

    <p>
        <label for="cem_event_organizer"><strong><?php _e( 'Organizer', CEM_TEXTDOMAIN ); ?></strong></label><br>
        <input type="text" id="cem_event_organizer" name="cem_event_organizer" class="regular-text" value="<?php echo esc_attr( $organizer ); ?>">
    </p>
    <?php
}

function cem_save_event_meta( $post_id ) {
    // Verify nonce
    if ( ! isset( $_POST['cem_event_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cem_event_meta_nonce'], 'cem_save_event_meta' ) ) {
        return;
    }

    // Autosave check
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Permissions
    if ( isset( $_POST['post_type'] ) && 'cem_event' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Sanitize and save
    $date      = isset( $_POST['cem_event_date'] ) ? sanitize_text_field( $_POST['cem_event_date'] ) : '';
    $location  = isset( $_POST['cem_event_location'] ) ? sanitize_text_field( $_POST['cem_event_location'] ) : '';
    $organizer = isset( $_POST['cem_event_organizer'] ) ? sanitize_text_field( $_POST['cem_event_organizer'] ) : '';

    if ( $date === '' ) {
        delete_post_meta( $post_id, 'cem_event_date' );
    } else {
        update_post_meta( $post_id, 'cem_event_date', $date );
    }

    if ( $location === '' ) {
        delete_post_meta( $post_id, 'cem_event_location' );
    } else {
        update_post_meta( $post_id, 'cem_event_location', $location );
    }

    if ( $organizer === '' ) {
        delete_post_meta( $post_id, 'cem_event_organizer' );
    } else {
        update_post_meta( $post_id, 'cem_event_organizer', $organizer );
    }
}
add_action( 'save_post_cem_event', 'cem_save_event_meta' ); // only for our CPT

/* Auto-fill default organizer if empty (after save) */
function cem_fill_default_organizer( $post_id, $post, $update ) {
    if ( $post->post_type !== 'cem_event' ) return;
    $organizer = get_post_meta( $post_id, 'cem_event_organizer', true );
    if ( empty( $organizer ) ) {
        $default = get_option( 'cem_default_organizer', '' );
        if ( $default !== '' ) {
            update_post_meta( $post_id, 'cem_event_organizer', sanitize_text_field( $default ) );
        }
    }
}
add_action( 'save_post', 'cem_fill_default_organizer', 20, 3 );

/* ------------------------------
 * Admin Columns for Events list
 * ------------------------------ */
function cem_event_columns( $columns ) {
    $cols = array();
    $cols['cb']    = $columns['cb'];
    $cols['title'] = __( 'Title', CEM_TEXTDOMAIN );
    $cols['cem_date'] = __( 'Date', CEM_TEXTDOMAIN );
    $cols['cem_location'] = __( 'Location', CEM_TEXTDOMAIN );
    $cols['cem_organizer'] = __( 'Organizer', CEM_TEXTDOMAIN );
    $cols['date'] = $columns['date'];
    return $cols;
}
add_filter( 'manage_cem_event_posts_columns', 'cem_event_columns' );

function cem_event_columns_render( $column, $post_id ) {
    if ( 'cem_date' === $column ) {
        $date = get_post_meta( $post_id, 'cem_event_date', true );
        echo $date ? esc_html( $date ) : '—';
    }
    if ( 'cem_location' === $column ) {
        $location = get_post_meta( $post_id, 'cem_event_location', true );
        echo $location ? esc_html( $location ) : '—';
    }
    if ( 'cem_organizer' === $column ) {
        $org = get_post_meta( $post_id, 'cem_event_organizer', true );
        if ( empty( $org ) ) $org = get_option( 'cem_default_organizer', '' );
        echo $org ? esc_html( $org ) : '—';
    }
}
add_action( 'manage_cem_event_posts_custom_column', 'cem_event_columns_render', 10, 2 );

/* Make Date column sortable */
function cem_sortable_columns( $columns ) {
    $columns['cem_date'] = 'cem_date';
    return $columns;
}
add_filter( 'manage_edit-cem_event_sortable_columns', 'cem_sortable_columns' );

function cem_orderby_date( $query ) {
    if ( ! is_admin() ) return;
    $orderby = $query->get( 'orderby' );
    if ( 'cem_date' === $orderby ) {
        $query->set( 'meta_key', 'cem_event_date' );
        $query->set( 'orderby', 'meta_value' );
    }
}
add_action( 'pre_get_posts', 'cem_orderby_date' );

/* ------------------------------
 * Settings Page (under Events)
 * ------------------------------ */
function cem_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=cem_event',
        __( 'Events Settings', CEM_TEXTDOMAIN ),
        __( 'Settings', CEM_TEXTDOMAIN ),
        'manage_options',
        'cem-settings',
        'cem_render_settings_page'
    );
}
add_action( 'admin_menu', 'cem_add_settings_page' );

function cem_register_settings() {
    register_setting( 'cem_settings_group', 'cem_default_events', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 5,
    ) );

    register_setting( 'cem_settings_group', 'cem_show_past_events', array(
        'type' => 'boolean',
        'sanitize_callback' => function( $v ){ return (bool) $v; },
        'default' => false,
    ) );

    register_setting( 'cem_settings_group', 'cem_default_organizer', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ) );

    add_settings_section(
        'cem_general',
        __( 'General Settings', CEM_TEXTDOMAIN ),
        '__return_false',
        'cem-settings'
    );

    add_settings_field(
        'cem_default_events',
        __( 'Default number of events to show', CEM_TEXTDOMAIN ),
        function() {
            $val = get_option( 'cem_default_events', 5 );
            echo '<input type="number" min="1" name="cem_default_events" value="' . esc_attr( $val ) . '">';
        },
        'cem-settings',
        'cem_general'
    );

    add_settings_field(
        'cem_show_past_events',
        __( 'Include past events by default?', CEM_TEXTDOMAIN ),
        function() {
            $val = get_option( 'cem_show_past_events', false );
            echo '<label><input type="checkbox" name="cem_show_past_events" value="1" ' . checked( true, (bool) $val, false ) . '> ' . esc_html__( 'Show events dated before today', CEM_TEXTDOMAIN ) . '</label>';
        },
        'cem-settings',
        'cem_general'
    );

    add_settings_field(
        'cem_default_organizer',
        __( 'Default organizer (optional)', CEM_TEXTDOMAIN ),
        function() {
            $val = get_option( 'cem_default_organizer', '' );
            echo '<input type="text" name="cem_default_organizer" class="regular-text" value="' . esc_attr( $val ) . '">';
            echo '<p class="description">' . esc_html__( 'Used when an event has no organizer specified.', CEM_TEXTDOMAIN ) . '</p>';
        },
        'cem-settings',
        'cem_general'
    );
}
add_action( 'admin_init', 'cem_register_settings' );

function cem_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Events Settings', CEM_TEXTDOMAIN ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'cem_settings_group' );
            do_settings_sections( 'cem-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/* ------------------------------
 * Shortcode: [cem_events limit="5" include_past="0|1"]
 * ------------------------------ */
function cem_events_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit'        => get_option( 'cem_default_events', 5 ),
        'include_past' => get_option( 'cem_show_past_events', false ) ? '1' : '0',
    ), $atts, 'cem_events' );

    $limit = max( 1, absint( $atts['limit'] ) );
    $include_past = $atts['include_past'] === '1';

    $today = date( 'Y-m-d' );

    $meta_query = array();
    if ( ! $include_past ) {
        $meta_query[] = array(
            'key'     => 'cem_event_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        );
    }

    $args = array(
        'post_type'      => 'cem_event',
        'posts_per_page' => $limit,
        'meta_key'       => 'cem_event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => $meta_query,
    );

    $q = new WP_Query( $args );

    if ( ! $q->have_posts() ) {
        return '<p>' . esc_html__( 'No events found.', CEM_TEXTDOMAIN ) . '</p>';
    }

    ob_start();
    echo '<div class="cem-events-list">';
    while ( $q->have_posts() ) {
        $q->the_post();
        $date      = get_post_meta( get_the_ID(), 'cem_event_date', true );
        $location  = get_post_meta( get_the_ID(), 'cem_event_location', true );
        $organizer = get_post_meta( get_the_ID(), 'cem_event_organizer', true );

        if ( empty( $organizer ) ) {
            $organizer = get_option( 'cem_default_organizer', '' );
        }
        ?>
        <article class="cem-event" style="border:1px solid #eee;padding:12px;margin-bottom:12px;border-radius:6px;">
            <h3 style="margin:0 0 6px;"><?php echo esc_html( get_the_title() ); ?></h3>
            <div style="font-size:13px;margin-bottom:8px;color:#333;">
                <strong><?php _e( 'Date:', CEM_TEXTDOMAIN ); ?></strong> <?php echo esc_html( $date ); ?>
                <?php if ( $location ) : ?> | <strong><?php _e( 'Location:', CEM_TEXTDOMAIN ); ?></strong> <?php echo esc_html( $location ); ?><?php endif; ?>
                <?php if ( $organizer ) : ?> | <strong><?php _e( 'Organizer:', CEM_TEXTDOMAIN ); ?></strong> <?php echo esc_html( $organizer ); ?><?php endif; ?>
            </div>
            <div class="cem-content"><?php the_content(); ?></div>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'cem_events', 'cem_events_shortcode' );

/* ------------------------------
 * Shortcode helper: usage in README style
 * ------------------------------ */
/*
Usage:
 - Add events in WP Admin -> Events -> Add New (set title, content, set date/location/organizer in Event Details meta box)
 - Settings -> Events -> Settings to change defaults
 - Insert into page/post:
    [cem_events]
    Optional attributes:
      limit="10"         (number of events)
      include_past="1"   (show past events too)
 Example:
    [cem_events limit="8" include_past="1"]
*/

/* ------------------------------
 * End of plugin file
 * ------------------------------ */
