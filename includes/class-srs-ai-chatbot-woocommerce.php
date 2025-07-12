<?php
/**
 * WooCommerce Integration
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_WooCommerce {

    /**
     * WooCommerce settings
     */
    private $wc_settings;

    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        $this->wc_settings = get_option('srs_ai_chatbot_woocommerce_settings', array());
    }

    /**
     * Get order information
     */
    public function get_order_info($order_number, $email = null) {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        // Try to get order by ID first
        $order = wc_get_order($order_number);
        
        // If not found, try by order number
        if (!$order) {
            $orders = wc_get_orders(array(
                'meta_key' => '_order_number',
                'meta_value' => $order_number,
                'limit' => 1
            ));
            
            if (!empty($orders)) {
                $order = $orders[0];
            }
        }

        if (!$order) {
            return __('Order not found.', 'srs-ai-chatbot');
        }

        // Verify email if provided
        if ($email && $this->wc_settings['require_email'] ?? true) {
            if (strtolower($order->get_billing_email()) !== strtolower($email)) {
                return __('Order not found or email does not match.', 'srs-ai-chatbot');
            }
        }

        // Check if order is too old
        $max_days = $this->wc_settings['max_days_back'] ?? 365;
        $order_date = $order->get_date_created();
        $days_old = (time() - $order_date->getTimestamp()) / (24 * 60 * 60);
        
        if ($days_old > $max_days) {
            return __('Order is too old to display information.', 'srs-ai-chatbot');
        }

        return $this->format_order_info($order);
    }

    /**
     * Format order information
     */
    private function format_order_info($order) {
        $template = $this->wc_settings['response_template'] ?? $this->get_default_template();
        
        // Get order data
        $order_data = array(
            '[order_number]' => $order->get_order_number(),
            '[order_status]' => wc_get_order_status_name($order->get_status()),
            '[order_date]' => $order->get_date_created()->date('F j, Y'),
            '[order_total]' => $order->get_formatted_order_total(),
            '[billing_name]' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '[billing_email]' => $order->get_billing_email(),
            '[shipping_method]' => $this->get_shipping_method_name($order),
            '[tracking_number]' => $this->get_tracking_number($order),
            '[estimated_delivery]' => $this->get_estimated_delivery($order)
        );

        // Add items list
        $items = array();
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = $item->get_quantity() . 'x ' . $item->get_name();
        }
        $order_data['[order_items]'] = implode(', ', $items);

        // Replace placeholders
        $response = str_replace(array_keys($order_data), array_values($order_data), $template);
        
        return $response;
    }
    /**
     * Get shipping method name
     */
    private function get_shipping_method_name($order) {
        $shipping_methods = array();
        foreach ($order->get_shipping_methods() as $shipping_method) {
            $shipping_methods[] = $shipping_method->get_method_title();
        }
        return !empty($shipping_methods) ? implode(', ', $shipping_methods) : __('Not specified', 'srs-ai-chatbot');
    }

    /**
     * Get tracking number (if available from plugins)
     */
    private function get_tracking_number($order) {
        // Check for common tracking plugins
        $tracking_number = '';
        
        // WooCommerce Shipment Tracking
        if (function_exists('wc_st_add_tracking_number')) {
            $tracking_info = get_post_meta($order->get_id(), '_wc_shipment_tracking_items', true);
            if (!empty($tracking_info)) {
                $tracking_number = $tracking_info[0]['tracking_number'] ?? '';
            }
        }
        
        // Advanced Shipment Tracking
        if (empty($tracking_number) && class_exists('WC_Advanced_Shipment_Tracking_Actions')) {
            $tracking_items = get_post_meta($order->get_id(), '_wc_shipment_tracking_items', true);
            if (!empty($tracking_items)) {
                $tracking_number = $tracking_items[0]['tracking_number'] ?? '';
            }
        }
        
        // Custom tracking meta (adjust as needed)
        if (empty($tracking_number)) {
            $tracking_number = get_post_meta($order->get_id(), '_tracking_number', true);
        }
        
        return $tracking_number ?: __('Not available', 'srs-ai-chatbot');
    }

    /**
     * Get estimated delivery date
     */
    private function get_estimated_delivery($order) {
        // Check for delivery date meta
        $delivery_date = get_post_meta($order->get_id(), '_delivery_date', true);
        
        if ($delivery_date) {
            return date('F j, Y', strtotime($delivery_date));
        }
        
        // Calculate based on shipping method and order date
        $shipping_days = $this->estimate_shipping_days($order);
        if ($shipping_days) {
            $estimated_date = strtotime($order->get_date_created()->date('Y-m-d') . " +{$shipping_days} days");
            return date('F j, Y', $estimated_date);
        }
        
        return __('Not available', 'srs-ai-chatbot');
    }

    /**
     * Estimate shipping days based on shipping method
     */
    private function estimate_shipping_days($order) {
        $shipping_methods = $order->get_shipping_methods();
        
        if (empty($shipping_methods)) {
            return null;
        }
        
        $method = reset($shipping_methods);
        $method_id = $method->get_method_id();
        
        // Default estimates (can be configured)
        $estimates = array(
            'free_shipping' => 5,
            'flat_rate' => 3,
            'local_pickup' => 1,
            'expedited' => 2
        );
        
        return $estimates[$method_id] ?? 5; // Default to 5 days
    }
    /**
     * Get default order response template
     */
    private function get_default_template() {
        return __('Here\'s your order information:

Order #[order_number]
Status: [order_status]
Order Date: [order_date]
Total: [order_total]

Items: [order_items]

Shipping Method: [shipping_method]
Tracking Number: [tracking_number]
Estimated Delivery: [estimated_delivery]

If you have any questions about your order, please don\'t hesitate to ask!', 'srs-ai-chatbot');
    }

    /**
     * Get recent orders for a customer
     */
    public function get_customer_recent_orders($email, $limit = 5) {
        if (!class_exists('WooCommerce')) {
            return array();
        }

        $customer = get_user_by('email', $email);
        
        $args = array(
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        if ($customer) {
            $args['customer_id'] = $customer->ID;
        } else {
            $args['billing_email'] = $email;
        }
        
        return wc_get_orders($args);
    }

    /**
     * Get product information by name or SKU
     */
    public function get_product_info($search_term) {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        // Search by SKU first
        $product_id = wc_get_product_id_by_sku($search_term);
        
        if ($product_id) {
            $product = wc_get_product($product_id);
        } else {
            // Search by name
            $products = wc_get_products(array(
                'name' => $search_term,
                'limit' => 1,
                'status' => 'publish'
            ));
            
            $product = !empty($products) ? $products[0] : null;
        }

        if (!$product) {
            return __('Product not found.', 'srs-ai-chatbot');
        }

        return $this->format_product_info($product);
    }

    /**
     * Format product information
     */
    private function format_product_info($product) {
        $info = $product->get_name() . "\n\n";
        
        if ($product->get_short_description()) {
            $info .= wp_strip_all_tags($product->get_short_description()) . "\n\n";
        }
        
        $info .= "Price: " . $product->get_price_html() . "\n";
        $info .= "Stock: " . ($product->is_in_stock() ? __('In Stock', 'srs-ai-chatbot') : __('Out of Stock', 'srs-ai-chatbot')) . "\n";
        
        if ($product->get_sku()) {
            $info .= "SKU: " . $product->get_sku() . "\n";
        }
        
        $info .= "Product URL: " . $product->get_permalink() . "\n";
        
        return $info;
    }

    /**
     * Check if user can view order information
     */
    public function can_view_order($order, $email = null) {
        if (!$order) {
            return false;
        }

        // If email verification is disabled, allow access
        if (!($this->wc_settings['require_email'] ?? true)) {
            return true;
        }

        // If email is provided, check if it matches
        if ($email && strtolower($order->get_billing_email()) === strtolower($email)) {
            return true;
        }

        // If user is logged in and owns the order
        if (is_user_logged_in() && $order->get_customer_id() === get_current_user_id()) {
            return true;
        }

        return false;
    }
}
