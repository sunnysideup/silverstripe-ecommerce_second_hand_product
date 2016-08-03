<?php


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
    public function AddForm() {
        $pageTypes = array();
        foreach($this->PageTypes() as $type) {
            $html = sprintf('<span class="page-icon class-%s"></span><strong class="title">%s</strong><span class="description">%s</span>',
                $type->getField('ClassName'),
                $type->getField('AddAction'),
                $type->getField('Description')
            );
            $pageTypes[$type->getField('ClassName')] = $html;
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

        // TODO Re-enable search once it allows for HTML title display,
        // see http://open.silverstripe.org/ticket/7455
        // $parentField->setShowSearch(true);

        $parentField->addExtraClass('parent-mode');

        // CMSMain->currentPageID() automatically sets the homepage,
        // which we need to counteract in the default selection (which should default to root, ID=0)
        if($parentID = $this->getRequest()->getVar('ParentID')) {
            $parentField->setValue((int)$parentID);
        }

        $actions = new FieldList(
            FormAction::create("doAdd", _t('CMSMain.Create',"Create"))
                ->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true),
            FormAction::create("doCancel", _t('CMSMain.Cancel',"Cancel"))
                ->addExtraClass('ss-ui-action-destructive ss-ui-action-cancel')
                ->setUseButtonTag(true)
        );

        $this->extend('updatePageOptions', $fields);

        $form = CMSForm::create(
            $this, "AddForm",
            $fields,
            $actions
        )->setHTMLID('Form_AddForm');
        $form->setAttribute('data-hints', $this->SiteTreeHints());
        $form->setAttribute('data-childfilter', $this->Link('childfilter'));

        $form->setResponseNegotiator($this->getResponseNegotiator());

        return $form;
    }

    public function doAdd($data, $form) {
        $className = isset($data['PageType']) ? $data['PageType'] : "Page";
        $parentID = isset($data['ParentID']) ? (int)$data['ParentID'] : 0;

        $suffix = isset($data['Suffix']) ? "-" . $data['Suffix'] : null;

        if( ! $parentID && isset($data['Parent'])) {
            $page = SiteTree::get_by_link($data['Parent']);
            if($page) {
                $parentID = $page->ID;
            }
        }

        if(is_numeric($parentID) && $parentID > 0) {
            $parentObj = ProductGroup::get()->byID($parentID);
        } else {
            $parentObj = null;
        }

        if(!$parentObj || !$parentObj->ID) {
            $parentID = 0;
        }

        if(!singleton($className)->canCreate(
            Member::currentUser(),
            array('Parent' => $parentObj))
        ) {
            return Security::permissionFailure($this);
        }

        $record = $this->getNewItem("new-$className-$parentID".$suffix, false);
        $this->extend('updateDoAdd', $record, $form);

        try {
            $record->write();
        } catch(ValidationException $ex) {
            $form->sessionMessage($ex->getResult()->message(), 'bad');
            return $this->getResponseNegotiator()->respond($this->getRequest());
        }

        Session::set(
            "FormInfo.Form_EditForm.formError.message",
            _t('CMSMain.PageAdded', 'Successfully created page')
        );
        Session::set("FormInfo.Form_EditForm.formError.type", 'good');

        return $this->redirect($record->CMSEditLink());
    }

    public function doCancel($data, $form) {
        return $this->redirect(singleton('SecondHandProductAdmin')->Link());
    }

    /**
     * @return ArrayList
     */
    public function PageTypes()
    {
        $pageTypes = parent::PageTypes();
        $result = new ArrayList();
        $productClass = Object::getCustomClass('SecondHandProduct');
        $acceptedClasses = ClassInfo::subclassesFor($productClass);
        foreach ($pageTypes as $type) {
            if (in_array($type->ClassName, $acceptedClasses)) {
                $result->push($type);
            }
        }
        return $result;
    }
}
