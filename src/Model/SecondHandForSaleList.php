<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

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
        'AutoArchived' => 'Text',
        'Archived' => 'Text',
        'DoubleUps' => 'Text',
        'LastItemArchived' => 'Varchar(50)',
        'EmailPrepared' => 'Boolean',
        'EmailSent' => 'Boolean',
        'Notes' => 'Text',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'ProductCount' => 'Count',
        'Added' => 'Added',
        'Removed' => 'Removed',
        'AutoArchived' => 'Auto Archived',
        'Archived' => 'Manually Archived',
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
                ),
            ]
        );
        $fields->addFieldsToTab(
            'Root.ProductsForSale',
            [
                ReadonlyField::create(
                    'ForSale',
                    'For Sale'
                ),
            ]
        );
        foreach (array_keys($this->Config()->get('db')) as $fieldName) {
            if ('Notes' !== $fieldName) {
                $fields->replaceField($fieldName, $fields->dataFieldByName($fieldName)->performReadonlyTransformation());
            }
        }

        return $fields;
    }

    public function deleteOldData()
    {
        if ($this->Config()->delete_old) {
            $archived = [];
            $daysAgo = $this->Config()->keep_for_days;
            if ($daysAgo) {
                $timeFilter = [
                    'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgo . ' days')) . ' 00:00:00',
                ];
                $olderOnes = SecondHandForSaleList::get()->filter($timeFilter)->limit(30);
                foreach ($olderOnes as $item) {
                    $removeList = explode(',', $item->Removed);
                    $item->ForSale = '';
                    $item->write();
                    foreach ($removeList as $code) {
                        $obj = SecondHandProduct::get()->filter(['AllowPurchase' => 1, 'InternalItemID' => $code])->first();
                        if ($obj) {
                            $archived[$obj->InternalItemID] = $obj->InternalItemID;
                            SecondHandProductActions::archive($obj->ID);
                        }
                    }
                }

                $timeFilterLastEdited = [
                    'LastEdited:LessThan' => date('Y-m-d', strtotime('-' . $daysAgo . ' days')) . ' 00:00:00',
                ];
                $objects = SecondHandProduct::get()->filter($timeFilterLastEdited + ['AllowPurchase' => 0])->limit(50);
                foreach ($objects as $obj) {
                    $archived[$obj->InternalItemID] = $obj->InternalItemID;
                    SecondHandProductActions::archive($obj->ID);
                }
            }

            $this->AutoArchived = implode(',', $archived);
            $this->write();
        }
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canEdit($member = null)
    {
        if ($this->Created && strtotime($this->Created) < (time() - 3600)) {
            return false;
        }

        return parent::canEdit($member);
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (! $this->ForSale) {
            $currentArray = SecondHandProduct::get()
                ->filter(['AllowPurchase' => 1])
                ->sort('Title ASC')
                ->column('InternalItemID')
            ;
            $notCurrentArray = SecondHandProduct::get()
                ->filter(['AllowPurchase' => 0])
                ->sort('Title ASC')
                ->column('InternalItemID')
            ;
            $this->ProductCount = count($currentArray);
            $this->ForSale = implode(',', $currentArray);
            $prev = SecondHandForSaleList::get()
                ->exclude(['ID' => (int) $this->ID])
                ->sort(['ID' => 'DESC'])->first();
            $this->DoubleUps = implode(',', $this->workOutDoubleUps($currentArray, $notCurrentArray));
            if ($prev) {
                $archivedSinceLastTime = $this->getLatestArchived($prev);
                $this->Archived = implode(',', $archivedSinceLastTime);
                $prevArray = explode(',', (string) $prev->ForSale);
                $this->Added = implode(',', array_diff($currentArray, $prevArray));
                $this->Removed = implode(',', array_diff($prevArray, array_merge($currentArray, $archivedSinceLastTime)));
                // this has to be done last.
                if ([] !== $archivedSinceLastTime) {
                    $this->LastItemArchived = array_shift($archivedSinceLastTime);
                }
            }
        }

        if (! $this->LastItemArchived) {
            $last = SecondHandArchive::get()->sort(['ID' => 'DESC'])->first();
            if ($last) {
                $this->LastItemArchived = $last->InternalItemID;
            }
        }
    }

    protected function onAfterWrite()
    {
        parent::onAfterWrite();
        if (! $this->Title) {
            $this->Title = 'Second Hand Products For Sale on ' . $this->Created;
            $this->write();
        } elseif ($this->Config()->email_admin && ! $this->EmailPrepared) {
            $from = Email::config()->admin_email;
            $to = Email::config()->admin_email;
            if ($from) {
                $subject = $this->Title;
                $body = $this->HTMLSummary();
                $email = new Email($from, $to, $subject, $body);
                $this->EmailPrepared = 1;
                $this->EmailSent = $email->send();
                $this->write();
            }
        }
    }

    protected function workOutDoubleUps(array $currentArray, array $notCurrentArray): array
    {
        $allArray = array_merge($currentArray, $notCurrentArray);
        $archived = SecondHandArchive::get()->column('InternalItemID');

        return array_intersect($allArray, $archived);
    }

    protected function getLatestArchived(SecondHandForSaleList $prev): array
    {
        $lastOneArchivedCode = $prev->LastItemArchived;
        if ($lastOneArchivedCode) {
            $lastOneArchivedObject = SecondHandArchive::get()->filter(['InternalItemID' => $lastOneArchivedCode])->first();
            if ($lastOneArchivedObject) {
                return SecondHandArchive::get()
                    ->sort(['ID' => 'DESC'])
                    ->filter(['ID:GreaterThan' => $lastOneArchivedObject->ID])
                    ->column('InternalItemID')
                ;
            }
        }

        return [];
    }

    protected function HTMLSummary(): string
    {
        $html = '<h1>' . $this->Title . ' (' . $this->ProductCount . ')</h1>';
        $html .= '<h2>Double-Ups</h2><div>' . $this->codeToDetails((string) $this->DoubleUps, 'No Double-Ups') . '</div>';
        $html .= '<h2>Removed</h2><div>' . $this->codeToDetails((string) $this->Removed) . '</div>';
        $html .= '<h2>Added</h2><div>' . $this->codeToDetails((string) $this->Added) . '</div>';
        $html .= '<h2>Auto Archived</h2><div>' . $this->codeToDetails((string) $this->AutoArchived) . '</div>';
        $html .= '<h2>Manually Archived</h2><div>' . $this->codeToDetails((string) $this->Archived) . '</div>';

        return $html . ('<h2>For Sale</h2><div>' . $this->codeToDetails((string) $this->ForSale, 'No Items For Sale') . '</div>');
    }

    protected function codeToDetails(string $list, $noListPhrase = 'No Change'): string
    {
        if (! $list) {
            return '<p>' . $noListPhrase . '</p>';
        }

        $listArray = explode(',', $list);
        $html = '';
        foreach ($listArray as $code) {
            $html .= $this->codeToDetailsInner($code);
        }

        return '<ol>' . $html . '</ol>';
    }

    protected function codeToDetailsInner(string $code): string
    {
        $obj = SecondHandProduct::get()->filter(['InternalItemID' => $code])->first();
        if (! $obj) {
            $obj = SecondHandArchive::get()->filter(['InternalItemID' => $code])->first();
        }

        $html = '';
        if ($obj) {
            $html .= '
                <a href="' . Director::absoluteURL($obj->CMSEditLink()) . '">' . $obj->InternalItemID . ' - ' . $obj->Title . ': ' . $obj->Price . '
                </a> ' .
                '(' . $obj->i18n_singular_name() . ')';
        } else {
            $html .= $code;
        }

        return '<li>' . $html . '</li>';
    }
}
