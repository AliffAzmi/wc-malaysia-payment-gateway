<?php

class Custom_Payment_Gateway_Admin_Column
{
    /**
     * Constructor for the order column.
     */
    public function __construct()
    {
        add_filter("manage_edit-shop_order_columns", array($this, "column_header"), 20);
        add_action("manage_shop_order_posts_custom_column", array($this, "column_content"));
    }

    /**
     * Get the column header and add new payment receipt header.
     *
     * @param array $columns.
     */
    public function column_header($columns)
    {
        $new_columns = array();
        foreach ($columns as $column_name => $column_info) {
            $new_columns[$column_name] = $column_info;
            if ('order_status' === $column_name) {
                $new_columns['wcuploadrcp'] = __('Payment Receipt', 'custom_payment_gateway');
            }
        }
        return $new_columns;
    }

    /**
     * Adjust column content.
     *
     * @param string $column_name.
     */
    public function column_content($column_name)
    {
        global $post;
        if ('wcuploadrcp' !== $column_name) {
            return;
        }
        $order_id = $post->ID;
        $order = new WC_Order($post->ID);
        $receipt_upload_path = get_post_meta($order_id, 'receipt_upload_path', true);

        $payment_method = $order->get_payment_method();
        $allowed_payment_methods = array('duitnow_qr_payment_gateway', 'bank_transfer_payment_gateway');

        if (in_array($payment_method, $allowed_payment_methods)) {
            if ($receipt_upload_path) {
                echo "<span style='background: #c6e1c6; color: #5b841b; text-align: center; padding: 8px; border-radius: 5px;'>Receipt uploaded</span>";
            } else {
                echo "<span style='background: #e5e5e5; color: #777; text-align: center; padding: 8px; border-radius: 5px;'>Awaiting receipt upload</span>";
            }
        } else {
            echo $order->get_payment_method_title();
        }
    }
}
