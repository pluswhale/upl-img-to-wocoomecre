<?php
/*
Plugin Name: My Custom Uploads
Description: Allows users to upload images as WooCommerce products with randomized names and descriptions.
Version: 1.0
Author: Egor Dultsev
*/

function custom_upload_form_shortcode() {
    $progress_message = '';
    if (isset($_SESSION['upload_progress']) && isset($_SESSION['upload_total'])) {
        $progress_message = "<p>Uploaded {$_SESSION['upload_progress']} out of {$_SESSION['upload_total']} images.</p>";
    }

        // Add category selector to the form
    $categories_html = '<select name="product_category_id">';
    $categories_html .= '<option value="">Choose a Category</option>';
    $categories = [
        'contemporary' => 'Contemporary',
        'abstract' => 'Abstract',
        'ukiyo-e' => 'Ukiyo-e',
        'art-deco' => 'Art Deco',
    ];
    foreach ($categories as $slug => $name) {
        $categories_html .= sprintf('<option value="%s">%s</option>', $slug, $name);
    }
    $categories_html .= '</select>';

    $form_html = $progress_message . '<form action="" method="post" enctype="multipart/form-data" id="uploadForm">' .
                    $categories_html .
                    // '<input type="text" name="category_name" placeholder="Category (Theme)" required>
                    '<p>I may add up to 1000 files</p>
                    <input type="file" name="images[]" multiple="multiple">
                    <input type="submit" name="submit_images" value="Upload">
                  </form>';
    
    return $form_html;
}


add_shortcode('custom_upload_form', 'custom_upload_form_shortcode');

function handle_image_upload() {
    if (isset($_POST['submit_images']) && !empty($_FILES['images']['name'][0])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $category_name = sanitize_text_field($_POST['category_name']);

        if (class_exists('WooCommerce')) {
            // if (!term_exists($category_name, 'product_cat')) {
            //     $term = wp_insert_term($category_name, 'product_cat');
            //     $category_id = isset($term['term_id']) ? $term['term_id'] : null;
            // } else {
            //     $term = get_term_by('name', $category_name, 'product_cat');
            //     $category_id = $term ? $term->term_id : null;
            // }

            // Initialize session variables if not already set
            if (!isset($_SESSION['upload_progress'])) {
                $_SESSION['upload_progress'] = 0;
                $_SESSION['upload_total'] = 0;
            }

            $uploaded_files = $_FILES['images'];
            $_SESSION['upload_total'] += count($uploaded_files['name']);

            $user_id = get_current_user_id();
            $category_slug = sanitize_text_field($_POST['product_category_id']);

            foreach ($uploaded_files['name'] as $key => $value) {
                if ($uploaded_files['name'][$key]) {
                    $_FILES = array('upload' => array(
                        'name'     => $uploaded_files['name'][$key],
                        'type'     => $uploaded_files['type'][$key],
                        'tmp_name' => $uploaded_files['tmp_name'][$key],
                        'error'    => $uploaded_files['error'][$key],
                        'size'     => $uploaded_files['size'][$key]
                    ));
                    $upload_overrides = array('test_form' => false);
                    $attachment_id = media_handle_upload('upload', 0, $upload_overrides);

                    if (!is_wp_error($attachment_id)) {
                        $product_name = generate_random_name();
                        $product_description = generate_random_description();

                        $product = new WC_Product_Simple();
                        $product->set_name($product_name);
                        $product->set_description($product_description);
                        $product->set_regular_price(10);
                        // $product->set_category_ids(array($category_id));
                        $product->set_image_id($attachment_id);

                         // Generate a unique SKU based on product name and a unique part
                        $unique_part = uniqid(); // Generates a unique ID
                        $sku = sanitize_title($product_name) . '-' . $unique_part;
                        $product->set_sku($sku);

                        $product->update_meta_data('uploader_user_id', $user_id);
                        $product->save();

                        $term = get_term_by('slug', $category_slug, 'product_cat');
                        if ($term) {
                            $product->set_category_ids([$term->term_id]);
                        }

                        // Save the product to apply the category change
                        $product->save();

                        
                        $_SESSION['upload_progress']++;
                    }
                }
            }

            // Provide feedback to user
            echo "<p>Uploaded {$_SESSION['upload_progress']} out of {$_SESSION['upload_total']} images.</p>";
        }
    }
}

add_action('wp_loaded', 'handle_image_upload');

// Implement these functions based on your requirements for random names and descriptions
function generate_random_name() {
    $nouns = ['Sunset', 'River', 'Mountain'];
    $adjectives = ['Beautiful', 'Serene', 'Majestic'];
    return $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)];
}

function generate_random_description() {
    $words = ['The', 'quick', 'brown', 'fox'];
    shuffle($words);
    return implode(' ', $words) . '.';
}