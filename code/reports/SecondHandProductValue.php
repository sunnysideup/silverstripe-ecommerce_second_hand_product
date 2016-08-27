<?php


class SecondHandProductValue extends SS_Report
{
    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'SiteTree';

    /**
     * @return string
     */
    public function title()
    {
        $values = $this->sourceRecords()->column('PurchasePrice');
        $sum = array_sum($values);
        $object = Currency::create('Sum');
        $object->setValue($sum);
        return _t('EcommerceSideReport.TOTAL_STOCK_VALUE', 'Total Stock Value').
        ': '.$object->Nice().'';
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
        return SecondHandProduct::get()->filter(array('AllowPurchase' => 1));
    }

    /**
     * @return array
     */
    public function columns()
    {
        return array(
            'Title' => array(
                'title' => 'FullName',
                'link' => true,
            ),
        );
    }

    /**
     * @return FieldList
     */
    public function getParameterFields()
    {
        return new FieldList();
    }
}
