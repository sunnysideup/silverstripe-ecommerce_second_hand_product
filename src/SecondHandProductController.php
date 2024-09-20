<?php

namespace Sunnysideup\EcommerceSecondHandProduct;

use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use Sunnysideup\Ecommerce\Pages\ProductController;

/**
 * Class \Sunnysideup\EcommerceSecondHandProduct\SecondHandProductController
 *
 * @property \Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct $dataRecord
 * @method \Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct data()
 * @mixin \Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct
 */
class SecondHandProductController extends ProductController
{
    private static $fields_to_remove_from_print = [];

    private static $allowed_actions = [
        'printview' => true,
    ];

    public function printview()
    {
        if (! Permission::check('CMS_ACCESS_SECOND_HAND_PRODUCTS')) {
            return Security::permissionFailure($this, 'You do not have access to this feature, please login first.');
        }

        return $this->RenderWith('SecondHandProduct_printview');
    }

    public function ListOfFieldsForPrinting()
    {
        $al = ArrayList::create();
        $fieldsWeNeed = $this->dataRecord->stat('db');
        $labels = $this->FieldLabels();
        foreach (array_keys($fieldsWeNeed) as $fieldKey) {
            if (in_array($fieldKey, self::$fields_to_remove_from_print, true)) {
                unset($fieldsWeNeed[$fieldKey]);
            } else {
                $fieldsWeNeed[$fieldKey] = $labels[$fieldKey];
            }
        }

        $fields = $this->dataRecord->Config()->get('db');
        foreach ($fieldsWeNeed as $key => $description) {
            if (isset($fields[$key])) {
                $type = preg_replace('#\(.*\)#', '', (string) $fields[$key]);
                $dbField = DBField::create_field($type, $this->{$key});
                $value = $dbField->hasMethod('Nice') ? $dbField->Nice() : $dbField->Raw();
            } else {
                $value = '';
            }

            $al->push(
                ArrayData::create(
                    [
                        'Key' => $description,
                        'Value' => $value,
                    ]
                )
            );
        }

        return $al;
    }

    public function IsSecondHandSection(): bool
    {
        return true;
    }
}
