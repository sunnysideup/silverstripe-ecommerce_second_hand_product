<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Cms;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\GridField\AbstractGridFieldComponent;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class RecentlySoldRestoreAction extends AbstractGridFieldComponent implements
    GridField_ColumnProvider,
    GridField_ActionProvider
{
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'grid-field__col-compact'];
    }

    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName === 'Actions') {
            return ['title' => ''];
        }
        return [];
    }

    public function getColumnsHandled($gridField)
    {
        return ['Actions'];
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if (!$record->hasMethod('canEdit') || !$record->canEdit()) {
            return null;
        }

        $field = GridField_FormAction::create(
            $gridField,
            'CustomAction' . $record->ID,
            'Restore as Copy',
            'restoreproductcopy',
            ['RecordID' => $record->ID]
        );

        $field->addExtraClass('action font-icon-level-up action-detail edit-link btn btn-secondary');

        return $field->Field();
    }

    public function getActions($gridField)
    {
        return ['restoreproductcopy'];
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName !== 'restoreproductcopy') {
            return;
        }

        $id = $arguments['RecordID'] ?? null;
        if (!$id) {
            Controller::curr()->getResponse()
                ->setStatusCode(500)
                ->addHeader('X-Status', 'Product ID Not Found.');
            return;
        }
        else {
            $soldProduct = SecondHandProduct::get_by_id($id);

            $copy = $soldProduct->duplicate(true, [
                'Image',
                'BasedOn',
                'EquivalentNewProduct',
                'ProductGroups'
            ]);
            SecondHandProductActions::quick_enable($copy);

            Controller::curr()->getResponse()
                ->setStatusCode(200)
                ->addHeader('X-Status', $soldProduct->Title . ' Copied.');

            return Controller::curr()->redirect(
                $copy->CMSEditLink()
            );
        }
    }
}