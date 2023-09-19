<?php
/*
Plugin Name: Woocommerce Custom Payment Gateway Malaysia
Description: Malaysia Custom Payment Gateway for Woocommerce that supports DuitNow QR and custom Bank Transfer.
Version: 1.0
Author: Aliff Azmi
Author URI: https://aliffazmi.com/
License: GPLv2 or later
*/

$asset_url = plugins_url("/assets/", __FILE__);

add_action('plugins_loaded', 'init_custom_payment_gateway');
function init_custom_payment_gateway()
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        require_once(WC()->plugin_path() . '/includes/abstracts/abstract-wc-payment-gateway.php');
        require_once(plugin_dir_path(__FILE__) . 'includes/class-duitnow-qr.php');
        require_once(plugin_dir_path(__FILE__) . 'includes/class-bank-transfer.php');

        require_once plugin_dir_path(__FILE__) . 'includes/partials/class-custom-payment-gateway-ajax.php';
        require_once plugin_dir_path(__FILE__) . 'includes/partials/class-custom-payment-gateway-metabox.php';
        require_once plugin_dir_path(__FILE__) . 'includes/partials/class-custom-payment-gateway-admin-column.php';

        global $asset_url;

        /**
         * This class is use to create metabox and show receipt in admin order page.
         */
        $metabox = new Custom_Payment_Gateway_MetaBox($asset_url);

        /**
         * Handle ajax request on checkout page.
         */
        $ajax_handler = new Custom_Payment_Gateway_Ajax_Handler();

        /**
         * Create receipt flag/status in order table.
         */
        $admin_order_column = new Custom_Payment_Gateway_Admin_Column();

        /**
         * Register our custom payment gateway class.
         */
        add_filter('woocommerce_payment_gateways', 'add_custom_payment_gateways');
        function add_custom_payment_gateways($gateways)
        {
            global $asset_url;

            $gateways[] = 'DuitNow_QR_Payment_Gateway';
            $gateways[] = 'Bank_Transfer_Payment_Gateway';
            return $gateways;
        }

        /**
         * Hook our settings.
         */
        add_filter("plugin_action_links", "plugin_action_links", 10, 2);
        function plugin_action_links($links, $file)
        {
            if (plugin_basename(__FILE__) == $file) {
                $links['wc-custom-payment-gateway-settings'] = '<a href="' . admin_url("admin.php?page=wc-settings&tab=checkout") . '">Settings</a>';
            }
            return $links;
        }
    }
}
