<?php
/*
Plugin Name: Request Quote Popup
Plugin URI: https://yourwebsite.com
Description: Adds a "Request Quote" button with a popup form and stores submissions in the WordPress database.
Version: 1.1
Author: Shagun Mishraustom_modal_scr
Author URI: https://yourwebsite.com
License: GPL2
*/

// --- Activation Hook to Create Database Table ---
register_activation_hook(__FILE__, 'create_quote_requests_table');

function create_quote_requests_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'quote_requests';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        full_name VARCHAR(255) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_code VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        quantity INT(11) NOT NULL,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// --- Add "Request Quote" Button on Product Pages ---
function add_request_quote_button()
{
    echo '<button id="request-quote-button" class="button alt" style="margin-top: 10px;">Request a Quote</button>';
}
add_action('woocommerce_after_add_to_cart_button', 'add_request_quote_button');

// --- Add Quote Form Modal on Product Pages ---
// --- Add Quote Form Modal on Product Pages ---
function add_quote_popup_to_product_page() {
    ?>
    <div id="quote-form-popup" style="display: none;">
        <div class="quote-form-content">
            <button id="close-quote-popup" style="float: right;">X</button>
            <h2 id="form-title">Contact Information</h2> <!-- Title for the first step -->
            <form id="quotation-form">
                <!-- Step 1: Contact Information -->
                <div id="contact-info-step">
                    <input type="text" id="full-name" name="full_name" placeholder="Full Name" required>
                    <input type="text" id="phone-number" name="phone_number" placeholder="Phone Number" required>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                    <button type="button" id="next-step-1">Next</button>
                </div>
                <!-- Step 2: Request Quote -->
                <div id="quotation-form-step" style="display: none;">
                    <h2 id="form-title-2" style="display: none;">Request Quote</h2> <!-- Title for the second step -->
                    <input type="text" id="product-name" name="product_name" value="<?php echo esc_attr(get_the_title()); ?>" readonly placeholder="Product Name">
                    <input type="text" id="product-code" name="product_code" value="<?php echo esc_attr(get_post_meta(get_the_ID(), '_sku', true)); ?>" placeholder="Product Code">
                    <textarea id="message" name="message" placeholder="Message"></textarea>
                    <input type="number" id="quantity" name="quantity" min="1" value="1" placeholder="Quantity">
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
add_action('woocommerce_after_main_content', 'add_quote_popup_to_product_page');


// --- Handle Form Submission and Store Data in Database ---
// --- Handle Form Submission and Store Data in Database ---
function send_quote_request() {
    global $wpdb;

    if (!empty($_POST['form_data'])) {
        parse_str($_POST['form_data'], $form_data);

        // Server-side validation
        if (empty($form_data['full_name']) || empty($form_data['phone_number']) || empty($form_data['email']) || empty($form_data['product_name']) || empty($form_data['message']) || empty($form_data['quantity'])) {
            wp_send_json_error('All fields are required.');
            wp_die();
        }

        // Validate email format
        if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error('Please provide a valid email address.');
            wp_die();
        }

        // Insert data into the database (no product_code)
        $table_name = $wpdb->prefix . 'quote_requests';
        $wpdb->insert(
            $table_name,
            [
                'full_name' => sanitize_text_field($form_data['full_name']),
                'phone_number' => sanitize_text_field($form_data['phone_number']),
                'email' => sanitize_email($form_data['email']),
                'product_name' => sanitize_text_field($form_data['product_name']),
                'message' => sanitize_textarea_field($form_data['message']),
                'quantity' => intval($form_data['quantity']),
            ]
        );

        // Send confirmation email to the user
  // Send confirmation email to the user
$to = sanitize_email($form_data['email']);
$subject = 'Aniga Jewellers Quote Request Received';

// Custom HTML Email Body
$message = "
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            text-align: center;
            padding: 10px;
            background-color: #541388;
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }
        .email-header h2 {
            margin: 0;
        }
        .email-body {
            padding: 20px;
            font-size: 16px;
            line-height: 1.5;
        }
        .email-body p {
            margin-bottom: 10px;
        }
        .email-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
        .email-footer a {
            color: #541388;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='email-header'>
            <h2>Quote Request Received</h2>
        </div>
        <div class='email-body'>
            <p>Hello <strong>" . sanitize_text_field($form_data['full_name']) . "</strong>,</p>
            <p>Thank you for requesting a quote from <strong>Aniga Jewellers</strong>. We have received the following details:</p>
            <p><strong>Product Name:</strong> " . sanitize_text_field($form_data['product_name']) . "</p>
            <p><strong>Quantity:</strong> " . intval($form_data['quantity']) . "</p>
            <p><strong>Message:</strong><br>" . nl2br(sanitize_textarea_field($form_data['message'])) . "</p>
            <p>Our team will get back to you soon with the requested quote.</p>
        </div>
        <div class='email-footer'>
            <p>If you have any questions, feel free to <a href='mailto:info@anigajewellers.com'>contact us</a>.</p>
            <p>Best regards,<br><strong>Aniga Jewellers</strong><br><small>www.anigajewellers.com</small></p>
        </div>
    </div>
</body>
</html>
";

// Set the email headers for HTML email format
$headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: Aniga Jewellers <info@anigajewellers.com>',
];

// Send the email
$email_sent = wp_mail($to, $subject, $message, $headers);


        if (!$email_sent) {
            wp_send_json_error('Failed to send confirmation email.');
        }

        wp_send_json_success('Your quote request has been submitted successfully!');
    } else {
        wp_send_json_error('Invalid form submission.');
    }

    wp_die();
}

add_action('wp_ajax_send_quote_request', 'send_quote_request');
add_action('wp_ajax_nopriv_send_quote_request', 'send_quote_request');



// --- Add Admin Menu for Quote Requests ---
function add_quote_requests_menu()
{
    add_menu_page(
        'Quote Requests',
        'Quote Requests',
        'manage_options',
        'quote-requests',
        'display_quote_requests',
        'dashicons-list-view',
        25
    );
}
add_action('admin_menu', 'add_quote_requests_menu');

// --- Handle Delete Request ---
function handle_quote_delete()
{
    if (isset($_GET['delete_quote']) && is_numeric($_GET['delete_quote'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'quote_requests';
        $quote_id = intval($_GET['delete_quote']);
        $wpdb->delete($table_name, ['id' => $quote_id]);
        wp_redirect(admin_url('admin.php?page=quote-requests'));
        exit;
    }
}
add_action('admin_init', 'handle_quote_delete');

// --- Display Stored Submissions in Admin Panel ---
function display_quote_requests()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'quote_requests';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submission_date DESC");

    echo '<div class="wrap"><h1>Quote Requests</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Phone Number</th>
            <th>Email</th>
            <th>Product Name</th>
            <th>Product Code</th>
            <th>Message</th>
            <th>Quantity</th>
            <th>Date</th>
            <th>Actions</th>
        </tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        $delete_url = esc_url(add_query_arg(['delete_quote' => $row->id], admin_url('admin.php?page=quote-requests')));
        $edit_url = esc_url(add_query_arg(['edit_quote' => $row->id], admin_url('admin.php?page=quote-requests')));
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->full_name) . '</td>';
        echo '<td>' . esc_html($row->phone_number) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->product_name) . '</td>';
        echo '<td>' . esc_html($row->product_code) . '</td>';
        echo '<td>' . esc_html($row->message) . '</td>';
        echo '<td>' . esc_html($row->quantity) . '</td>';
        echo '<td>' . esc_html($row->submission_date) . '</td>';
        echo '<td>
                <a href="' . $delete_url . '" class="button button-secondary" onclick="return confirm(\'Are you sure you want to delete this quote?\')">Delete</a>
              </td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// --- Enqueue Scripts and Styles for Modal ---
// --- Enqueue Scripts and Styles for Modal ---
function enqueue_custom_modal_scripts()
{
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function ($) {
            // Open modal
            $("#request-quote-button").on("click", function (e) {
                e.preventDefault();
                $("#quote-form-popup").fadeIn();
            });

            // Close modal
            $("#close-quote-popup").on("click", function (e) {
                e.preventDefault();
                $("#quote-form-popup").fadeOut();
            });

            // Navigate to the next step
            $("#next-step-1").on("click", function (e) {
                e.preventDefault();

                let isValid = true;
                $("#contact-info-step input").each(function () {
                    if ($(this).val() === "") {
                        alert("Please fill out all fields.");
                        isValid = false;
                        return false;
                    }
                });

                if ($("#email").val() && !isValidEmail($("#email").val())) {
                    alert("Please enter a valid email address.");
                    isValid = false;
                }

                if (isValid) {
                    $("#contact-info-step").hide();
                    $("#quotation-form-step").fadeIn();
                }
            });

            // Email validation function
            function isValidEmail(email) {
                var re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$/;
                return re.test(email);
            }

            // Submit form via AJAX
            $("#quotation-form").on("submit", function (e) {
                e.preventDefault();

                let isValid = true;
                $("#quotation-form-step input, #quotation-form-step textarea").each(function () {
                    if ($(this).val() === "") {
                        alert("Please fill out all fields.");
                        isValid = false;
                        return false;
                    }
                });

                if (isValid) {
                    let $submitButton = $(this).find("button[type=\'submit\']");
                    $submitButton.prop("disabled", true).text("Submitting...");

                    $.post(quoteAjax.ajaxurl, {
    action: "send_quote_request",
    form_data: $(this).serialize()
}, function (response) {
    alert(response.data);
    
    // Clear form fields after successful submission
    $("#quotation-form")[0].reset();
    // Reset the modal steps to show the first step again
    $("#quotation-form-step").hide();
    $("#contact-info-step").show();
    
    // Close the modal
    $("#quote-form-popup").fadeOut();

    $submitButton.prop("disabled", false).text("Submit");
}).fail(function () {
    alert("Something went wrong. Please try again.");
    $submitButton.prop("disabled", false).text("Submit");
});
                }
            });
        });
    ');
    wp_enqueue_style('quote-popup-style', plugin_dir_url(__FILE__) . 'css/quote-popup.css');
    wp_localize_script('jquery', 'quoteAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
}
add_action('wp_enqueue_scripts', 'enqueue_custom_modal_scripts');

