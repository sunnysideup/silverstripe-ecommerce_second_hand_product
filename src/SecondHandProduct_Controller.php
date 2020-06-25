<?php

class SecondHandProductController extends ProductController
{
    private static $fields_to_remove_from_print = [];

    private static $allowed_actions = array(
        'printview' => true
    );

    public function printview()
    {
        if (!Permission::check('CMS_ACCESS_SECOND_HAND_PRODUCTS')) {
            return Security::permissionFailure($this, 'You do not have access to this feature, please login first.');
        }

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: ->RenderWith( (ignore case)
  * NEW: ->RenderWith( (COMPLEX)
  * EXP: Check that the template location is still valid!
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        return $this->RenderWith('SecondHandProduct_printview');
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

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: ->db() (case sensitive)
  * NEW: ->Config()->get('db') (COMPLEX)
  * EXP: Check implementation
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $fields = $this->dataRecord->Config()->get('db');
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
