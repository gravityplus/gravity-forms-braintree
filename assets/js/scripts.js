/* !- gravity forms braintree scripts */

jQuery( function($) {

  // When gravity form selection changes, load the the edit feed page
  $('select#form_id[name="_gaddon_setting_form_id"]').bind('change', function(e) {

    var new_id = $(this).children(':selected').val();

    if( new_id == '' )
      return false;

    $.ajax({

				type: 'POST',
				dataType: 'json',
				url: gf_braintree_scripts_strings.ajax_url,
				data: {
          id: new_id,
          fid: gf_braintree_scripts_strings.feed_id,
          action: 'map_feed_fields'
        },
				beforeSend: function() {

				},
				success: function( result ) {

          var html = $.parseHTML(result.data.html);
          html = $(html).find('#gaddon-setting-row-gf_braintree_mapped_fields').children();

          $('#gaddon-setting-row-gf_braintree_mapped_fields').children().remove();
          $('#gaddon-setting-row-gf_braintree_mapped_fields').append(html);

				},
				error: function () {

				},
				complete: function () {

				}

			});

  });

});
