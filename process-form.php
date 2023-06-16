<?php

$possible_paths = [
	'/wp-load.php', // WordPress standard
	'/wordpress/wp-load.php', // WordPlate
	'/wp/wp-load.php', // Radicle
];

// Try to get the document root from the server
$base_path = $_SERVER['DOCUMENT_ROOT'] ?? '';

foreach ( $possible_paths as $possible_path ) {
	if ( file_exists( $base_path . $possible_path ) ) {
		require_once( $base_path . $possible_path );
		break;
	}
}

function send_form_email( $data ): void {
	$to_admin      = get_option( 'modal_form_email_recipient', get_bloginfo( 'admin_email' ) ); // Replace with your email address
	$email_field   = get_option( 'modal_form_email_field', '' );
	$name_field    = get_option( 'modal_form_name_field', '' );
	$head_email    = get_option( 'modal_form_head_email', 'Hello,' );
	$footer_email  = get_option( 'modal_form_footer_email', 'Goodbye.' );
	$to            = $data[ $email_field ];
	$name          = $data[ $name_field ];
	$subject_admin = __( 'New Brochure Request from', 'modal-form-brochure' ) . " $name";
	$subject       = __( 'Download your brochure', 'modal-form-brochure' );

	$message       = $head_email . '<br><br>';
	$message_admin = '';
	// Loop through all form fields
	foreach ( $data as $key => $value ) {
		// If the field is empty, skip it
		if ( empty( $value ) ) {
			continue;
		}

		if ( $key === 'file' ) {
			continue;
		}

		if ( $key === 'rgpd' ) {
			continue;
		}
		// transformer les _ en espace
		$key           = str_replace( '_', ' ', $key );
		$message_admin .= ucwords( $key ) . ': ' . $value . "<br>\n\r";
	}

	$file_id       = $data['file'];
	$file_url      = wp_get_attachment_url( $file_id );
	$file_name     = basename( get_attached_file( $file_id ) ) ?? '';
	$message       .= '<a href="' . $file_url . '">' . __( "Download your brochure", "modal-form-brochure" ) . ' ' . $file_name . '</a><br><br>';
	$message_admin .= '<br><a href="' . $file_url . '">' . __( "File:", "modal-form-brochure" ) . ' ' . $file_name . ' </a><br><br> ';

	$message .= $footer_email;
	$error   = '';
	if ( ! wp_mail( $to_admin, $subject_admin, $message_admin, [
		'Content-Type: text/html; charset=UTF-8',
		'From:' . $to_admin
	] ) ) {
		$error .= "Email to admin error<br>";
	}
	if ( ! wp_mail( $to, $subject, $message, [ 'Content-Type: text/html; charset=UTF-8', 'From:' . $to_admin ] ) ) {
		$error .= "Email to user error<br>";
	}
	if ( $error === '' ) {
		echo __( "Email sent successfully", "modal-form-brochure" );
	} else {
		echo $error;
	}
}

$form_data = $_POST;
send_form_email( $form_data );

die();
