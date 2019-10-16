<?php

defined('ABSPATH') or die('Direct Access Not allowed');

class AngelleyeGravityBraintreeFieldMapping
{
    public function __construct()
    {
        //if(isset($_GET['gf_edit_forms']))
        add_filter( 'gform_form_settings_menu', array($this, 'addBraintreeMappingMenu' ), 100, 2);
        add_action( 'gform_form_settings_page_braintree_mapping_settings_page', array($this, 'braintreeFieldMapping') );

        add_action('wp_ajax_save_gravity_form_mapping', array($this,'saveMapping'));

        add_filter('angelleye_braintree_parameter', array($this, 'mapGravityBraintreeFields'),10, 4);

        add_filter( 'gform_field_content', array($this,'addNoticeToCreditCardForm'), 10, 5 );
    }

    public function addBraintreeMappingMenu($menu_items, $form_id)
    {
        if(isset($_GET['page']) && $_GET['page']=='gf_edit_forms') {
            if($this->isCreditCardFieldExist($form_id)) {
                $menu_items[] = array(
                    'name' => 'braintree_mapping_settings_page',
                    'label' => __('BrainTree Fields Mapping')
                );
            }
        }
        return $menu_items;
    }

    public function braintreeFieldMapping()
    {
        GFFormSettings::page_header();
        require dirname(__FILE__).'/pages/angelleye-braintree-field-map-form.php';
        GFFormSettings::page_footer();
    }

    public function isCreditCardFieldExist($id)
    {
        $get_form = GFAPI::get_form($id);
        if(isset($get_form['fields'])) {
            foreach ($get_form['fields'] as $single_field) {
                if ($single_field->type == 'creditcard') {
                    return true;
                }
            }
        }
        return false;
    }

    public function saveMapping()
    {
        $form_id = $_POST['gform_id'];
        //sanitize input values
        $final_mapping = [];
        foreach ($_POST['gravity_form_field'] as $key=>$field_id){
            if(empty($field_id)) continue;
            $final_mapping[$key]  = $field_id;
        }

        $custom_fields=[];
        if(isset($_POST['gravity_form_custom_field_name'])){
            $mapped_field_ids = $_POST['gravity_form_custom_field'];
            foreach ($_POST['gravity_form_custom_field_name'] as $key => $single_custom_field_name){
                if(!isset($mapped_field_ids[$key]) || empty($mapped_field_ids[$key]))
                    continue;

                $custom_fields[$single_custom_field_name] = $mapped_field_ids[$key];
            }
        }
        if(count($custom_fields))
            $final_mapping['custom_fields'] = $custom_fields;

        $get_form = GFAPI::get_form($form_id);
        $get_form['braintree_fields_mapping'] = $final_mapping;
        GFAPI::update_form($get_form, $form_id);

        die(json_encode(['status'=>true,'message'=>'Mapping has been updated successfully.']));
    }

    function assignArrayByPath(&$arr, $path, $value, $separator='.') {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;
    }

    public function mapGravityBraintreeFields($args, $submission_data, $form, $entry)
    {

        $braintree_mapping = isset($form['braintree_fields_mapping'])?$form['braintree_fields_mapping']:[];
        $final_array = [];

        if(count($braintree_mapping)){
            foreach ($braintree_mapping as $key_name => $single_mapid)
            {
                if(is_array($single_mapid)){
                    if($key_name=='custom_fields') {
                        foreach ($single_mapid as $subkey_name => $sub_mapid) {
                            if (isset($entry[$sub_mapid])) {
                                $this->assignArrayByPath($final_array, 'customFields.' . $subkey_name,  $entry[$sub_mapid]);
                            }
                        }
                    }
                }else {
                    if (isset($entry[$single_mapid])) {
                        $this->assignArrayByPath($final_array, $key_name, $entry[$single_mapid]);
                    }
                }
            }
        }
        if(count($final_array)){
            $args = array_merge($args, $final_array);
        }
        //var_dump($args); die;
        return $args;
    }

    function addNoticeToCreditCardForm( $field_content, $field,  $value, $lead_id, $form_id ) {
        if ( $field->type == 'creditcard' ) {
            //echo ($field_content); die;
            $first_label_position = strpos($field_content, '<label');
            if($first_label_position!==false) {
                //'http://localhost/angelleye-wp/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=&id=3'
                $mapping_page_link = add_query_arg(['view'=>'settings', 'subview' => 'braintree_mapping_settings_page', 'id' => $form_id ], menu_page_url('gf_edit_forms', false));
                $feed_page_link = add_query_arg(['view'=>'settings', 'subview' => 'gravity-forms-braintree', 'id' => $form_id ], menu_page_url('gf_edit_forms', false));
                $field_content = substr_replace($field_content,
                    "<div style='background-color: #f5e5cd;padding: 10px;color: #000;opacity: 0.83;transition: opacity 0.6s;margin:10px 0;'><p style='margin: 0'>1) You can use <a href='$mapping_page_link'>Braintree Fields Mapping</a> to send the values with transaction.<br>2) To process the payments, please configure the <a href='$feed_page_link'>Braintree feed</a>.</p></div>",
                    $first_label_position, 0);
            }
        }

        return $field_content;
    }

}
