<?php
$braintree_fields = [
    'billing' => [
        'company' => 'Company Name',
        'countryCodeAlpha2'     => 'Country Code (e.g US)',
        'countryCodeAlpha3'     => 'Country Code (e.g. USA)',
        'countryCodeNumeric'    => 'Country Code (e.g. +1)',
        'countryName'           => 'Country Name',
        'extendedAddress'       => 'Apartment or Suite Number',
        'firstName'             => 'First Name',
        'lastName'              => 'Last Name',
        'locality'              => 'Locality/City',
        'postalCode'            => 'Postal Code',
        'region'                => 'State/Province',
        'streetAddress'         => 'Street Address',
    ],
    'shipping' => [
        'company' => 'Company Name',
        'countryCodeAlpha2'     => 'Country Code (e.g US)',
        'countryCodeAlpha3'     => 'Country Code (e.g. USA)',
        'countryCodeNumeric'    => 'Country Code (e.g. +1)',
        'countryName'           => 'Country Name',
        'extendedAddress'       => 'Apartment or Suite Number',
        'firstName'             => 'First Name',
        'lastName'              => 'Last Name',
        'locality'              => 'Locality/City',
        'postalCode'            => 'Postal Code',
        'region'                => 'State/Province',
        'streetAddress'         => 'Street Address',
    ],
    //'customFields'              => 'Custom Fields (Defined in your account)',
    'customer' => [
        'company'               => 'Company Name',
        'email'                 => 'Email',
        'firstName'             => 'First Name',
        'lastName'              => 'Last Name',
        'phone'                 => 'Phone',
        'website'               => 'Website URL'
    ]
];

$form_id = $_GET['id'];
$get_form = GFAPI::get_form($form_id);
//echo '<pre>';print_r($get_form); die;

$braintree_mapping = isset($get_form['braintree_fields_mapping'])?$get_form['braintree_fields_mapping']:[];

function agreegateAllFields($main_arr, $parent_key = ''){
    $final_fields = [];
    foreach ($main_arr as $main_key => $single_field){
        if(is_array($single_field))
            $final_fields[$main_key] = agreegateAllFields($single_field, $main_key);
        else {
            $final_fields[($parent_key != '' ? $parent_key . '.' : '') . $main_key] = $single_field;
        }
    }

    return $final_fields;
}

$total_braintree_fields = agreegateAllFields($braintree_fields);
//print_r($total_braintree_fields);

$gravity_fields = $get_form['fields'];
$ignore_type_fields = ['creditcard'];

$final_gravity_fields = [];
foreach ($gravity_fields as $gravity_field) {
    if(in_array($gravity_field->type, $ignore_type_fields))
        continue;
    $final_gravity_fields[$gravity_field['id']] = $gravity_field['label'];
}
//print_r($gravity_fields);
?>
<h3><span><i class="fa fa-cogs"></i> Braintree field Mapping</span></h3>
<p>
    Please map the gravity form fields with Braintree fields
</p>
<form action="<?php echo add_query_arg(['action'=>'save_gravity_form_mapping'], admin_url('admin-ajax.php')) ?>" method="post" id="gform_braintree_mapping">
    <input type="hidden" name="gform_id" value="<?php echo $form_id ?>">
    <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
        <tbody>
        <?php  foreach ($total_braintree_fields as $keyname => $single_field_set){
                if(is_array($single_field_set)){
                    ?>
                    <tr>
                        <td colspan="2"><h4 class="gf_settings_subgroup_title"><?php echo ucfirst($keyname) ?></h4></td>
                    </tr>
                    <?php
                    foreach ($single_field_set as $key => $label){
                        $selected_option = isset($braintree_mapping[$key])?$braintree_mapping[$key]:'';
                        ?>
                        <tr>
                            <td width="300"><?php echo $label ?></td>
                            <td width="300">
                                <select name="gravity_form_field[<?php echo $key ?>]">
                                    <option value="">-- Map Form Field --</option>
                                    <?php foreach ($final_gravity_fields as $gid=>$single_field_label)
                                        echo '<option value="'.$gid.'" '.($selected_option==$gid?'selected':'').'>'.$single_field_label.'</option>'?>
                                </select>
                            </td>
                        </tr>

                        <?php
                    }
                }else{
                    $selected_option = isset($braintree_mapping[$keyname])?$braintree_mapping[$keyname]:'';
            ?>
                    <tr>
                        <td><?php echo $label ?></td>
                        <td>
                            <select name="gravity_form_field[<?php echo $keyname ?>]">
                                <option value="">-- Map Form Field --</option>
                                <?php foreach ($final_gravity_fields as $gid=>$single_field_label)
                                    echo '<option value="'.$gid.'" '.($selected_option==$gid?'selected':'').'>'.$single_field_label.'</option>'?>
                            </select>
                        </td>
                    </tr>
        <?php }
        } ?>
        <tr class="custom_field_row"><td colspan="2"><h4 class="gf_settings_subgroup_title">Custom Fields <a href="#" onclick="return false;" onkeypress="return false;" class="gf_tooltip tooltip tooltip_notification_send_to_email" title="<h6>Setup custom fields</h6>You can map your braintree custom fields with gravity form fields."><i class="fa fa-question-circle"></i></a> <a class="pull-right addmorecustomfield">Add Custom Field</a></h4></td></tr>

        <?php if(isset($braintree_mapping['custom_fields']))
            foreach($braintree_mapping['custom_fields'] as $key => $mapped_id){
            ?><tr class="custom_field_row">
                <td><input type="text" name="gravity_form_custom_field_name[]" value="<?php echo $key ?>" placeholder="Please enter your field name from BrainTree" class="form-control" ></td>
                <td>
                    <select name="gravity_form_custom_field[]">
                        <option value="">-- Map Form Field --</option>
                        <?php foreach ($final_gravity_fields as $gid=>$single_field_label)
                            echo '<option value="'.$gid.'" '.($mapped_id==$gid?'selected':'').'>'.$single_field_label.'</option>'?>
                    </select>
                    <a class="remove_custom_field">Remove</a> </td>
              </tr>
            <?php
        } ?>
        <tr><td colspan="2">
                <button class="button button-primary">Update Mapping</button>
                <div class="successful_message"></div>
            </td></tr>
        </tbody>
    </table>
</form>
<div class="hide custom_fields_template">
    <select name="gravity_form_custom_field[]">
        <option value="">-- Map Form Field --</option>
        <?php foreach ($final_gravity_fields as $gid=>$single_field_label)
            echo '<option value="'.$gid.'">'.$single_field_label.'</option>'?>
    </select>
</div>
<style type="text/css">
    .form-control{width:99%}
    .pull-right{
        float: right;}
    .hide{display: none;}
</style>