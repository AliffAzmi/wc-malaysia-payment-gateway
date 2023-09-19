<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bank_Transfer_Payment_Gateway extends WC_Payment_Gateway
{
    /**
     * Array of locales
     *
     * @var array
     */
    public $locale;

    /**
     * Gateway instructions that will be added to the thank you page and emails.
     *
     * @var string
     */
    public $instructions;

    /**
     * Account details.
     *
     * @var array
     */
    public $account_details;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'bank_transfer_payment_gateway';
        $this->method_title = __('Bank Transfer', $this->id);
        $this->method_description = __('Malaysia payment gateway via custom bank transfer.', $this->id);
        $this->title = __('Bank Transfer', $this->id);
        $this->icon = null;
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions');

        $this->account_details = get_option(
            'woocommerce_bank_transfer_accounts',
            array(
                array(
                    'account_name'   => $this->get_option('account_name'),
                    'account_number' => $this->get_option('account_number'),
                    'sort_code'      => $this->get_option('sort_code'),
                    'bank_name'      => $this->get_option('bank_name'),
                    'iban'           => $this->get_option('iban'),
                    'bic'            => $this->get_option('bic'),
                ),
            )
        );

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_account_details'));
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Bank Transfer Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Bank Transfer',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Make your payment via our custom bank transfer payment gateway.',
            ),
            'instructions'    => array(
                'title'       => __('Instructions', $this->id),
                'type'        => 'textarea',
                'description' => __('Instructions that will be added to the thank you page and emails.', $this->id),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'account_details' => array(
                'type' => 'account_details',
            ),
        );
    }

    /**
     * Generate account details html.
     *
     * @return string
     */
    public function generate_account_details_html()
    {
        ob_start();

        $country = WC()->countries->get_base_country();
        $locale  = $this->get_country_locale();

        $sortcode = isset($locale[$country]['sortcode']['label']) ? $locale[$country]['sortcode']['label'] : __('Sort code', $this->id);
?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php esc_html_e('Account details:', $this->id); ?></th>
            <td class="forminp" id="bacs_accounts">
                <div class="wc_input_table_wrapper">
                    <table class="widefat wc_input_table sortable" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="sort">&nbsp;</th>
                                <th><?php esc_html_e('Account name', $this->id); ?></th>
                                <th><?php esc_html_e('Account number', $this->id); ?></th>
                                <th><?php esc_html_e('Bank name', $this->id); ?></th>
                                <th><?php echo esc_html($sortcode); ?></th>
                                <th><?php esc_html_e('IBAN', $this->id); ?></th>
                                <th><?php esc_html_e('BIC / Swift', $this->id); ?></th>
                            </tr>
                        </thead>
                        <tbody class="accounts">
                            <?php
                            $i = -1;
                            if ($this->account_details) {
                                foreach ($this->account_details as $account) {
                                    $i++;

                                    echo '<tr class="account">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr(wp_unslash($account['account_name'])) . '" name="bacs_account_name[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr($account['account_number']) . '" name="bacs_account_number[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr(wp_unslash($account['bank_name'])) . '" name="bacs_bank_name[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr($account['sort_code']) . '" name="bacs_sort_code[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr($account['iban']) . '" name="bacs_iban[' . esc_attr($i) . ']" /></td>
										<td><input type="text" value="' . esc_attr($account['bic']) . '" name="bacs_bic[' . esc_attr($i) . ']" /></td>
									</tr>';
                                }
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7"><a href="#" class="add button"><?php esc_html_e('+ Add account', $this->id); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e('Remove selected account(s)', $this->id); ?></a></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <script type="text/javascript">
                    jQuery(function() {
                        jQuery('#bacs_accounts').on('click', 'a.add', function() {

                            var size = jQuery('#bacs_accounts').find('tbody .account').length;

                            jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_sort_code[' + size + ']" /></td>\
									<td><input type="text" name="bacs_iban[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bic[' + size + ']" /></td>\
								</tr>').appendTo('#bacs_accounts table tbody');

                            return false;
                        });
                    });
                </script>
            </td>
        </tr>
    <?php
        return ob_get_clean();
    }

    /**
     * Save account details table.
     */
    public function save_account_details()
    {
        $accounts = array();

        if (
            isset($_POST['bacs_account_name']) &&
            isset($_POST['bacs_account_number']) &&
            isset($_POST['bacs_bank_name']) &&
            isset($_POST['bacs_sort_code']) &&
            isset($_POST['bacs_iban']) &&
            isset($_POST['bacs_bic'])
        ) {

            $account_names   = wc_clean(wp_unslash($_POST['bacs_account_name']));
            $account_numbers = wc_clean(wp_unslash($_POST['bacs_account_number']));
            $bank_names      = wc_clean(wp_unslash($_POST['bacs_bank_name']));
            $sort_codes      = wc_clean(wp_unslash($_POST['bacs_sort_code']));
            $ibans           = wc_clean(wp_unslash($_POST['bacs_iban']));
            $bics            = wc_clean(wp_unslash($_POST['bacs_bic']));

            foreach ($account_names as $i => $name) {
                if (!isset($account_names[$i])) {
                    continue;
                }

                $accounts[] = array(
                    'account_name'   => $account_names[$i],
                    'account_number' => $account_numbers[$i],
                    'bank_name'      => $bank_names[$i],
                    'sort_code'      => $sort_codes[$i],
                    'iban'           => $ibans[$i],
                    'bic'            => $bics[$i],
                );
            }
        }

        do_action('woocommerce_update_option', array('id' => 'woocommerce_bank_transfer_accounts'));
        update_option('woocommerce_bank_transfer_accounts', $accounts);
    }

    /**
     * Generate custom ui payment fields.
     *
     * @return string
     */
    public function payment_fields()
    {
        $accounts = $this->account_details;
    ?>
        <div class="bank_transfer-payment-fields">
            <div>
                <!-- Step 1 -->
                <h5><span class=" badge rounded-pill bg-danger ">Step 1</span></h5>
                <h4>Order is only confirmed upon receiving email confirmation</h4>
                <p>You may make payment via online instant bank transfer / ATM / cash deposit to the following bank account:</p>
                <?php
                if ($accounts) {
                    foreach ($accounts as $account) {
                ?>
                        <div class="row">
                            <div class="col">Bank Name</div>
                            <div class="col"><img src="images/bank-logo.png" alt="Bank Logo" height="25"></div>
                        </div>
                        <div class="row">
                            <div class="col">Account Name</div>
                            <div class="col"><?php echo esc_html($account['bank_name']); ?></div>
                        </div>
                        <div class="row">
                            <div class="col">Account Number</div>
                            <div class="col"><?php echo esc_html($account['account_number']); ?>
                            </div>
                    <?php
                    }
                }
                    ?>
                        </div>
                        <div>
                            <!-- Step 2 -->
                            <h5><span class=" badge rounded-pill bg-danger">Step 2</span></h5>

                            <p>Upon completion, please upload your transaction receipt. For ATM / cash deposit, you may snap a photo of the receipt and upload it.</p>

                            <!-- More content here -->

                            <h5>Upload bank slip to get email confirmation</h5>
                            <input type="hidden" name="receipt_upload" class="receipt_upload" />
                            <input type="file" class="receipt_upload_file" name="bank_transfer_receipt_upload" id="bank_transfer_receipt_upload" accept="image/*,.pdf" />
                            <embed src='#' class="receipt_preview" id="bank_transfer_receipt_preview" width="400" height="400" style="display: none;">
                        </div>
            </div>
    <?php

    }

    /**
     * Get country locale if localized.
     *
     * @return array
     */
    public function get_country_locale()
    {

        if (empty($this->locale)) {

            // Locale information to be used - only those that are not 'Sort Code'.
            $this->locale = apply_filters(
                'woocommerce_get_bacs_locale',
                array(
                    'AU' => array(
                        'sortcode' => array(
                            'label' => __('BSB', 'woocommerce'),
                        ),
                    ),
                    'CA' => array(
                        'sortcode' => array(
                            'label' => __('Bank transit number', 'woocommerce'),
                        ),
                    ),
                    'IN' => array(
                        'sortcode' => array(
                            'label' => __('IFSC', 'woocommerce'),
                        ),
                    ),
                    'IT' => array(
                        'sortcode' => array(
                            'label' => __('Branch sort', 'woocommerce'),
                        ),
                    ),
                    'NZ' => array(
                        'sortcode' => array(
                            'label' => __('Bank code', 'woocommerce'),
                        ),
                    ),
                    'SE' => array(
                        'sortcode' => array(
                            'label' => __('Bank code', 'woocommerce'),
                        ),
                    ),
                    'US' => array(
                        'sortcode' => array(
                            'label' => __('Routing number', 'woocommerce'),
                        ),
                    ),
                    'ZA' => array(
                        'sortcode' => array(
                            'label' => __('Branch code', 'woocommerce'),
                        ),
                    ),
                )
            );
        }

        return $this->locale;
    }

    /**
     * Custom process payment. We store receipt file path and date upload in order meta.
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        $uploaded_file = $_POST['receipt_upload'];

        if (!empty($uploaded_file)) {

            update_post_meta($order_id, 'receipt_upload_path', sanitize_text_field($uploaded_file));
            update_post_meta($order_id, 'receipt_upload_date_uploaded', current_time('Y-m-d H:i:s'));

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
