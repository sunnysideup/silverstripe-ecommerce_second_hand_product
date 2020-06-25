<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Cms;









use CMSForm;








use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Forms\LiteralField;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProductGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\CMS\Model\SiteTree;
use Sunnysideup\Ecommerce\Pages\ProductGroup;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Control\Controller;
use Sunnysideup\EcommerceSecondHandProduct\Cms\SecondHandProductAdmin;
use SilverStripe\ORM\ArrayList;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use SilverStripe\Core\ClassInfo;
use SilverStripe\CMS\Controllers\CMSPageAddController;




class CMSPageAddController_SecondHandProducts extends CMSPageAddController
{
    private static $url_segment = 'addsecondhandproduct';

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $url_priority = 40;

    private static $menu_title = 'Add Second Hand Product';

    private static $required_permission_codes = 'CMS_ACCESS_SECOND_HAND_PRODUCTS';

    private static $allowed_actions = array(
        'AddForm',
        'doAdd',
        'doCancel',
    );

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
            ;
        }

        $numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><span class="title">%s</span></span>';

        $fields = new FieldList(
            new LiteralField('PageModeHeader', sprintf($numericLabelTmpl, 1, _t('CMSMain.ChoosePageParentMode', 'Choose where to create this page'))),
            $parentField = DropdownField::create(
                "ParentID",
                "Category",
                SecondHandProductGroup::get()->map()
            ),
            $typeField = new OptionsetField(
                "PageType",
                sprintf($numericLabelTmpl, 2, _t('CMSMain.ChoosePageType', 'Choose page type')),
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

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: ->dontEscape (case sensitive)
  * NEW: ->dontEscape (COMPLEX)
  * EXP: dontEscape is not longer in use for form fields, please use HTMLReadonlyField (or similar) instead.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $typeField->dontEscape = true;

        // TODO Re-enable search once it allows for HTML title display,
        // see http://open.silverstripe.org/ticket/7455
        // $parentField->setShowSearch(true);

        $parentField->addExtraClass('parent-mode');

        // CMSMain->currentPageID() automatically sets the homepage,
        // which we need to counteract in the default selection (which should default to root, ID=0)
        if ($parentID = $this->getRequest()->getVar('ParentID')) {
            $parentField->setValue((int)$parentID);
        }

        $actions = new FieldList(
            FormAction::create("doAdd", _t('CMSMain.Create', "Create"))
                ->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true),
            FormAction::create("doCancel", _t('CMSMain.Cancel', "Cancel"))
                ->addExtraClass('ss-ui-action-destructive ss-ui-action-cancel')
                ->setUseButtonTag(true)
        );

        $this->extend('updatePageOptions', $fields);

        $form = CMSForm::create(
            $this,
            "AddForm",
            $fields,
            $actions
        )->setHTMLID('Form_AddForm');
        $form->setAttribute('data-hints', $this->SiteTreeHints());
        $form->setAttribute('data-childfilter', $this->Link('childfilter'));

        $form->setResponseNegotiator($this->getResponseNegotiator());

        return $form;
    }

    public function doAdd($data, $form)
    {

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $className = isset($data['PageType']) ? $data['PageType'] : "Page";
        $parentID = isset($data['ParentID']) ? (int)$data['ParentID'] : 0;

        $suffix = isset($data['Suffix']) ? "-" . $data['Suffix'] : null;

        if (! $parentID && isset($data['Parent'])) {
            $page = SiteTree::get_by_link($data['Parent']);
            if ($page) {
                $parentID = $page->ID;
            }
        }

        if (is_numeric($parentID) && $parentID > 0) {
            $parentObj = ProductGroup::get()->byID($parentID);
        } else {
            $parentObj = null;
        }

        if (!$parentObj || !$parentObj->ID) {
            $parentID = 0;
        }


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        if (!singleton($className)->canCreate(
            Member::currentUser(),
            array('Parent' => $parentObj)
        )
        ) {
            return Security::permissionFailure($this);
        }


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: $className (case sensitive)
  * NEW: $className (COMPLEX)
  * EXP: Check if the class name can still be used as such
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $record = $this->getNewItem("new-$className-$parentID".$suffix, false);
        $this->extend('updateDoAdd', $record, $form);

        try {
            $record->write();
        } catch (ValidationException $ex) {
            $form->sessionMessage($ex->getResult()->message(), 'bad');
            return $this->getResponseNegotiator()->respond($this->getRequest());
        }


/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        Controller::curr()->getRequest()->getSession()->set(
            "FormInfo.Form_EditForm.formError.message",
            _t('CMSMain.PageAdded', 'Successfully created page')
        );

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: Session:: (case sensitive)
  * NEW: Controller::curr()->getRequest()->getSession()-> (COMPLEX)
  * EXP: If THIS is a controller than you can write: $this->getRequest(). You can also try to access the HTTPRequest directly. 
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        Controller::curr()->getRequest()->getSession()->set("FormInfo.Form_EditForm.formError.type", 'good');

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

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD:  Object:: (case sensitive)
  * NEW:  SilverStripe\\Core\\Injector\\Injector::inst()-> (COMPLEX)
  * EXP: Check if this is the right implementation, this is highly speculative.
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $productClass = SilverStripe\Core\Injector\Injector::inst()->getCustomClass(SecondHandProduct::class);
        $acceptedClasses = ClassInfo::subclassesFor($productClass);
        foreach ($pageTypes as $type) {
            if (in_array($type->ClassName, $acceptedClasses)) {
                $result->push($type);
            }
        }
        return $result;
    }
}

