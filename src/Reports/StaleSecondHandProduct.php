<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\Reports\Report;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class StaleSecondHandProduct extends Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = SecondHandProduct::class;

    private static $default_days_back = 180;

    /**
     * @return string
     */
    public function title()
    {
        $values = $this->sourceRecords()->column('PurchasePrice');
        $sum = array_sum($values);
        $object = DBCurrency::create('Sum');
        $object->setValue($sum);

        $name = _t(
            'EcommerceSideReport.STALE_SECOND_HAND_REPORT',
            'Second Hand Products - Stale'
        );

        return $name . ': ' . $object->Nice();
    }

    /**
     * not sure if this is used in SS3.
     *
     * @return string
     */
    public function group()
    {
        return _t('EcommerceSideReport.ECOMMERCEGROUP', 'Ecommerce');
    }

    /**
     * @return int - for sorting reports
     */
    public function sort()
    {
        return 8000;
    }

    /**
     * working out the items.
     *
     * @param null|mixed $params
     *
     * @return DataList
     */
    public function sourceRecords($params = null, $sort = null, $limit = null)
    {
        if (isset($params['OlderThanNDays'])) {
            $params['OlderThanNDays'] = (int) $params['OlderThanNDays'];
        }

        if (empty($params['OlderThanNDays'])) {
            $params['OlderThanNDays'] = Config::inst()->get(StaleSecondHandProduct::class, 'default_days_back');
        }

        if (! isset($params['Title'])) {
            $params['Title'] = '';
        }

        $ts = strtotime('-' . $params['OlderThanNDays'] . ' days');

        return SecondHandProduct::get()->filter(
            [
                'Title:PartialMatch' => $params['Title'],
                'AllowPurchase' => $params['AllowPurchase'] ?? 1,
                'SellingOnBehalf' => 0,
                'Created:LessThan' => date('Y-m-d', $ts) . ' 23:59:59',
            ]
        );
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            'InternalItemID' => 'ID',
            'Title' => [
                'title' => 'Product Name',
                'link' => true,
            ],
            'Created' => 'Created Time',
            'Price' => 'Selling Price',
            'PurchasePrice' => 'Purchase Price',
        ];
    }

    public function parameterFields()
    {
        $params = new FieldList();

        $params->push(
            NumericField::create(
                'OlderThanNDays',
                'Older than: [Number of days]',
                Config::inst()->get(StaleSecondHandProduct::class, 'default_days_back')
            )
        );

        $params->push(
            TextField::create(
                'Title',
                'Product Name'
            )
        );

        $params->push(
            CheckboxField::create(
                'AllowPurchase',
                'For sale?'
            )
        );

        return $params;
    }

    public function getReportField()
    {
        $field = parent::getReportField();
        $config = $field->getConfig();
        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $exportButton->setExportColumns($field->getColumns());

        return $field;
    }
}
