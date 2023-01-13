<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\Vardump\ArrayToTable;

class SecondHandForSaleList extends DataObject
{
    protected static $archive_count = 0;
    private static $keep_list_for_days = 21;

    private static $last_edited_remove_in_days = 30;

    private static $max_items_to_archive = 300;

    private static $days_ago_to_be_really_old_product = 360;

    private static $table_name = 'SecondHandForSaleList';

    private static $send_email_to_admin = true;

    private static $delete_old_products = true;

    private static $delete_old_lists = true;

    private static $autoArchiveList = [];

    private static $db = [
        'Title' => 'Varchar(100)',
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
        'CalculationsCompleted' => 'Boolean',
    ];

    private static $summary_fields = [
        'Created.Nice' => 'Created',
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
                ReadonlyField::create(
                    'Created',
                    'Created'
                ),
            ]
        );
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

    public function archiveStaleProducts()
    {
        $this->deleteOldListsAndArchiveRemovedProducts();
        if ($this->Config()->delete_old_products) {
            $this->autoArchiveNonActiveProducts();
            $this->autoArchiveReallyOldProducts();
        }
        $this->AutoArchived = implode(',', $this->autoArchiveList);
        $this->write();
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function canEdit($member = null)
    {
        if ($this->Created && strtotime((string) $this->Created) < (time() - 3600)) {
            return false;
        }

        return parent::canEdit($member);
    }

    public function sendEmail()
    {
        if ($this->Config()->send_email_to_admin && ! $this->EmailPrepared) {
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

    protected function deleteOldListsAndArchiveRemovedProducts()
    {
        $daysAgoForList = (int) $this->Config()->keep_list_for_days;
        $deleteOldLists = $this->Config()->delete_old_lists;
        if ($daysAgoForList > 0 && $deleteOldLists) {
            $timeFilter = [
                'Created:LessThan' => date('Y-m-d', strtotime('-' . $daysAgoForList . ' days')) . ' 00:00:00',
            ];
            $olderOnes = SecondHandForSaleList::get()
                ->filter($timeFilter)
                ->limit(5)
                ->exclude(['ID' => $this->ID])
                ->sort(['ID' => 'ASC'])
            ;
            foreach ($olderOnes as $oldList) {
                $removeList = explode(',', (string) $oldList->Removed);
                if ($this->Config()->delete_old_products) {
                    // clearning the for sale - to save space!
                    $objects = SecondHandProduct::get()->filter(['AllowPurchase' => false, 'InternalItemID' => $removeList]);
                    $this->autoArchiveProducts($objects);
                }
                $oldList->delete();
            }
        }
    }

    protected function autoArchiveNonActiveProducts()
    {
        $daysAgo = $this->Config()->last_edited_remove_in_days;
        if ($daysAgo) {
            $timeFilterLastEdited = [
                'LastEdited:LessThan' => date('Y-m-d', strtotime('-' . $daysAgo . ' days')) . ' 00:00:00',
            ];
            $objects = SecondHandProduct::get()->filter(['AllowPurchase' => false] + $timeFilterLastEdited)->limit($this->Config()->max_items_to_archive);
            $this->autoArchiveProducts($objects);
        }
    }

    protected function autoArchiveReallyOldProducts()
    {
        $daysAgo = $this->Config()->days_ago_to_be_really_old_product;
        if ($daysAgo) {
            $tsOneYearAgo = strtotime('-' . $daysAgo . ' days');
            $timeFilter = [
                'Created:LessThan' => date('Y-m-d', $tsOneYearAgo) . ' 00:00:00',
            ];
            $filter = ['AllowPurchase' => 0] + $timeFilter;
            $objects = SecondHandProduct::get()->filter($filter);
            $this->autoArchiveProducts($objects);
        }
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (! $this->CalculationsCompleted) {
            $this->CalculationsCompleted = true;
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
            $this->DoubleUps = implode(',', $this->workOutDoubleUps($currentArray, $notCurrentArray));

            // compare to previous
            $prev = SecondHandForSaleList::get()
                ->exclude(['ID' => (int) $this->ID])
                ->sort(['ID' => 'DESC'])->first();
            if ($prev) {
                $archivedSinceLastTime = $this->getLatestArchived($prev);
                $prevArray = explode(',', (string) $prev->ForSale);
                $this->Archived = implode(',', $archivedSinceLastTime);
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
        if (! $this->Title && $this->Created) {
            $this->Title = 'Second Hand Products For Sale on ' . $this->Created;
        }
    }

    protected function workOutDoubleUps(array $currentArray, array $notCurrentArray): array
    {
        $archived = SecondHandArchive::get()->column('InternalItemID');
        $allNotCurrentArray = array_merge($notCurrentArray, $archived);

        return array_intersect($currentArray, $allNotCurrentArray);
    }

    protected function getLatestArchived(SecondHandForSaleList $prev): array
    {
        $lastOneArchivedCode = $prev->LastItemArchived;
        if ($lastOneArchivedCode) {
            $lastOneArchivedObject = SecondHandArchive::get()
                ->filter(['InternalItemID' => $lastOneArchivedCode])
                ->sort(['Created' => 'DESC'])
                ->first()
            ;
            if ($lastOneArchivedObject) {
                return SecondHandArchive::get()
                    ->sort(['ID' => 'DESC'])
                    ->filter(['ID:GreaterThan' => $lastOneArchivedObject->ID, 'AutoArchived' => false])
                    ->column('InternalItemID')
                ;
            }
        }

        return [];
    }

    protected function HTMLSummary(): string
    {
        $html = '<h1>' . $this->Title . ' (' . $this->ProductCount . ')</h1>';
        $html .= '<h2>Double-Ups</h2><div>' . $this->codeToDetails((string) $this->DoubleUps, 'No Double-Ups', false) . '</div>';
        $html .= '<h2>Removed</h2><div>' . $this->codeToDetails((string) $this->Removed, 'Nothing removed', false) . '</div>';
        $html .= '<h2>Added</h2><div>' . $this->codeToDetails((string) $this->Added, 'Nothing added', false) . '</div>';
        $html .= '<h2>Auto Archived</h2><div>' . $this->codeToDetails((string) $this->AutoArchived, 'Nothing automatically archived', false) . '</div>';
        $html .= '<h2>Manually Archived</h2><div>' . $this->codeToDetails((string) $this->Archived, 'Nothing manually archived', false) . '</div>';

        return $html . ('<h2>For Sale</h2><div>' . $this->codeToDetails((string) $this->ForSale, 'No Items For Sale') . '</div>');
    }

    protected function codeToDetails(string $list, $noListPhrase = 'No Change', ?bool $showHistory = false): string
    {
        if (! $list) {
            return '<p>' . $noListPhrase . '</p>';
        }

        $listArray = explode(',', (string) $list);
        $html = '';
        $list = [];
        foreach ($listArray as $code) {
            $list[] = $this->codeToDetailsInner($code);
        }
        // important, sort alpabetically.
        sort($list);

        return '<ol>' . implode($list) . '</ol>';
    }

    protected function codeToDetailsInner(string $code, ?bool $showHistory = false): string
    {
        $obj = SecondHandProduct::get()->filter(['InternalItemID' => $code])->first();
        if (! $obj) {
            $obj = SecondHandArchive::get()->filter(['InternalItemID' => $code])->first();
        }

        $html = '';
        if ($obj) {
            $firstCreated = $obj->Created;
            $lastEdited = $obj->Created;
            $html .=
                $obj->Title .
                ' (<a href="' . Director::absoluteURL($obj->CMSEditLink()) . '">' . $obj->InternalItemID . '</a>)' .
                ' $' . $obj->Price .
                ' (' . $obj->i18n_singular_name() . ')';
        } else {
            $html .= $code;
        }
        $historyTable = '';
        if ($obj && $showHistory) {
            $historyTable = ArrayToTable::convert($obj->getHistoryData($code));
        }

        return '<li>' . $html . '<br />' . $historyTable . '</li>';
    }

    private function autoArchiveProducts(DataList $objects)
    {
        $maxItemsToArchive = (int) $this->Config()->max_items_to_archive;

        foreach ($objects as $obj) {
            if (0 === $maxItemsToArchive || self::$archive_count < $maxItemsToArchive) {
                ++self::$archive_count;
                $this->autoArchiveList[$obj->InternalItemID] = $obj->InternalItemID;
                $archivedRecord = null;

                try {
                    $archivedRecord = SecondHandProductActions::archive($obj->ID);
                } catch (\Exception $exception) {
                    DB::alteration_message('Caught exception, could not delete item ' . $exception->getMessage(), 'deleted');
                }
                if ($archivedRecord && $archivedRecord instanceof SecondHandArchive) {
                    $archivedRecord->AutoArchived = true;
                    $archivedRecord->write();
                } else {
                    user_error('Could not archive ' . $obj->InternalItemID);
                }
            }
        }
    }
}
