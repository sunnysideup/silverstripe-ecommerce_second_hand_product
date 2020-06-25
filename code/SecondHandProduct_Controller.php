<?php

class SecondHandProduct_Controller extends Product_Controller
{
    private static $fields_to_remove_from_print = array();

    private static $allowed_actions = array(
        'printview' => true
    );

    public function printview()
    {
        if (!Permission::check('CMS_ACCESS_SECOND_HAND_PRODUCTS')) {
            return Security::permissionFailure($this, 'You do not have access to this feature, please login first.');
        }
        return $this->renderWith('SecondHandProduct_printview');
    }

    public function ListOfFieldsForPrinting()
    {
        $al = ArrayList::create();
        $fieldsWeNeed = $this->dataRecord->stat('db');
        $labels = $this->FieldLabels();
        foreach ($fieldsWeNeed as $fieldKey => $useless) {
            if (in_array($fieldKey, self::$fields_to_remove_from_print)) {
                unset($fieldsWeNeed[$fieldKey]);
            } else {
                $fieldsWeNeed[$fieldKey] = $labels[$fieldKey];
            }
        }
        $fields = $this->dataRecord->db();
        foreach ($fieldsWeNeed as $key => $description) {
            if (isset($fields[$key])) {
                $type = preg_replace('/\(.*\)/', '', $fields[$key]);
                $dbField = DBField::create_field($type, $this->$key);
                if ($dbField->hasMethod('Nice')) {
                    $value = $dbField->Nice();
                } else {
                    $value = $dbField->Raw();
                }
            } else {
                $value = "";
            }
            $al->push(
                ArrayData::create(
                    array(
                        'Key' => $description,
                        'Value' => $value
                    )
                )
            );
        }
        return $al;
    }
}
