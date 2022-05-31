<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Model;

use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataObject;
use Sunnysideup\EcommerceSecondHandProduct\Api\SecondHandProductActions;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

use Sunnysideup\EcommerceSecondHandProduct\Model\SecondHandArchive;

use Sunnysideup\Vardump\ArrayToTable;

class SecondHandForSaleList extends DataObject
{
    private static $keep_for_days = 21;

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
        'CalculationsCompleted' => 'Boolean',
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

    public function archiveStaleProducts()
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
                    // clearning the for sale - to save space!
                    $item->ForSale = '';
                    $item->write();
                    foreach ($removeList as $code) {
                        if($code) {
                            $obj = SecondHandProduct::get()->filter(['AllowPurchase' => false, 'InternalItemID' => $code])->first();
                            if ($obj) {
                                $archived[$obj->InternalItemID] = $obj->InternalItemID;
                                $this->autoArchiveProduct($obj);
                            }
                        }
                    }
                }

                $timeFilterLastEdited = [
                    'LastEdited:LessThan' => date('Y-m-d', strtotime('-' . $daysAgo . ' days')) . ' 00:00:00',
                ];
                $objects = SecondHandProduct::get()->filter($timeFilterLastEdited + ['AllowPurchase' => false])->limit(50);
                foreach ($objects as $obj) {
                    $archived[$obj->InternalItemID] = $obj->InternalItemID;
                    $this->autoArchiveProduct($obj);
                }
            }

            $this->AutoArchived = implode(',', $archived);
        }
    }

    protected function autoArchiveProduct(SecondHandProduct $obj)
    {
        $archivedRecord = SecondHandProductActions::archive($obj->ID);
        if($archivedRecord && $archivedRecord instanceof SecondHandArchive) {
            $archivedRecord->AutoArchive = true;
            $archivedRecord->write();
        } else {
            user_error('Could not archive '.$obj->InternalItemID);
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
                $this->archiveStaleProducts();
            }
        }

        if (! $this->LastItemArchived) {
            $last = SecondHandArchive::get()->sort(['ID' => 'DESC'])->first();
            if ($last) {
                $this->LastItemArchived = $last->InternalItemID;
            }
        }
        if (! $this->Title) {
            $this->Title = 'Second Hand Products For Sale on ' . $this->Created;
        }

    }

    public function sendEmail()
    {
        if ($this->Config()->email_admin && ! $this->EmailPrepared) {
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
        $archived = SecondHandArchive::get()->column('InternalItemID');
        $allNotCurrentArray = array_merge($notCurrentArray, $archived);

        return array_intersect($currentArray, $allNotCurrentArray);
    }

    protected function getLatestArchived(SecondHandForSaleList $prev): array
    {
        $lastOneArchivedCode = $prev->LastItemArchived;
        if ($lastOneArchivedCode) {
            $lastOneArchivedObject = SecondHandArchive::get()->filter(['InternalItemID' => $lastOneArchivedCode])->first();
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

        $listArray = explode(',', $list);
        $html = '';
        foreach ($listArray as $code) {
            $html .= $this->codeToDetailsInner($code);
        }

        return '<ol>' . $html . '</ol>';
    }

    protected function codeToDetailsInner(string $code, ?bool $showHistory = false): string
    {
        $obj = SecondHandProduct::get()->filter(['InternalItemID' => $code])->first();
        $firstCreated = $obj->Created;
        $lastEdited = $obj->Created;
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
        $historyTable = '';
        if($obj && $showHistory) {
            $historyTable = ArrayToTable::convert($obj->getHistoryData($code));
        }

        return '<li>' . $html . '<br />'.$historyTable.'</li>';
    }
}
