<?php
/*
Plugin Name: Modal Form Brochure
Description: A simple plugin that displays a modal with a form when the user clicks on a link with the id #brochure.
Version: 1.0
Author: Influactive
Author URI: https://influactive.com
*/

function load_modal_form_scripts(): void
{
    wp_enqueue_script('modal-form-script', plugin_dir_url(__FILE__) . 'modal-form-script.js', array(), '1.0', true);
    wp_enqueue_style('modal-form-style', plugin_dir_url(__FILE__) . 'modal-form-style.css');

}

add_action('wp_enqueue_scripts', 'load_modal_form_scripts');

function load_admin_scripts($hook): void
{
    if ('settings_page_modal-form-options' !== $hook) {
        return;
    }
    wp_enqueue_script('modal-form-admin', plugin_dir_url(__FILE__) . 'admin.js', array(), '1.0', true);
    wp_enqueue_style('modal-form-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
}

add_action('admin_enqueue_scripts', 'load_admin_scripts');

function add_modal_form(): void
{
    $fields = get_option('modal_form_fields', array());
    $title = get_option('modal_form_title', 'Do you want to download this product sheet?');
    $description = get_option('modal_form_description', 'In order to receive your product sheet, please fill in your information below, we will send you a link by email to download it.');
    $submit_text = get_option('modal_form_submit_text', 'Submit');

    ob_start(); ?>
    <div id="modal-form" class="modal-form">
        <div class="modal-content">
            <span id="modal-form-close" class="close">&times;</span>
            <h2><?= $title ?></h2>
            <hr>
            <p class="description"><?= $description ?></p>
            <form action="<?= plugin_dir_url(__FILE__) . 'process-form.php' ?>" method="post">
                <?php foreach ($fields as $field) : ?>
                    <label for="<?= $field['name'] ?>"><?= $field['label'] ?></label>
                    <?php if ($field['type'] === 'textarea') : ?>
                        <textarea id="<?= $field['name'] ?>" name="<?= $field['name'] ?>" rows="6" <?= $field['required'] ? 'required' : '' ?>></textarea>
                    <?php else : ?>
                        <input type="<?= $field['type'] ?>" id="<?= $field['name'] ?>" name="<?= $field['name'] ?>" <?= $field['required'] ? 'required' : '' ?>>
                    <?php endif; ?>
                <?php endforeach ?>
                <?php if (count($fields) > 0) : ?>
                    <input type="submit" value="<?= $submit_text ?>">
                <?php endif; ?>
            </form>
            <div class="message"></div>
        </div>
    </div>
    <?= ob_get_clean();
}

add_action('wp_footer', 'add_modal_form');

function modal_form_options_page(): void
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Start output buffering
    ob_start();
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('modal_form_options');
            do_settings_sections('modal-form-options');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
    // Output the content of the buffer
    echo ob_get_clean();
}

function modal_form_settings_init(): void
{
    register_setting('modal_form_options', 'modal_form_fields', 'modal_form_options_validate');
    register_setting('modal_form_options', 'modal_form_title', 'sanitize_text_field');
    register_setting('modal_form_options', 'modal_form_description', 'sanitize_text_field');
    register_setting('modal_form_options', 'modal_form_submit_text', 'sanitize_text_field');
    register_setting('modal_form_options', 'modal_form_email_recipient');
    register_setting('modal_form_options', 'modal_form_email_field');
    register_setting('modal_form_options', 'modal_form_name_field');
    add_settings_section('modal_form_main', 'Main Settings', 'modal_form_fields_callback', 'modal-form-options');
}

add_action('admin_init', 'modal_form_settings_init');

function modal_form_fields_callback(): void
{
    $form_fields = get_option('modal_form_fields');
    $form_title = get_option('modal_form_title');
    $form_description = get_option('modal_form_description');
    $form_submit_text = get_option('modal_form_submit_text');
    $email_recipient = get_option('modal_form_email_recipient', get_bloginfo('admin_email'));
    $email_field = get_option('modal_form_email_field', get_bloginfo('admin_email'));
    $name_field = get_option('modal_form_name_field', 'Name');
    ?>
    <div id="content-edit">
        <label for="modal_form_title">Form Title:</label>
        <input id="modal_form_title" type="text" name="modal_form_title" value="<?= esc_attr($form_title ?? 'Do you want to download this product sheet?') ?>">
        <label for="modal_form_description">Form Description:</label>
        <input id="modal_form_description" type="text" name="modal_form_description" value="<?= esc_attr($form_description ?? 'In order to receive your product sheet, please fill in your information below, we will send you a link by email to download it.') ?>">
        <label for="modal_form_submit_text">Submit Button Text:</label>
        <input id="modal_form_submit_text" type="text" name="modal_form_submit_text" value="<?= esc_attr($form_submit_text ?? 'Submit') ?>">
    </div>
    <div id="recipient-fields">
        <label for="email_field">Email Field:</label>
        <select id="email_field" name="modal_form_email_field">
            <?php foreach ($form_fields as $field) : ?>
                <option value="<?= $field['name'] ?>" <?= $field['name'] === $email_field ? 'selected' : '' ?>><?= $field['label'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="name_field">Name Field:</label>
        <select id="name_field" name="modal_form_name_field">
            <?php foreach ($form_fields as $field) : ?>
                <option value="<?= $field['name'] ?>" <?= $field['name'] === $name_field ? 'selected' : '' ?>><?= $field['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="email-recipient">
        <label for="email_recipient">Email Recipient:</label>
        <input id="email_recipient" type="text" name="modal_form_email_recipient" value="<?= esc_attr($email_recipient) ?>">
    </div>
    <div id="form-fields">
        <?php if (is_array($form_fields)) { ?>
            <?php foreach ($form_fields as $field) : ?>
                <div class="field">
                    <label for="type">Type:</label>
                    <select id="type" name="modal_form_fields[field_type][]">
                        <option value="text" <?= $field['type'] === 'text' ? 'selected' : '' ?>>Text</option>
                        <option value="email" <?= $field['type'] === 'email' ? 'selected' : '' ?>>Email</option>
                        <option value="number" <?= $field['type'] === 'number' ? 'selected' : '' ?>>Number</option>
                        <option value="date" <?= $field['type'] === 'date' ? 'selected' : '' ?>>Date</option>
                        <option value="checkbox" <?= $field['type'] === 'checkbox' ? 'selected' : '' ?>>Checkbox
                        </option>
                        <option value="radio" <?= $field['type'] === 'radio' ? 'selected' : '' ?>>Radio</option>
                        <option value="textarea" <?= $field['type'] === 'textarea' ? 'selected' : '' ?>>Textarea
                        </option>
                    </select>
                    <label for="label">Label:</label>
                    <input id="label" type="text" name="modal_form_fields[field_label][]"
                           value="<?= esc_attr($field['label'] ?? '') ?>">
                    <label for="name">Name:</label>
                    <input id="name" type="text" name="modal_form_fields[field_name][]"
                           value="<?= esc_attr($field['name'] ?? '') ?>">
                    <label for="required">Required:</label>
                    <input id="required" type="checkbox" name="modal_form_fields[field_required][]"
                        <?= isset($field['required']) && $field['required'] ? 'checked' : '' ?>>
                    <?php submit_button('Delete', 'delete-field', 'delete-field', false, array('data-id' => $field['name'])); ?>
                </div>
            <?php endforeach; ?>
        <?php } ?>
    </div>
    <?php submit_button('Add Field', 'add-field', 'add-field', false); ?>
    <?php
}

add_action('admin_menu', static function () {
    add_options_page('Modal Form Brochure Options', 'Modal Form Options', 'manage_options', 'modal-form-options', 'modal_form_options_page');
});

function modal_form_options_validate($input): array
{
    // Initialize the new array that will hold the sanitize values
    $new_input = array();

    if (is_array($input)) {
        for ($i = 0, $iMax = count($input['field_type']); $i < $iMax; $i++) {
            // Check if the input is a string, if it is, sanitize it
            if (is_string($input['field_type'][$i]) && is_string($input['field_label'][$i]) && is_string($input['field_name'][$i])) {
                $new_input[] = array(
                    'type' => sanitize_text_field($input['field_type'][$i]),
                    'label' => sanitize_text_field($input['field_label'][$i]),
                    'name' => sanitize_title($input['field_name'][$i]),
                    'required' => isset($input['field_required'][$i]) && $input['field_required'][$i] === 'on',
                );
            }
        }
        if (isset($input['modal_form_email_recipient']) && is_email($input['modal_form_email_recipient'])) {
            $new_input['modal_form_email_recipient'] = sanitize_email($input['modal_form_email_recipient']);
        }

        if (isset($input['modal_form_email_field'])) {
            $new_input['modal_form_email_field'] = sanitize_title($input['modal_form_email_field']);
        }

        if (isset($input['modal_form_name_field'])) {
            $new_input['modal_form_name_field'] = sanitize_title($input['modal_form_name_field']);
        }
    }

    return $new_input;
}

function add_action_links($links): array
{
    $mylinks = array(
        '<a href="' . admin_url('options-general.php?page=modal-form-options') . '">Settings</a>',
    );
    return array_merge($links, $mylinks);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links');
