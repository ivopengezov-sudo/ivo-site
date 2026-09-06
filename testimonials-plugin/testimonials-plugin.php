<?php
/**
 * Plugin Name: Ivo - Testimonials & Reviews
 * Description: Custom post type for client testimonials with a rating field, a [testimonials] shortcode to display them, and a REST API endpoint for headless/JS use.
 * Version: 1.0
 * Author: Ivo Pengezov
 */

if (!defined('ABSPATH')) exit;

/**
 * 1) Register the "Testimonial" custom post type.
 */
add_action('init', 'ivo_register_testimonial_cpt');

function ivo_register_testimonial_cpt() {
    register_post_type('testimonial', [
        'labels' => [
            'name'          => 'Testimonials',
            'singular_name' => 'Testimonial',
            'add_new_item'  => 'Add New Testimonial',
            'edit_item'     => 'Edit Testimonial',
        ],
        'public'       => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-format-quote',
        'supports'     => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ]);
}

/**
 * 2) Meta box for author name, company/role, and a 1-5 star rating.
 */
add_action('add_meta_boxes', 'ivo_testimonial_meta_box');

function ivo_testimonial_meta_box() {
    add_meta_box(
        'ivo_testimonial_details',
        'Testimonial Details',
        'ivo_testimonial_meta_box_html',
        'testimonial',
        'side'
    );
}

function ivo_testimonial_meta_box_html($post) {
    wp_nonce_field('ivo_save_testimonial', 'ivo_testimonial_nonce');

    $author  = get_post_meta($post->ID, '_ivo_author_name', true);
    $company = get_post_meta($post->ID, '_ivo_author_company', true);
    $rating  = get_post_meta($post->ID, '_ivo_rating', true) ?: 5;
    ?>
    <p>
        <label for="ivo_author_name">Author name</label>
        <input type="text" id="ivo_author_name" name="ivo_author_name" value="<?php echo esc_attr($author); ?>" style="width:100%;">
    </p>
    <p>
        <label for="ivo_author_company">Company / role</label>
        <input type="text" id="ivo_author_company" name="ivo_author_company" value="<?php echo esc_attr($company); ?>" style="width:100%;">
    </p>
    <p>
        <label for="ivo_rating">Rating (1-5)</label>
        <input type="number" id="ivo_rating" name="ivo_rating" min="1" max="5" value="<?php echo esc_attr($rating); ?>" style="width:100%;">
    </p>
    <?php
}

add_action('save_post_testimonial', 'ivo_save_testimonial_meta');

function ivo_save_testimonial_meta($post_id) {
    if (!isset($_POST['ivo_testimonial_nonce']) || !wp_verify_nonce($_POST['ivo_testimonial_nonce'], 'ivo_save_testimonial')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['ivo_author_name'])) {
        update_post_meta($post_id, '_ivo_author_name', sanitize_text_field($_POST['ivo_author_name']));
    }
    if (isset($_POST['ivo_author_company'])) {
        update_post_meta($post_id, '_ivo_author_company', sanitize_text_field($_POST['ivo_author_company']));
    }
    if (isset($_POST['ivo_rating'])) {
        $rating = max(1, min(5, (int) $_POST['ivo_rating']));
        update_post_meta($post_id, '_ivo_rating', $rating);
    }
}

/**
 * 3) [testimonials] shortcode - renders a responsive grid of testimonials.
 *    Usage: [testimonials count="6" columns="3"]
 */
add_shortcode('testimonials', 'ivo_testimonials_shortcode');

function ivo_testimonials_shortcode($atts) {
    $atts = shortcode_atts([
        'count'   => 6,
        'columns' => 3,
    ], $atts, 'testimonials');

    $query = new WP_Query([
        'post_type'      => 'testimonial',
        'posts_per_page' => (int) $atts['count'],
    ]);

    if (!$query->have_posts()) {
        return '<p>No testimonials yet.</p>';
    }

    $columns = max(1, (int) $atts['columns']);
    $output  = '<div class="ivo-testimonials-grid" style="display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:20px;">';

    while ($query->have_posts()) {
        $query->the_post();

        $author  = get_post_meta(get_the_ID(), '_ivo_author_name', true);
        $company = get_post_meta(get_the_ID(), '_ivo_author_company', true);
        $rating  = (int) get_post_meta(get_the_ID(), '_ivo_rating', true) ?: 5;
        $stars   = str_repeat('â', $rating) . str_repeat('â', 5 - $rating);

        $output .= '<div class="ivo-testimonial-card" style="border:1px solid #e0e0e0;border-radius:8px;padding:20px;">';
        $output .= '<div class="ivo-testimonial-stars" style="color:#f5a623;">' . $stars . '</div>';
        $output .= '<div class="ivo-testimonial-text">' . wpautop(get_the_content()) . '</div>';
        $output .= '<p class="ivo-testimonial-author"><strong>' . esc_html($author) . '</strong>';
        if ($company) {
            $output .= ' &mdash; ' . esc_html($company);
        }
        $output .= '</p></div>';
    }

    wp_reset_postdata();
    $output .= '</div>';

    return $output;
}

/**
 * 4) REST API endpoint for headless/JS use: GET /wp-json/ivo/v1/testimonials
 */
add_action('rest_api_init', 'ivo_register_testimonials_rest_route');

function ivo_register_testimonials_rest_route() {
    register_rest_route('ivo/v1', '/testimonials', [
        'methods'             => 'GET',
        'callback'            => 'ivo_get_testimonials_rest',
        'permission_callback' => '__return_true',
    ]);
}

function ivo_get_testimonials_rest($request) {
    $query = new WP_Query([
        'post_type'      => 'testimonial',
        'posts_per_page' => -1,
    ]);

    $data = [];

    while ($query->have_posts()) {
        $query->the_post();

        $data[] = [
            'id'      => get_the_ID(),
            'text'    => get_the_content(),
            'author'  => get_post_meta(get_the_ID(), '_ivo_author_name', true),
            'company' => get_post_meta(get_the_ID(), '_ivo_author_company', true),
            'rating'  => (int) get_post_meta(get_the_ID(), '_ivo_rating', true) ?: 5,
        ];
    }

    wp_reset_postdata();

    return rest_ensure_response($data);
}
