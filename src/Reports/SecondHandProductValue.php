<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Reports;

use Currency;

use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Reports\Report;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;

class SecondHandProductValue extends Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = SecondHandProduct::class;

    /**
     * @return string
     */
    public function title()
    {
        $values = $this->sourceRecords()->column('PurchasePrice');
        $sum = array_sum($values);
        $object = Currency::create('Sum');
        $object->setValue($sum);
        $name = _t(
            'EcommerceSideReport.SECOND_HAND_REPORT_TOTAL_STOCK_VALUE',
            'Second Hand Products, total stock value'
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
     * @return DataList
     */
    public function sourceRecords($params = null)
    {
        return SecondHandProduct::get()->filter(
            [
                'AllowPurchase' => 1,
                'SellingOnBehalf' => 0,
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

    public function getReportField()
    {
        $field = parent::getReportField();
        $config = $field->getConfig();
        $exportButton = $config->getComponentByType(GridFieldExportButton::class);
        $exportButton->setExportColumns($field->getColumns());

        return $field;
    }
}
