jQuery(document).ready(function ($) {
  $('#ap_upload_thumbnail').click(function (e) {
    e.preventDefault();
    var post_thumbnail;

    if (post_thumbnail) {
      post_thumbnail.open();
      return;
    }
    // Extend the wp.media object
    post_thumbnail = wp.media.frames.file_frame = wp.media({
      title: 'Select thumbnail',
      button: {
        text: 'Select',
      },
      multiple: false,
    });

    // When a file is selected, grab the URL and set it as the text field's value
    post_thumbnail.on('select', function () {
      var attachment = post_thumbnail.state().get('selection').first().toJSON();
      $('#ap_default_thumbnail').val(attachment.id);
      $('.ap_thumbnail_preview').html(
        `<img style="width: 190px; margin: 10px 0; border: 2px solid #ddd; border-radius: 5px;" src="${attachment.url}">`
      );
    });
    // Open the upload dialog
    post_thumbnail.open();
  });

  $('#ap_remove_thumbnail').on('click', function (e) {
    e.preventDefault();
    $('.ap_thumbnail_preview').html('');
    $('#ap_default_thumbnail').val('');
  });
});
