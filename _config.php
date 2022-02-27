<?php

use SilverStripe\Admin\CMSMenu;
use Sunnysideup\EcommerceSecondHandProduct\Cms\CMSPageAddControllerSecondHandProducts;

if (isset($_SERVER['REQUEST_URI']) && 0 === strpos($_SERVER['REQUEST_URI'], '/admin/')) {
    CMSMenu::remove_menu_class(CMSPageAddControllerSecondHandProducts::class);
}
