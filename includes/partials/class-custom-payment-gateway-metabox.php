<?php

class Custom_Payment_Gateway_MetaBox
{
    /**
     * Constructor for the metabox.
     */
    public function __construct($asset_url)
    {
        $this->asset_url = $asset_url;
        $this->defaultImg = $asset_url . 'images/no-image.png';

        wp_enqueue_script('upload-receipt-order', $asset_url . 'js/upload-receipt-order.js', array('jquery'), time(), true);

        add_action('admin_enqueue_scripts', array($this, 'load_media_files'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes_custom_payment_gateway'));
        add_action("save_post", array($this, "receipt_upload_save"));
    }

    /**
     * Load WordPress media files.
     */
    function load_media_files()
    {
        wp_enqueue_media();
    }

    /**
     * Initialise the meta box by post type and custom payment method.
     */
    public function add_meta_boxes_custom_payment_gateway()
    {
        global $post;

        if ($post->post_type === 'shop_order') {
            $order_id = $post->ID;
            $order = wc_get_order($order_id);

            $payment_method = $order->get_payment_method();
            $allowed_payment_methods = array('duitnow_qr_payment_gateway', 'bank_transfer_payment_gateway');

            if (in_array($payment_method, $allowed_payment_methods)) {
                add_meta_box('custom_payment_gateway_meta_box', 'Upload Receipt', array($this, 'render_meta_box'), 'shop_order', 'side', 'high');
            }
        }
    }

    /**
     * Generate meta box content.
     * @param object $post
     * @return string
     */
    public function render_meta_box($post)
    {
        $order                 = $post instanceof \WC_Order ? $post : wc_get_order($post->ID);
        $payment_method        = $order->get_payment_method();
        $receipt_upload_path   = get_post_meta($post->ID, 'receipt_upload_path', true);
        $receipt_date_uploaded = get_post_meta($post->ID, 'receipt_upload_date_uploaded', true);
        $receipt_upload_admin_note = get_post_meta($post->ID, 'receipt_upload_admin_note', true);
        add_thickbox();

        $src = $receipt_upload_path ? $receipt_upload_path : $this->defaultImg; ?>
        <div style="display: flex;flex-direction: column;width: 100%;">
            <img data-def="<?= $this->defaultImg ?>" id="change_receipt_file" title="Click to change" src="<?= $src ?>" style="width: 100%;min-height: 90px;border-radius: 4px;border: 1px solid #ccc;">
            <p class="hidden"><input type="text" name="receipt_upload_path" class="receipt_upload_path" id="receipt_upload_path" value="<?= $receipt_upload_path ?>"></p>
        </div>
        <p>
            <span>Uploaded at: <date><?= $receipt_date_uploaded ?></date></span>
        </p>
        <p style="display: grid; gap: 5px;">
            <?php if ($receipt_upload_path) { ?>
                <a href="#TB_inline?height=600&amp;width=550&amp;inlineId=examplePopup1" class="button viewfile thickbox" style="width: 100%;" id="receipt_upload_view"><span style="margin: 4px;" class="dashicons dashicons-cover-image"></span>View Receipt</a>
            <?php } ?>
            <a href="#" class="button changefile" style="width: 100%;" id="receipt_upload_btn"><span style="margin: 4px;" class="dashicons dashicons-format-image"></span><?= $receipt_upload_path ? 'Change Receipt Image' : 'Upload Receipt Image' ?></a>
            <a href="#" class="button changedate" style="width: 100%;" id="receipt_upload_date_btn"><span style="margin: 4px;" class="dashicons dashicons-calendar-alt"></span>Change Upload Date</a>
        </p>
        <p>
            <input type="text" dir="ltr" style="display: none; width: 100%;" autocomplete="off" name="receipt_upload_date_uploaded" id="receipt_upload_date_uploaded" value="<?= $receipt_date_uploaded ?>">
        </p>

        <label for="receipt_upload_note">Admin Note</label>
        <div>
            <textarea rows="5" style="width: 100%;" autocomplete="off" name="receipt_upload_admin_note" id="receipt_upload_admin_note"><?= $receipt_upload_admin_note ?></textarea>
        </div>

        <p style="font-size: 11px;"><em>*Please update the order if changes have been made</em></span></p>

        <div id="examplePopup1" style="display:none">
            <div style="float:left;padding:10px;">
                <img src="<?= $receipt_upload_path ?>" id="receipt_upload_view_img" width="500" />
            </div>
        </div>
<?php
    }

    /**
     * Save the meta box data.
     * @param int $post_id
     */
    public function receipt_upload_save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['receipt_upload_path'])) {
            update_post_meta($post_id, 'receipt_upload_path', sanitize_text_field($_POST['receipt_upload_path']));
        }

        if (isset($_POST['receipt_upload_date_uploaded'])) {
            update_post_meta($post_id, 'receipt_upload_date_uploaded', sanitize_text_field($_POST['receipt_upload_date_uploaded']));
        }

        if (isset($_POST['receipt_upload_admin_note'])) {
            update_post_meta($post_id, 'receipt_upload_admin_note', sanitize_text_field($_POST['receipt_upload_admin_note']));
        }
    }
}
