<?php
/**
 * Content Indexer for site content training
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_Content_Indexer {

    /**
     * Training settings
     */
    private $training_settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->training_settings = get_option('srs_ai_chatbot_training_settings', array());
        
        // Hook into post save to reindex content
        add_action('save_post', array($this, 'reindex_post'), 10, 2);
        add_action('delete_post', array($this, 'remove_post_from_index'));
    }

    /**
     * Index all site content
     */
    public function index_all_content() {
        $post_types = $this->training_settings['post_types'] ?? array('post', 'page');
        $indexed_count = 0;

        foreach ($post_types as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => -1
            ));

            foreach ($posts as $post) {
                if ($this->index_post($post)) {
                    $indexed_count++;
                }
            }
        }

        // Index WooCommerce products if enabled
        if (class_exists('WooCommerce') && in_array('product', $post_types)) {
            $products = wc_get_products(array('limit' => -1));
            foreach ($products as $product) {
                if ($this->index_product($product)) {
                    $indexed_count++;
                }
            }
        }

        return $indexed_count;
    }

    /**
     * Index a single post
     */
    public function index_post($post) {
        if (is_numeric($post)) {
            $post = get_post($post);
        }

        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        // Prepare content
        $content = $this->prepare_post_content($post);
        $content_hash = hash('sha256', $content);

        // Check if content has changed
        global $wpdb;
        $table = $wpdb->prefix . 'srs_content_index';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE content_id = %d AND content_type = 'post'",
            $post->ID
        ));

        if ($existing && $existing->content_hash === $content_hash) {
            // Content unchanged, just update timestamp
            $wpdb->update(
                $table,
                array('last_indexed' => current_time('mysql')),
                array('id' => $existing->id),
                array('%s'),
                array('%d')
            );
            return true;
        }

        // Insert or update content
        $data = array(
            'content_id' => $post->ID,
            'content_type' => 'post',
            'title' => $post->post_title,
            'content' => $content,
            'content_hash' => $content_hash,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
            'author_id' => $post->post_author,
            'excerpt' => $post->post_excerpt,
            'metadata' => json_encode(array(
                'url' => get_permalink($post->ID),
                'modified' => $post->post_modified
            ))
        );

        if ($existing) {
            return $wpdb->update($table, $data, array('id' => $existing->id));
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Index WooCommerce product
     */
    public function index_product($product) {
        if (is_numeric($product)) {
            $product = wc_get_product($product);
        }

        if (!$product || $product->get_status() !== 'publish') {
            return false;
        }

        // Prepare product content
        $content = $this->prepare_product_content($product);
        $content_hash = hash('sha256', $content);

        global $wpdb;
        $table = $wpdb->prefix . 'srs_content_index';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE content_id = %d AND content_type = 'product'",
            $product->get_id()
        ));

        if ($existing && $existing->content_hash === $content_hash) {
            $wpdb->update(
                $table,
                array('last_indexed' => current_time('mysql')),
                array('id' => $existing->id),
                array('%s'),
                array('%d')
            );
            return true;
        }
        $data = array(
            'content_id' => $product->get_id(),
            'content_type' => 'product',
            'title' => $product->get_name(),
            'content' => $content,
            'content_hash' => $content_hash,
            'post_type' => 'product',
            'post_status' => $product->get_status(),
            'metadata' => json_encode(array(
                'url' => $product->get_permalink(),
                'price' => $product->get_price(),
                'sku' => $product->get_sku(),
                'stock_status' => $product->get_stock_status(),
                'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'))
            ))
        );

        if ($existing) {
            return $wpdb->update($table, $data, array('id' => $existing->id));
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    /**
     * Prepare post content for indexing
     */
    private function prepare_post_content($post) {
        $content = $post->post_title . "\n\n";
        
        if (!empty($post->post_excerpt)) {
            $content .= $post->post_excerpt . "\n\n";
        }
        
        // Strip shortcodes and HTML tags
        $post_content = strip_shortcodes($post->post_content);
        $post_content = wp_strip_all_tags($post_content);
        $content .= $post_content;

        // Add custom fields if configured
        if ($this->training_settings['include_custom_fields'] ?? false) {
            $custom_fields = get_post_meta($post->ID);
            foreach ($custom_fields as $key => $values) {
                if (!str_starts_with($key, '_')) { // Skip private fields
                    $content .= "\n" . $key . ": " . implode(', ', $values);
                }
            }
        }

        return trim($content);
    }
    /**
     * Prepare product content for indexing
     */
    private function prepare_product_content($product) {
        $content = $product->get_name() . "\n\n";
        
        if ($product->get_short_description()) {
            $content .= $product->get_short_description() . "\n\n";
        }
        
        if ($product->get_description()) {
            $description = wp_strip_all_tags($product->get_description());
            $content .= $description . "\n\n";
        }

        // Add product details
        $content .= "Price: " . $product->get_price_html() . "\n";
        
        if ($product->get_sku()) {
            $content .= "SKU: " . $product->get_sku() . "\n";
        }
        
        $content .= "Stock Status: " . $product->get_stock_status() . "\n";

        // Add categories
        $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
        if (!empty($categories)) {
            $content .= "Categories: " . implode(', ', $categories) . "\n";
        }

        // Add tags
        $tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));
        if (!empty($tags)) {
            $content .= "Tags: " . implode(', ', $tags) . "\n";
        }

        return trim($content);
    }

    /**
     * Search indexed content
     */
    public function search_content($query, $limit = 5) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_content_index';
        $search_terms = explode(' ', strtolower($query));
        $results = array();

        // Simple keyword search (can be enhanced with full-text search or vector search)
        foreach ($search_terms as $term) {
            if (strlen($term) > 2) {
                $found = $wpdb->get_results($wpdb->prepare(
                    "SELECT title, content, metadata FROM $table 
                     WHERE LOWER(title) LIKE %s OR LOWER(content) LIKE %s 
                     ORDER BY 
                        CASE WHEN LOWER(title) LIKE %s THEN 1 ELSE 2 END,
                        CHAR_LENGTH(content) ASC
                     LIMIT %d",
                    '%' . $wpdb->esc_like($term) . '%',
                    '%' . $wpdb->esc_like($term) . '%',
                    '%' . $wpdb->esc_like($term) . '%',
                    $limit
                ));

                foreach ($found as $result) {
                    $results[] = $result->title . ": " . substr($result->content, 0, 500);
                }
            }
        }

        return array_unique(array_slice($results, 0, $limit));
    }
    /**
     * Reindex post when it's updated
     */
    public function reindex_post($post_id, $post) {
        // Skip auto-saves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        $post_types = $this->training_settings['post_types'] ?? array('post', 'page');
        
        if (in_array($post->post_type, $post_types)) {
            $this->index_post($post);
        }
    }

    /**
     * Remove post from index when deleted
     */
    public function remove_post_from_index($post_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_content_index';
        $wpdb->delete(
            $table,
            array(
                'content_id' => $post_id,
                'content_type' => 'post'
            ),
            array('%d', '%s')
        );
    }

    /**
     * Clear all indexed content
     */
    public function clear_index() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_content_index';
        return $wpdb->query("TRUNCATE TABLE $table");
    }

    /**
     * Get indexing statistics
     */
    public function get_index_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_content_index';
        
        $stats = array(
            'total_indexed' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'posts' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE content_type = 'post'"),
            'products' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE content_type = 'product'"),
            'last_indexed' => $wpdb->get_var("SELECT MAX(last_indexed) FROM $table")
        );

        return $stats;
    }
}
