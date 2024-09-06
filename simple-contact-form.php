<?php
/*
* Plugin Name:       Simple Contact Form
* Description:       A WordPress plugin for contact form.
* Plugin URI:        https://al-amin.xyz/simple-contact-form
* Author:            Al Amin
* Author URI:        https://al-amin.xyz/
* Version:           1.0.0
* Text Domain:       simple-contact-form
*
*/

if (!defined('ABSPATH')) {
    echo 'What are you trying to do here?';
    exit; // Exit if accessed directly.
}

class SimpleContactForm {
    public function __construct()
    {
        // Create custom post type
        add_action('init', array($this, 'create_custom_post_type'));

        // Add assets (js, css, etc)
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));
        
        // Add shortcode
        add_shortcode('contact-form', array($this, 'load_shortcode'));

        // Load javascript
        add_action('wp_footer', array($this, 'load_scripts'));

        // Register REST API
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    public function create_custom_post_type()
    {
        $args = array(
            'public' => true,
            'has_archive' => true,
            'supports' => array("title"),
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
            'labels' => array(
                'name' => 'Contact Form',
                'singular_name' => 'contact Form Entry'
            ),
            'menu_icon' => 'dashicons-media-text'
        );

        register_post_type('simple_contact_form', $args);
    }

    public function load_assets()
    {
        wp_enqueue_style(
            'simple-contact-form',
            plugin_dir_url(__FILE__) . 'css/simple-contact-form.css',
            array(),
            1,
            'all'
        );

        wp_enqueue_script(
            'simple-contact-form',
            plugin_dir_url(__FILE__) . 'js/simple-contact-form.js',
            array('jquery'),
            1,
            true
        );
    }

    public function load_shortcode()
    {?>
        <div class="simple-contact-form">
            <h1>Send us an email</h1>
            <p>Please fill the below form</p>
            <form id="simple-contact-form__form">

                <div class="form-group mb-2">
                    <input name="name" type="text" placeholder="Name" class="form-control">
                </div>

                <div class="form-group mb-2">
                    <input name="email" type="email" placeholder="Email" class="form-control">
                </div>

                <div class="form-group mb-2">
                    <input name="phone" type="tel" placeholder="Phone" class="form-control">
                </div>

                <div class="form-group mb-2">
                    <textarea name="message" placeholder="Type your message" class="form-control"></textarea>
                </div>

                <div class="form-group mb-2">
                    <button class="btn btn-success btn-block w-100">Send Message</button>
                </div>

            </form>
        </div>
    <?php }

    public function load_scripts()
    {?>
        <script>

            var nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
            (function ($){
                $('#simple-contact-form__form').submit( function (event) {
                    event.preventDefault();

                    var form = $(this).serialize();

                    $.ajax({
                        method: 'post',
                        url: '<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email'); ?>',
                        headers: { 'X-WP-Nonce': nonce},
                        data: form,
                        success: function( data ) {
                            alert( data.responseText );
                        },
                        error: function(e) {
                            alert( e.responseText );
                        }
                    })
                })

            })(jQuery)

        </script>
    <?php }

    public function register_rest_api()
    {
        register_rest_route('simple-contact-form/v1', 'send-email', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_contact_form')
        ));
    }

    public function handle_contact_form($data)
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers['x_wp_nonce'][0];

        if(!wp_verify_nonce($nonce, 'wp_rest'))
        {
            return new WP_REST_Response('Message not sent', 422);
        }

        $to = 'ialamin.pro@gmail.com';
        $subject = 'Contact enquiry';
        $message = $params['message'];
        $header = array('Content-Type: text/html; charset=UTF-8', "From: {$params['email']} <{$params['name']}>");

        if( wp_mail( $to, $subject, $message, $header) ) {
            return new WP_REST_Response('Email sent successfully!', 200);
        } else {
            return new WP_REST_Response('Email sending failed!', 400);
        }

        /** $post_id = wp_insert_post([
                'post_type' => 'simple_contact_form',
                'post_title' => 'Contact enquiry',
                'post_status' => 'publish'
        ]);

        if ($post_id)
        {
            return new WP_REST_Response('Thank you for your email', 200);
        }
         **/
    }
}

new SimpleContactForm;