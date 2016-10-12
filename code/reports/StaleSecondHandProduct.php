<?php


class StaleSecondHandProduct extends SS_Report
{

    private static $default_days_back = 180;

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
        $name = _t(
            'EcommerceSideReport.STALE_SECOND_HAND_REPORT', 
            'Stale Second Hand Products'
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
        if(isset($params['OlderThanNDays'])) {
            $params['OlderThanNDays'] = intval($params['OlderThanNDays']);
        } else {
            $params['OlderThanNDays'] = Config::inst()->get('StaleSecondHandProduct', 'default_days_back');
        }
        return SecondHandProduct::get()->filter(
            array(
                'AllowPurchase' => 1,
                'Created:LessThan' => date('Y-m-d',strtotime('-'. $params['OlderThanNDays'] .' days'))
            )
        );
    }

    /**
     * @return array
     */
    public function columns()
    {
        return array(
            'InternalItemID' => 'ID',
            'Title' => array(
                'title' => 'Product Name',
                'link' => true,
            ),
            'Created' => 'Created Time',
            'Price' => 'Selling Price',
            'PurchasePrice' => 'Purchase Price'
        );
    }

    /**
     * @return FieldList
     */
    public function getParameterFields()
    {
        return new FieldList();
    }
    
    public function parameterFields()
    {
        $params = new FieldList();
        
        $params->push(
            NumericField::create(
                'OlderThanNDays',
                'Older than: [Number of days]',
                Config::inst()->get('StaleSecondHandProduct', 'default_days_back')
            )
        );
                 
        return $params;
    }   
}
