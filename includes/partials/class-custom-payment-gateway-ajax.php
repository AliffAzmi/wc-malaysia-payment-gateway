<?php

class Custom_Payment_Gateway_Ajax_Handler
{
    /**
     * Constructor for the ajax.
     */
    public function __construct()
    {
        add_action('wp_ajax_receipt_upload', array($this, 'ajax_receipt_upload'));
        add_action('wp_ajax_nopriv_receipt_upload', array($this, 'ajax_receipt_upload'));
    }

    /**
     * Return the receipt upload path.
     * @var string
     */
    public function ajax_receipt_upload()
    {
        $upload_dir = wp_upload_dir();
        if (isset($_FILES['receipt_upload'])) {
            $path = $upload_dir['path'] . '/' . basename($_FILES['receipt_upload']['name']);
            if (move_uploaded_file($_FILES['receipt_upload']['tmp_name'], $path)) {
                $url_parsed = parse_url($upload_dir['url']);
                echo $url_parsed['path'] . '/' . basename($_FILES['receipt_upload']['name']);
            }
        }
        die;
    }
}
