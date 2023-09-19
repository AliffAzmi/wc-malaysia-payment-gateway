<?php

if (!defined('ABSPATH')) {
    exit;
}

class DuitNow_QR_Payment_Gateway extends WC_Payment_Gateway
{
    private $asset_url;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'duitnow_qr_payment_gateway';
        $this->method_title = __('DuitNow QR', 'duitnow-qr-payment-gateway');
        $this->method_description = __('Malaysia payment gateway via DuitNow QR.', 'duitnow-qr-payment-gateway');
        $this->title = __('via DuitNow QR', 'duitnow-qr-payment-gateway');
        $this->icon = null;
        $this->has_fields = true;
        $this->init_form_fields();
        $this->init_settings();

        global $asset_url;
        $this->asset_url = $asset_url;

        wp_enqueue_script('preview-receipt', $this->asset_url . 'js/preview-receipt.js', array('jquery'), time(), true);

        // Action.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable DuitNow QR Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'via DuitNow QR',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Make your payment via our DuitNow QR payment gateway.',
            ),
            'qr_code' => array(
                'title'       => 'Upload QR Code',
                'type'        => 'file',
                'description' => 'Upload your DuitNow QR code here.',
                'default'     => '',
                'desc_tip'    => true,
                'required'    => true
            )
        );
    }

    /**
     * Process the admin options and save qr code.
     */
    public function process_admin_options()
    {
        $this->upload_qr_code();

        $saved = parent::process_admin_options();
        return $saved;
    }

    /**
     * Generate admin settings html with qr code.
     */
    public function admin_options()
    { ?>
        <h2><?php echo esc_html($this->method_title); ?></h2>
        <p><?php echo esc_html($this->method_description); ?></p>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>

            <!-- Add a custom row for the uploaded QR code -->
            <tr valign="top">
                <th scope="row"></th>
                <td>
                    <?php
                    $file_field_name = 'qr_code';
                    $file_path_option = $this->plugin_id . $this->id . '_' . $file_field_name;
                    $file_path = get_option($file_path_option);

                    // Display the uploaded QR code if it exists
                    if ($file_path) {
                        $upload_dir = wp_upload_dir();
                        $base_url = $upload_dir['baseurl'];
                        $relative_path = str_replace($upload_dir['basedir'], '', $file_path);
                        $image_url = $base_url . $relative_path;

                        echo '<img src="' . esc_url($image_url) . '" alt="QR Code" style="max-width: 200px;" />';
                    }
                    ?>
                </td>
            </tr>
        </table>
    <?php
    }

    private function upload_qr_code()
    {
        $file_field_name = 'woocommerce_duitnow_qr_payment_gateway_qr_code';
        if (isset($_FILES[$file_field_name])) {

            $uploaded_file = $_FILES[$file_field_name];

            if ($uploaded_file['error'] == 0) {

                $timestamp = time();
                $original_file_name = $uploaded_file['name'];
                $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
                $file_name = $timestamp . '-' . wp_hash($original_file_name) . '.' . $file_extension;

                $upload_dir = wp_upload_dir();
                $file_path = $upload_dir['path'] . '/' . $file_name;

                if (move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
                    // File uploaded successfully, you can save the file path or do further processing here
                    update_option($file_field_name, $file_path);
                }
            }
        }
    }

    /**
     * Generate custom payment form html. Adjust or remove it if u don't need it on checkout page.
     */
    public function payment_fields()
    {

        $file_field_name = 'qr_code';
        $file_path_option = $this->plugin_id . $this->id . '_' . $file_field_name;
        $file_path = get_option($file_path_option);

    ?>
        <div class="qr_code-payment-fields">
            <div>
                <!-- Step 1 -->
                <h5><span class=" badge rounded-pill bg-danger ">Step 1</span></h5>
                <h4>Order is only confirmed upon receiving email confirmation</h4>
                <p>You may make payment via DuitNow QR below</p>

                <?php

                if ($file_path) {
                    $upload_dir = wp_upload_dir();
                    $base_url = $upload_dir['baseurl'];
                    $relative_path = str_replace($upload_dir['basedir'], '', $file_path);
                    $image_url = $base_url . $relative_path;
                ?>

                    <div class=" text-center">
                        <img src="<?= $this->asset_url . 'images/icon-duit-now.png' ?>" alt="DuitNow Icon" style="max-width: 80px;" />
                        <div>
                            <img src="<?= esc_url($image_url) ?>" alt="QR Code" style="max-width: 200px;" />
                        </div>
                    </div>

                <?php } ?>

            </div>
            <div>
                <!-- Step 2 -->
                <h5><span class=" badge rounded-pill bg-danger">Step 2</span></h5>
                <p>Upon completion, please upload your transaction receipt. Kindly contact customer service if you require further assistance.</p>

                <!-- More content here -->

                <h5>Upload receipt to get email confirmation</h5>
                <input type="hidden" name="receipt_upload" class="receipt_upload" />
                <input type="file" class="receipt_upload_file" name="duitnow_qr_receipt_upload" id="duitnow_qr_receipt_upload" accept="image/*,.pdf" />
                <embed src='#' class="receipt_preview" id="duitnow_qr_receipt_preview" width="400" height="400" style="display: none;">
            </div>
        </div>
<?php

    }

    /*
	* Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
	*/
    public function payment_scripts()
    {
    }

    /*
    * Fields validation
    */
    public function validate_fields()
    {
    }

    /*
    * We're processing the payments here. Trigger when placing order.
    */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        $uploaded_file = $_POST['receipt_upload'];

        if (!empty($uploaded_file)) {

            update_post_meta($order_id, 'receipt_upload_path', sanitize_text_field($uploaded_file));
            update_post_meta($order_id, 'receipt_upload_date_uploaded', current_time('Y-m-d H:i:s'));
            // update_post_meta($order_id, 'receipt_upload_status', 'uploaded');

            $order->payment_complete();
            wc_reduce_stock_levels($order_id);

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            wc_add_notice(__('Please upload a receipt before placing your order.', $this->id), 'error');
            return array(
                'result'   => 'fail',
                'redirect' => wc_get_checkout_url(),
            );
        }
    }
}
