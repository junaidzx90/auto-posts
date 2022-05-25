jQuery(document).ready(function ($) {
  $(document).on("click", '.ap_upload_thumbnail', function (e) {
    let btn = $(this);

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
      btn.parents('td').find('.ap_default_thumbnail').val(attachment.id);
      btn
        .parents('td')
        .find('.ap_thumbnail_preview')
        .html(`<img class="preview_thumbnail_img" src="${attachment.url}">`);
    });
    // Open the upload dialog
    post_thumbnail.open();
  });

  $('.ap_remove_thumbnail').on('click', function (e) {
    e.preventDefault();
    $(this).parents('td').find('.ap_thumbnail_preview').html('');
    $(this).parents('td').find('.ap_default_thumbnail').val('');
  });

  // Selectable options
  initialSelectBox();
  function initialSelectBox() {
    $('.ap_post_author').selectize({
      placeholder: ' Select an author',
    });
    $('.ap_default_tag').selectize({
      placeholder: ' Select tags',
      plugins: ['remove_button'],
    });
  }

  // Add single template content
  $(document).on('click', '.add_new_template', function (e) {
    e.preventDefault();
    let namef = $(this).data('name');
    let textareaContent = `<div class="template_content"> <textarea name="${namef}[ap_contents][]" placeholder="To fix the issues in %%cat_name%%, you have to do the following steps." class="widefat" rows="5"></textarea> <p>Use <code>%%cat_name%%</code> to show category name inside texts.</p> <span class="remove_template">+</span></div>`;
    $(this).parents('td').find('.__default_templates').append(textareaContent);
  });
  // Remove template content
  $(document).on('click', '.remove_template', function () {
    if (confirm('You will lose your saved template!')) {
      $(this).parents('.template_content').remove();
    }
  });

  let loader = `<div id="loaderspin"> <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="50px" height="50px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve"> <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946 s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634 c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"></path> <path fill="#2271b1" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0 C22.32,8.481,24.301,9.057,26.013,10.047z"> <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="0.9s" repeatCount="indefinite"></animateTransform> </path> </svg> </div>`;

  // Add new additional template
  $('#add_new_template').on('click', function (e) {
    e.preventDefault();
    let timestamp = Date.now();
    $.ajax({
      type: 'get',
      url: autopost.ajaxurl,
      data: {
        action: 'get_additional_template',
        index: timestamp
      },
      beforeSend: ()=>{
        $("body").append(loader);
      },
      dataType: 'json',
      success: function (response) {
        $(document).find("#loaderspin").remove();
        if (response.template) {
          $('#additional_templates').append(response.template);
          $('#user-'+timestamp).selectize({
            placeholder: ' Select an author',
          });

          $('#tags-'+timestamp).selectize({
            placeholder: ' Select tags',
            plugins: ['remove_button'],
          });
        }
      },
    });
  });

  // Additionl remove template
  $(document).on('click', '.removeTemplate', function (e) {
    e.preventDefault();
    if (confirm('You will lose your saved template!')) {
      $(this).parents('.__template').remove();
    }
  });
});
