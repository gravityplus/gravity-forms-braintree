jQuery(function () {
    jQuery('[id^=angelleye_notification]').each(function (i) {
        jQuery('[id="' + this.id + '"]').slice(1).remove();
    });
    var el_notice = jQuery(".angelleye-notice");
    el_notice.fadeIn(750);
    jQuery(".angelleye-notice-dismiss").click(function(e){
        e.preventDefault();
        jQuery( this ).parent().parent(".angelleye-notice").fadeOut(600, function () {
            jQuery( this ).parent().parent(".angelleye-notice").remove();
        });
        notify_wordpress(jQuery( this ).data("msg"));
    });
    function notify_wordpress(message) {
        var param = {
            action: 'angelleye_gform_braintree_adismiss_notice',
            data: message
        };
        jQuery.post(ajaxurl, param);
    }
});

jQuery(document).ready(function ($) {
    $('.addmorecustomfield').click(function () {
        $('.custom_field_row:last').after('<tr class="custom_field_row"><td><input type="text" name="gravity_form_custom_field_name[]" value="" placeholder="Please enter your field name from BrainTree" class="form-control" ></td><td>'+$('.custom_fields_template').html()+' <a class="remove_custom_field">Remove</a> </td></tr>');
    });

    $('body').on('click','.remove_custom_field', function () {
        $(this).closest('tr.custom_field_row') .remove();
    });

    $('#gform_braintree_mapping').submit(function (e) {
        e.preventDefault();

        var data = $(this).serialize();
        var url = $(this).attr('action');
        $('.successful_message').html('');
        $('.updatemappingbtn').html('Saving...').attr('disabled','disabled');
        $.ajax({
            url:url,
            method: 'post',
            'data': data,
            'dataType': 'json'
        }).done(function (response) {
            if(response.status){
                $('.successful_message').html('<div class="updated fade"><p>'+response.message+'</p></div>')
            }else {
                console.log('error', response);
            }

        }).complete(function () {
            $('.updatemappingbtn').html('Update Mapping').removeAttr('disabled');
        });

    })
});