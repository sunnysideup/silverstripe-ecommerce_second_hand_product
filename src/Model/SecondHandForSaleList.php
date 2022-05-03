<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;
use Sunnysideup\Vardump\Vardump;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\Ecommerce\Forms\Fields\EcommerceCMSButtonField;
use SilverStripe\Control\Email\Email;
use Sunnysideup\Ecommerce\Api\ClassHelpers;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use SilverStripe\Control\Director;
use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;

use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;

class SecondHandForSaleList extends DataObject
{

    private static $keep_for_days = 7;

    private static $table_name = 'SecondHandForSaleList';

    private static $email_admin = true;

    private static $delete_old = true;

    private static $db = [
        'Title' => 'Varchar',
        'ProductCount' => 'Int',
        'ForSale' => 'Text',
        'Added' => 'Text',
        'Removed' => 'Text',
        'Archived' => 'Text',
        'EmailPrepared' => 'Boolean',
        'EmailSent' => 'Boolean',
        'Notes' => 'Text',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'ProductCount' => 'Count',
        'Added' => 'Added',
        'Removed' => 'Removed',
        'Archived' => 'Archived',
        'EmailPrepared.Nice' => 'Email Ready',
        'EmailSent.Nice' => 'Email Sent',
    ];

    private static $singular_name = 'For Sale List Entry';

    private static $plural_name = 'For Sale List Entries';

    private static $default_sort = [
        'ID' => 'DESC',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Summary',
            [
                LiteralField::create(
                    'HTMLSummary',
                    $this->HTMLSummary()
                )
            ]
        );
        $fields->addFieldsToTab(
            'Root.ProductsForSale',
            [
                ReadonlyField::create(
                    'ForSale',
                    'For Sale'
                )
            ]
        );
        foreach(array_keys($this->Config()->get('db')) as $fieldName) {
            if($fieldName !== 'Notes') {
                $fields->replaceField($fieldName, $fields->dataFieldByName($fieldName)->performReadonlyTransformation());
            }
        }
        return $fields;
    }

    public function deleteOldData()
    {
        if($this->Config()->delete_old) {
            $archived = [];
            $daysAgo = $this->Config()->keep_for_days;
            if($daysAgo) {
                $timeFilter = [
                    'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgo . ' days')) . ' 00:00:00'
                ];
                $olderOnes = SecondHandForSaleList::get()->filter($timeFilter)->limit(30);
                foreach($olderOnes as $item) {
                    $removeList = explode(',', $item->Removed);
                    $item->ForSale = '';
                    $item->write();
                    foreach($removeList as $code) {
                        $obj = SecondHandProduct::get()->filter(['AllowPurchase' => 1, 'InternalItemID' => $code])->first();
                        if($obj) {
                            $archived[] = $obj->InternalItemID;
                            SecondHandProductActions::archive($obj);
                        }
                    }
                }
                $timeFilterLastEdited = [
                    'LastEdited:LessThan' => date('Y-m-d', strtotime('-' . $daysAgo . ' days')) . ' 00:00:00'
                ];
                $objects = SecondHandProduct::get()->filter($timeFilterLastEdited + ['AllowPurchase' => 0])->limit(50);
                foreach($objects as $obj) {
                    $archived[] = $obj->InternalItemID;
                    SecondHandProductActions::archive($obj);
                }
            }
            $this->Archived = implode(',', $archived);
            $this->write();
        }
    }


    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if(! $this->ForSale) {
            $currentArray = SecondHandProduct::get()
                ->filter(['AllowPurchase' => 1])
                ->sort('Created DESC')
                ->column('InternalItemID');
            $this->ProductCount = count($currentArray);
            $this->ForSale = implode(',', $currentArray);
            $prev = SecondHandForSaleList::get()
                ->exclude(['ID' => (int) $this->ID])
                ->sort(['ID' => 'DESC'])->first();
            if($prev) {
                $prevArray = explode(',', (string) $prev->ForSale);
                $this->Added = implode(',', array_diff($currentArray, $prevArray));
                $this->Removed = implode(',', array_diff($prevArray, $currentArray));
            }
        }
    }


    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if(! $this->Title) {
            $this->Title = 'Second Hand Products For Sale on '.$this->Created;
            $this->write();
        } else {
            if($this->Config()->email_admin && ! $this->EmailPrepared) {
                $from = Email::config()->admin_email;
                $to = Email::config()->admin_email;
                if($from) {
                    $subject = $this->Title;
                    $body = $this->HTMLSummary();
                    $email = new Email($from, $to, $subject, $body);
                    $this->EmailPrepared = 1;
                    $this->EmailSent = $email->send();
                    $this->write();
                }
            }
        }
    }

    protected function HTMLSummary(): string
    {
        $html = '<h1>'.$this->Title.' ('.$this->ProductCount.')</h1>';
        $html .= '<h2>Removed</h2><div>'.$this->codeToDetails((string) $this->Removed).'</div>';
        $html .= '<h2>Added</h2><div>'.$this->codeToDetails((string) $this->Added).'</div>';
        $html .= '<h2>Archived</h2><div>'. $this->codeToDetails((string) $this->Archived) .'</div>';
        $html .= '<h2>For Sale</h2><div>'. $this->codeToDetails((string) $this->ForSale) .'</div>';

        return $html;
    }

    protected function codeToDetails(string $list) : string
    {
        if(! $list) {
            return '<p>No Change</p>';
        }
        $listArray = explode(',', $list);
        $html = '';
        foreach($listArray as $code) {
            $html .= $this->codeToDetailsInner($code);
        }
        return '<ol>'.$html.'</ol>';
    }
    protected function codeToDetailsInner(string $code) : string
    {
        $obj = SecondHandProduct::get()->filter(['InternalItemID' => $code])->first();
        $html = '';
        if($obj) {
            $html .= '<a href="'.Director::absoluteURL($obj->CMSEditLink()).'">'.$obj->InternalItemID.' - '.$obj->Title.': '.$obj->Price.'</a>';
        } else {
            $html .= $code;
        }
        return '<li>'.$html.'</li>';
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canEdit($member = null)
    {
        if($this->Created && strtotime($this->Created) < (time() - 3600)) {
            return false;
        }
        return parent::canEdit($member);
    }

}
