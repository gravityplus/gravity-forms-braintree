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

}
