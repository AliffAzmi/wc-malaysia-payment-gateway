jQuery(function ($) {
  var payment_method = $('input[name="payment_method"]:checked').val()
  var active_pm_container = `.payment_box.payment_method_${payment_method}`
  $(document).on(
    'input',
    "#payment .wc_payment_method input[name='payment_method']",
    function () {
      payment_method = $(this).val()
      active_pm_container = `.payment_box.payment_method_${payment_method}`
    }
  )
  if (!is_allowed(payment_method)) {
    return
  }

  $(document).on('input', `.receipt_upload_file`, function () {
    var input = this
    const [file] = input.files
    if (file) {
      let reader = new FileReader()
      reader.onload = function (e) {
        $(`${active_pm_container} .receipt_preview`)
          .attr('src', URL.createObjectURL(file))
          .show()
        handleUploadFile(file)
      }
      reader.readAsDataURL(file)
    } else {
      $(`${active_pm_container} .receipt_preview`).attr('src', '#').hide()
    }
  })

  function handleUploadFile (file) {
    var formData = new FormData()
    formData.append('receipt_upload', file)
    $.ajax({
      url: wc_checkout_params.ajax_url + '?action=receipt_upload',
      type: 'POST',
      data: formData,
      contentType: false,
      enctype: 'multipart/form-data',
      processData: false,
      success: function (data) {
        $('.receipt_upload').val(data)
      },
      error: function (err) {
        console.log(err)
      }
    })
  }

  function is_allowed (payment_method) {
    var allowed_payment_methods = [
      'bank_transfer_payment_gateway',
      'duitnow_qr_payment_gateway'
    ]
    return allowed_payment_methods.includes(payment_method)
  }
})
