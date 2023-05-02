<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Cms;

use SilverStripe\CMS\Controllers\CMSPageAddController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Security;
use Sunnysideup\Ecommerce\Config\EcommerceConfigClassNames;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;

/**
 * adds a special kind of add form for new second had products.
 *
 */
class CMSPageAddControllerSecondHandProducts extends CMSPageAddController
{
    private static $url_segment = 'addsecondhandproduct';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 40;

    private static $menu_title = 'Add Second Hand Product';

    private static $required_permission_codes = 'CMS_ACCESS_SECOND_HAND_PRODUCTS';

    private static $allowed_actions = [
        'AddForm',
        'doAdd',
        'doCancel',
    ];

    /**
     * @return Form
     */
    public function AddForm()
    {
        $pageTypes = [];
        foreach ($this->PageTypes() as $type) {
            $html = sprintf(
                '<span class="page-icon class-%s"></span><strong class="title">%s</strong><span class="description">%s</span>',
                $type->getField('ClassName'),
                $type->getField('AddAction'),
                $type->getField('Description')
            );
            $pageTypes[$type->getField('ClassName')] = DBField::create_field('HTMLText', $html);
        }

        $numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><span class="title">%s</span></span>';

        $fields = new FieldList(
            LiteralField::create(
                'PageModeHeader',
                DBField::create_field(
                    'HTMLText',
                    sprintf(
                        $numericLabelTmpl,
                        1,
                        _t('CMSMain.ChoosePageParentMode', 'Choose where to create this page')
                    )
                )
            ),
            $parentField = DropdownField::create(
                'ParentID',
                'Category',
                SecondHandProductGroup::get()->map()
            ),
            $typeField = new OptionsetField(
                'PageType',
                DBField::create_field(
                    'HTMLText',
                    sprintf(
                        $numericLabelTmpl,
                        2,
                        _t('CMSMain.ChoosePageType', 'Choose page type')
                    )
                ),
                $pageTypes
            ),
            new LiteralField(
                'RestrictedNote',
                sprintf(
                    '<p class="message notice message-restricted">%s</p>',
                    _t(
                        'CMSMain.AddPageRestriction',
                        'Note: Some page types are not allowed for this selection'
                    )
                )
            )
        );

        // TODO Re-enable search once it allows for HTML title display,
        // see http://open.silverstripe.org/ticket/7455
        // $parentField->setShowSearch(true);

        $parentField->addExtraClass('parent-mode');

        // CMSMain->currentPageID() automatically sets the homepage,
        // which we need to counteract in the default selection (which should default to root, ID=0)
        $parentID = $this->getRequest()->getVar('ParentID');
        if ($parentID) {
            $parentField->setValue((int) $parentID);
        }

        $actions = new FieldList(
            FormAction::create('doAdd', _t('CMSMain.Create', 'Create'))
                ->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true),
            FormAction::create('doCancel', _t('CMSMain.Cancel', 'Cancel'))
                ->addExtraClass('ss-ui-action-destructive ss-ui-action-cancel')
                ->setUseButtonTag(true)
        );

        $this->extend('updatePageOptions', $fields);

        $form = Form::create(
            $this,
            'AddForm',
            $fields,
            $actions
        )->setHTMLID('Form_AddForm');
        $form->setAttribute('data-hints', $this->SiteTreeHints());
        $form->setAttribute('data-childfilter', $this->Link('childfilter'));

        return $form;
    }

    public function doAdd($data, $form)
    {
        $className = isset($data['PageType']) ? $data['PageType'] : \Page::class;
        $parentID = isset($data['ParentID']) ? (int) $data['ParentID'] : 0;

        $suffix = isset($data['Suffix']) ? '-' . $data['Suffix'] : null;

        if (! $parentID && isset($data['Parent'])) {
            $page = SiteTree::get_by_link($data['Parent']);
            if ($page) {
                $parentID = $page->ID;
            }
        }

        if (is_numeric($parentID) && $parentID > 0) {
            $parentObj = ProductGroup::get_by_id($parentID);
        } else {
            $parentObj = null;
        }

        if (! $parentObj || ! $parentObj->ID) {
            $parentID = 0;
        }

        if (! singleton($className)->canCreate(
            Security::getCurrentUser(),
            ['Parent' => $parentObj]
        )
        ) {
            return Security::permissionFailure($this);
        }

        $record = $this->getNewItem("new-{$className}-{$parentID}" . $suffix, false);
        $this->extend('updateDoAdd', $record, $form);

        try {
            $record->write();
        } catch (ValidationException $validationException) {
            foreach ($validationException->getResult()->getMessages() as $messageArray) {
                $form->sessionMessage($messageArray['message'], $messageArray['messageType']);
            }

            return $this->getResponseNegotiator()->respond($this->getRequest());
        }

        $this->getRequest()->getSession()->set(
            'FormInfo.Form_EditForm.formError.message',
            _t('CMSMain.PageAdded', 'Successfully created page')
        );

        $this->getRequest()->getSession()->set('FormInfo.Form_EditForm.formError.type', 'good');

        return $this->redirect($record->CMSEditLink());
    }

    public function doCancel($data, $form)
    {
        return $this->redirect(singleton(SecondHandProductAdmin::class)->Link());
    }

    /**
     * @return ArrayList
     */
    public function PageTypes()
    {
        $pageTypes = parent::PageTypes();
        $result = new ArrayList();
        $productClass = EcommerceConfigClassNames::getName(SecondHandProduct::class);
        $acceptedClasses = ClassInfo::subclassesFor($productClass);
        foreach ($pageTypes as $type) {
            if (in_array($type->ClassName, $acceptedClasses, true)) {
                $result->push($type);
            }
        }

        return $result;
    }
}
