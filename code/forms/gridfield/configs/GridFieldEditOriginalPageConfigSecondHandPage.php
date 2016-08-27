<?php
/**
 * @author nicolaas <github@sunnysideup.co.nz>
 */
class GridFieldEditOriginalPageConfigSecondHandPage extends GridFieldConfig_RecordEditor
{
    /**
     * @param int $itemsPerPage - How many items per page should show up
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);
        $this
            ->removeComponentsByType('GridFieldDeleteAction')
            ->removeComponentsByType('GridFieldAddNewButton')
            ->removeComponentsByType('GridFieldAddNewButtonOriginalPage')
            ->addComponent(new GridFieldAddNewButtonOriginalPageSecondHandProduct());
    }
}
