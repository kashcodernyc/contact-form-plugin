<?php

/**
 * Plugin Name: Contact From Plugin Prakash
 * Description: Contact form with cool functionality
 * Author: Prakash Subba
 * Version: 1.0.1
 * Text Domain: contact-form-plugin-prakash
 */

if (!defined('ABSPATH')) {
    exit;
}

class BasicContactForm
{

    public function __construct()
    {
        //create custom post type
        add_action('init', array($this, 'create_custom_post_type'));

        // Add assets (js, css, etc)
        add_action('wp_enqueue_scripts', array($this, 'load_assets'));

        // add shortcode
        add_shortcode('contact-form', array($this, 'load_shortcode'));

        // load javascripts
        add_action('wp_footer', array($this, 'load_scripts'));

        //register REST API
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    public function create_custom_post_type()
    {
        $args = array(
            'public' => true,
            'has_archive' => true,
            'supports' => array('title'),
            'exclude_form_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
            'labels' => array(
                'name' => 'Contact Form',
                'singular_name' => 'Contact Form Entry'
            ),
            'menu_icon' => 'dashicons-media-text',
        );

        register_post_type('basic_contact_form', $args);
    }

    public function load_assets()
    {
        wp_enqueue_style(
            'basic-contact-form',
            plugin_dir_url(__FILE__) . 'css/basic-contact-form.css',
            array(),
            1,
            'all'
        );

        wp_enqueue_script(
            'basic-contact-form',
            plugin_dir_url(__FILE__) . 'js/basic-contact-form.js',
            array('jquery'),
            1,
            true
        );
        wp_enqueue_script(
            'jquery',
            'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
            array(),
            '3.6.0',
            true
        );
    }

    public function load_shortcode()
    { ?>
        <div class="simple-contact-form">
            <h1>Send Us Email</h1>
            <p>Please fill out the form below</p>
            <form id="simple-contact-form__form">
                <div class="form-group mb-2">
                    <input name="name" type="text" placeholder="name" class="form-control" />
                </div>
                <div class="form-group mb-2">
                    <input name="email" type="email" placeholder="email" class="form-control" />
                </div>
                <div class="form-group mb-2">
                    <input name="phone" type="tel" placeholder="phone" class="form-control" />
                </div>
                <div class="form-group mb-2">
                    <textarea name="message" placeholder="type your message" class="form-control"></textarea>
                </div>
                <div class="form-group mb-2">
                    <button type="submmit" class="btn btn-success btn-block w-100">submit</button>
                </div>
            </form>
        </div>
    <?php
    }

    public function load_scripts()
    { ?>
        <script>
            var nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
            jQuery(function($) {
                $('#simple-contact-form__form').submit(function(event) {
                    event.preventDefault();
                    var form = $(this).serialize();
                    console.log(form);

                    $.ajax({
                        method: 'post',
                        url: '<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email') ?>',
                        headers: {
                            'X-WP-Nonce': nonce
                        },
                        data: form
                    })
                })
            });
        </script>
<?php
    }

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
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response('Message not sent', 422);
        }

        $post_id = wp_insert_post([
            'post_type' => 'basic_contact_form',
            'post_title' => 'Contact enquiry',
            'post_status' => 'bublish'
        ]);

        if ($post_id) {
            return new WP_REST_Response('Thank you for your email', 200);
        }
    }
}

new BasicContactForm;
