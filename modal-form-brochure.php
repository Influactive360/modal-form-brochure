<?php
/*
Plugin Name: Modal Form Brochure
Description: A simple plugin that displays a modal with a form when the user clicks on a link with the id #brochure and a parameter ?file=FILE_ID.
Version: 1.0
Author: Influactive
Author URI: https://influactive.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function load_modal_form_scripts(): void {
	wp_enqueue_script( 'modal-form-brochure', plugin_dir_url( __FILE__ ) . 'dist/index.mjs', array(), '1.0', true );
	wp_enqueue_style( 'modal-form-brochure', plugin_dir_url( __FILE__ ) . 'dist/style.css' );
}

add_action( 'wp_enqueue_scripts', 'load_modal_form_scripts' );

function load_admin_scripts( $hook ): void {
	if ( 'settings_page_modal-form-options' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'modal-form-brochure-admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array(), '1.0', true );
	wp_enqueue_style( 'modal-form-brochure-admin', plugin_dir_url( __FILE__ ) . 'assets/css/admin-style.css' );
}

add_action( 'admin_enqueue_scripts', 'load_admin_scripts' );

function add_modal_form(): void {
	$fields      = get_option( 'modal_form_fields', array() );
	$title       = get_option( 'modal_form_title', 'Do you want to download this product sheet?' );
	$description = get_option( 'modal_form_description', 'In order to receive your product sheet, please fill in your information below, we will send you a link by email to download it.' );
	$submit_text = get_option( 'modal_form_submit_text', 'Submit' );

	ob_start(); ?>
    <div id="modal-form" class="modal-form">
        <div class="modal-content">
            <span id="modal-form-close" class="close">&times;</span>
            <h2><?= $title ?></h2>
            <hr>
            <p class="description"><?= $description ?></p>
            <form action="<?= plugin_dir_url( __FILE__ ) . 'process-form.php' ?>" method="post">
				<?php foreach ( $fields as $field ) : ?>
                    <div class="form-group" data-type="<?= $field['type'] ?>">
                        <label for="<?= $field['name'] ?>"
                               data-type="<?= $field['type'] ?>"><?= $field['label'] ?></label>
						<?php if ( $field['type'] === 'textarea' ) : ?>
                            <textarea id="<?= $field['name'] ?>" name="<?= $field['name'] ?>"
                                      rows="6" <?= $field['required'] ? 'required' : '' ?>></textarea>
						<?php else : ?>
                            <input type="<?= $field['type'] ?>" id="<?= $field['name'] ?>"
                                   name="<?= $field['name'] ?>" <?= $field['required'] ? 'required' : '' ?>>
						<?php endif; ?>
                    </div>
				<?php endforeach ?>
				<?php if ( count( $fields ) > 0 ) : ?>
                    <input type="submit" value="<?= $submit_text ?>">
				<?php endif; ?>
            </form>
            <div class="message"></div>
        </div>
    </div>
	<?= ob_get_clean();
}

add_action( 'wp_footer', 'add_modal_form' );

function modal_form_options_page(): void {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Start output buffering
	ob_start();
	?>
    <div class="wrap">
        <h1><?= esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
			<?php
			settings_fields( 'modal_form_options' );
			do_settings_sections( 'modal-form-options' );
			submit_button( __( 'Save Settings', 'modal-form-brochure' ) );
			?>
        </form>
    </div>
	<?php
	// Output the content of the buffer
	echo ob_get_clean();
}

function modal_form_settings_init(): void {
	register_setting( 'modal_form_options', 'modal_form_fields', 'modal_form_options_validate' );
	register_setting( 'modal_form_options', 'modal_form_title', 'sanitize_text_field' );
	register_setting( 'modal_form_options', 'modal_form_description', 'sanitize_text_field' );
	register_setting( 'modal_form_options', 'modal_form_submit_text', 'sanitize_text_field' );
	register_setting( 'modal_form_options', 'modal_form_email_recipient' );
	register_setting( 'modal_form_options', 'modal_form_email_field' );
	register_setting( 'modal_form_options', 'modal_form_name_field' );
	register_setting( 'modal_form_options', 'modal_form_rgpd_field' );
	register_setting( 'modal_form_options', 'modal_form_head_email', '' );
	register_setting( 'modal_form_options', 'modal_form_footer_email', '' );
	register_setting( 'modal_form_options', 'modal_form_pages' );
	register_setting( 'modal_form_options', 'modal_form_posts' );
	add_settings_section( 'modal_form_main', 'Main Settings', 'modal_form_fields_callback', 'modal-form-options' );
}

add_action( 'admin_init', 'modal_form_settings_init' );

function modal_form_fields_callback(): void {
	$form_fields      = get_option( 'modal_form_fields' );
	$form_title       = get_option( 'modal_form_title' );
	$form_description = get_option( 'modal_form_description' );
	$form_submit_text = get_option( 'modal_form_submit_text' );
	$email_recipient  = get_option( 'modal_form_email_recipient', get_bloginfo( 'admin_email' ) );
	$email_field      = get_option( 'modal_form_email_field', get_bloginfo( 'admin_email' ) );
	$name_field       = get_option( 'modal_form_name_field', 'Name' );
	$rgpd             = get_option( 'modal_form_rgpd_field', 'I agree to receive emails from this website' );
	$head_email       = get_option( 'modal_form_head_email', 'Hello,' );
	$footer_email     = get_option( 'modal_form_footer_email', 'Goodbye.' );
	?>
    <div class="columns-brochure">
        <div class="column-one">
            <div id="content-edit">
                <label for="modal_form_title"><?= __( 'Form Title:', 'modal-form-brochure' ) ?></label>
                <input id="modal_form_title" type="text" name="modal_form_title"
                       value="<?= esc_attr( $form_title ?? 'Do you want to download this product sheet?' ) ?>">
                <label for="modal_form_description"><?= __( 'Form Description:', 'modal-form-brochure' ) ?></label>
                <textarea id="modal_form_description" name="modal_form_description"
                          rows="6"><?= esc_attr( $form_description ?? 'In order to receive your product sheet, please fill in your information below, we will send you a link by email to download it.' ) ?></textarea>
                <label for="modal_form_submit_text"><?= __( 'Submit Button Text:', 'modal-form-brochure' ) ?></label>
                <input id="modal_form_submit_text" type="text" name="modal_form_submit_text"
                       value="<?= esc_attr( $form_submit_text ?? 'Submit' ) ?>">
                <label for="modal_form_head_email"><?= __( 'Email text header', 'modal-form-brochure' ) ?></label>
                <textarea id="modal_form_head_email"
                          name="modal_form_head_email"><?= esc_attr( $head_email ?? '' ) ?></textarea>
                <label for="modal_form_footer_email"><?= __( 'Email text footer', 'modal-form-brochure' ) ?></label>
                <textarea id="modal_form_footer_email"
                          name="modal_form_footer_email"><?= esc_attr( $footer_email ?? '' ) ?></textarea>
            </div>
            <div id="content-select">
                <label for="modal_form_pages"><?= __( 'Select Pages:', 'modal-form-brochure' ) ?></label>
				<?php
				// Get selected pages
				$selected_pages = get_option( 'modal_form_pages' );
				if ( ! is_array( $selected_pages ) ) {
					$selected_pages = array();
				}
				$args         = array(
					'name'             => 'modal_form_pages[]',
					'selected'         => $selected_pages,
					'echo'             => 0,
					'show_option_none' => __( '- Select -', 'modal-form-brochure' ),
				);
				$pages_select = wp_dropdown_pages( $args );
				$pages_select = str_replace( '<select', '<select multiple', $pages_select );
				echo $pages_select;
				?>
            </div>
            <div id="content-select-posts">
                <label for="modal_form_posts"><?= __( 'Select Posts:', 'modal-form-brochure' ) ?></label>
				<?php
				// Get selected posts
				$selected_posts = get_option( 'modal_form_posts' );
				if ( ! is_array( $selected_posts ) ) {
					$selected_posts = array();
				}
				// Query for all posts
				$args        = array(
					'post_type'   => [ 'post', 'page' ],
					'post_status' => 'publish',
					'nopaging'    => true,
				);
				$posts_query = new WP_Query( $args );
				?>
                <select id="modal_form_posts" name="modal_form_posts[]" multiple>
                    <option value=""><?= __( '- Select -', 'modal-form-brochure' ) ?></option>
					<?php
					if ( $posts_query->have_posts() ) {
						while ( $posts_query->have_posts() ) {
							$posts_query->the_post();
							$selected = in_array( get_the_ID(), $selected_posts, true ) ? 'selected' : '';
							?>
                            <option value="<?php the_ID(); ?>" <?= $selected ?>><?php the_title(); ?></option>
							<?php
						}
						wp_reset_postdata();
					}
					?>
                </select>
            </div>
            <div id="recipient-fields">
                <label for="email_field"><?= __( 'User Email Field:', 'modal-form-brochure' ) ?></label>
                <select id="email_field" name="modal_form_email_field">
					<?php foreach ( $form_fields as $field ) : ?>
                        <option
                                value="<?= $field['name'] ?>" <?= $field['name'] === $email_field ? 'selected' : '' ?>><?= $field['label'] ?></option>
					<?php endforeach; ?>
                </select>
                <label for="name_field"><?= __( 'User Name Field:', 'modal-form-brochure' ) ?></label>
                <select id="name_field" name="modal_form_name_field">
					<?php foreach ( $form_fields as $field ) : ?>
                        <option
                                value="<?= $field['name'] ?>" <?= $field['name'] === $name_field ? 'selected' : '' ?>><?= $field['label'] ?></option>
					<?php endforeach; ?>
                </select>
                <label for="rgpd_field"><?= __( 'RGPD Field:', 'modal-form-brochure' ) ?></label>
                <select id="rgpd_field" name="modal_form_rgpd_field">
					<?php foreach ( $form_fields as $field ) :
						if ( $field['type'] !== 'checkbox' ) {
							continue;
						}
						?>
                        <option
                                value="<?= $field['name'] ?>" <?= $field['name'] === $rgpd ? 'selected' : '' ?>><?= $field['label'] ?></option>
					<?php endforeach; ?>
                </select>
            </div>
            <div id="email-recipient">
                <label for="email_recipient"><?= __( 'Admin Email Recipient:', 'modal-form-brochure' ) ?></label>
                <input id="email_recipient" type="text" name="modal_form_email_recipient"
                       value="<?= esc_attr( $email_recipient ) ?>">
            </div>
        </div>
        <div class="column-two">
            <div id="form-fields">
				<?php if ( is_array( $form_fields ) ) { ?>
					<?php foreach ( $form_fields as $field ) : ?>
                        <div class="field">
                            <label for="type_<?= $field['name'] ?>"><?= __( 'Type:', 'modal-form-brochure' ) ?></label>
                            <select id="type_<?= $field['name'] ?>" name="modal_form_fields[field_type][]">
                                <option
                                        value="text" <?= $field['type'] === 'text' ? 'selected' : '' ?>><?= __( 'Text', 'modal-form-brochure' ) ?></option>
                                <option
                                        value="email" <?= $field['type'] === 'email' ? 'selected' : '' ?>><?= __( 'Email', 'modal-form-brochure' ) ?></option>
                                <option
                                        value="number" <?= $field['type'] === 'number' ? 'selected' : '' ?>><?= __( 'Number', 'modal-form-brochure' ) ?>
                                </option>
                                <option
                                        value="date" <?= $field['type'] === 'date' ? 'selected' : '' ?>><?= __( 'Date', 'modal-form-brochure' ) ?></option>
                                <option
                                        value="checkbox" <?= $field['type'] === 'checkbox' ? 'selected' : '' ?>><?= __( 'Checkbox', 'modal-form-brochure' ) ?>
                                </option>
                                <option
                                        value="radio" <?= $field['type'] === 'radio' ? 'selected' : '' ?>><?= __( 'Radio', 'modal-form-brochure' ) ?></option>
                                <option
                                        value="textarea" <?= $field['type'] === 'textarea' ? 'selected' : '' ?>><?= __( 'Textarea', 'modal-form-brochure' ) ?>
                                </option>
                            </select>
                            <label for="label_<?= $field['name'] ?>"><?= __( 'Label:', 'modal-form-brochure' ) ?></label>
                            <input id="label_<?= $field['name'] ?>" type="text" name="modal_form_fields[field_label][]"
                                   value="<?= esc_attr( $field['label'] ?? '' ) ?>">
                            <label for="name_<?= $field['name'] ?>"><?= __( 'Name:', 'modal-form-brochure' ) ?></label>
                            <input id="name_<?= $field['name'] ?>" type="text" name="modal_form_fields[field_name][]"
                                   value="<?= esc_attr( $field['name'] ?? '' ) ?>">
                            <p><strong><?= __( 'Required', 'modal-form-brochure' ) ?>:</strong></p>
                            <input id="required_yes_<?= $field['name'] ?>" type="radio"
                                   name="modal_form_fields[field_required][<?= $field['name'] ?>]" <?= $field['required'] === 'yes' ? 'checked' : '' ?>
                                   value="yes">
                            <label
                                    for="required_yes_<?= $field['name'] ?>"><?= __( 'Yes', 'modal-form-brochure' ) ?></label>
                            <input id="required_no_<?= $field['name'] ?>" type="radio"
                                   name="modal_form_fields[field_required][<?= $field['name'] ?>]" <?= $field['required'] === 'no' ? 'checked' : '' ?>
                                   value="no">
                            <label
                                    for="required_no_<?= $field['name'] ?>"><?= __( 'No', 'modal-form-brochure' ) ?></label>
							<?php submit_button( __( 'Delete', 'modal-form-brochure' ), 'delete-field', 'delete-field', false, array( 'data-id' => $field['name'] ) ); ?>
                        </div>
					<?php endforeach; ?>
				<?php } ?>
            </div>
			<?php submit_button( 'Add Field', 'add-field', 'add-field', false ); ?>
        </div>
    </div>
	<?php
}

add_action( 'admin_menu', static function () {
	add_options_page( 'Modal Form Brochure Options', 'Modal Form Options', 'manage_options', 'modal-form-options', 'modal_form_options_page' );
} );

function modal_form_options_validate( &$input ): array {
	// Initialize the new array that will hold the sanitize values
	$new_input = array();

	if ( is_array( $input ) ) {
		foreach ( $input['field_type'] as $key => $value ) {
			if ( is_string( $input['field_type'][ $key ] ) && is_string( $input['field_label'][ $key ] ) && is_string( $input['field_name'][ $key ] ) ) {
				$new_input[] = array(
					'type'     => sanitize_text_field( $input['field_type'][ $key ] ),
					'label'    => sanitize_text_field( $input['field_label'][ $key ] ),
					'name'     => sanitize_title( $input['field_name'][ $key ] ),
					'required' => $input['field_required'][ $input['field_name'][ $key ] ] ?? 'no',
				);
			}
		}

		if ( isset( $input['modal_form_email_recipient'] ) && is_email( $input['modal_form_email_recipient'] ) ) {
			$new_input['modal_form_email_recipient'] = sanitize_email( $input['modal_form_email_recipient'] );
		}

		if ( isset( $input['modal_form_email_field'] ) ) {
			$new_input['modal_form_email_field'] = sanitize_title( $input['modal_form_email_field'] );
		}

		if ( isset( $input['modal_form_name_field'] ) ) {
			$new_input['modal_form_name_field'] = sanitize_title( $input['modal_form_name_field'] );
		}

		if ( isset( $input['modal_form_rgpd_field'] ) ) {
			$new_input['modal_form_rgpd_field'] = sanitize_title( $input['modal_form_rgpd_field'] );
		}

		// Validation for pages
		if ( isset( $input['modal_form_pages'] ) ) {
			$pages                     = array_map( 'absint', $input['modal_form_pages'] );
			$input['modal_form_pages'] = array_filter( $pages, 'get_post' );
		}

		// Validation for posts
		if ( isset( $input['modal_form_posts'] ) ) {
			$posts                     = array_map( 'absint', $input['modal_form_posts'] );
			$input['modal_form_posts'] = array_filter( $posts, 'get_post' );
		}
	}

	return $new_input;
}

function add_action_links( $links ): array {
	$mylinks = array(
		'<a href="' . admin_url( 'options-general.php?page=modal-form-options' ) . '">' . __( "Settings", "modal-form-brochure" ) . '</a>',
	);

	return array_merge( $links, $mylinks );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'add_action_links' );

add_action( 'plugins_loaded', 'load_modal_form_textdomain' );
function load_modal_form_textdomain(): void {
	load_plugin_textdomain( 'modal-form-brochure', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
