jQuery(function ($) {
  $(document).ready(function () {
    $(document).on('click tap', '.changefile', function (e) {
      e.preventDefault()
      $('#change_receipt_file').trigger('click')
    })

    $(document).on('click tap', '.changedate', function (e) {
      e.preventDefault()
      $('#receipt_upload_date_uploaded').toggle()
    })

    $(document).on('click tap', '#change_receipt_file', function (e) {
      var image_frame,
        me = $(this)
      if (image_frame) {
        image_frame.open()
      }
      image_frame = wp.media({
        title: '',
        multiple: false,
        library: {}
      })

      image_frame.on('select', function () {
        if (image_frame.state().get('selection').first()) {
          var selection = image_frame.state().get('selection').first().toJSON()
          let url = new URL(selection.url)
          let path = url.pathname
          $('.receipt_upload_path').val(path)
          $('#change_receipt_file').attr('src', selection.url)
          $('#receipt_upload_view_img').attr('src', selection.url)
        }
      })

      //   image_frame.on('open', function() {
      //     var selection = image_frame.state().get('selection');
      //     console.log(selection)
      //   })

      image_frame.open()
    })
  })
})
