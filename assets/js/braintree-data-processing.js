jQuery( function($) {
    braintree.client.create({
        authorization: braintree_data_processing_strings.bt_magic
    }, function (err, clientInstance) {
        // Creation of any other components...

        // Inside of your client create callback...
        braintree.dataCollector.create({
            client: clientInstance,
            kount: true
        }, function (err, dataCollectorInstance) {
            if (err) {
                // Handle error in data collector creation
                return;
            }
            var deviceDataInput = jQuery(".ginput_card_expiration_year").parents('form').find('input[type=hidden]').first();

            if (deviceDataInput == null) {
                deviceDataInput = document.createElement('input');
                deviceDataInput.name = 'device_data';
                deviceDataInput.type = 'hidden';
                form.appendChild(deviceDataInput);
            }

            deviceDataInput.val(dataCollectorInstance.deviceData);
        });
    });
});
