<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use BuildTask;
use Injector;
use db;
use EcommerceConfig;



/**
 * create the e-commerce specific Member Groups.
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class EcommerceTaskCreateSecondHandProductManager extends BuildTask
{
    protected $title = 'Create e-commerce Second Hand Product Manager';

    protected $description = 'Create the member groups and members for second hard products';

    public function run($request)
    {
        $permissionProviderFactory = Injector::inst()->get('PermissionProviderFactory');
        db::alteration_message('========================== <br />creating second hand products sales manager', 'created');
        $email = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_email');
        if (! $email) {
            $email = 'secondhandproducts@'.$_SERVER['HTTP_HOST'];
        }
        $firstName = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_first_name');
        if (!$firstName) {
            $firstName = 'Second Hand';
        }
        $surname = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_user_surname');
        if (!$surname) {
            $surname = 'Sales';
        }

        $member = $permissionProviderFactory->CreateDefaultMember(
            $email,
            $firstName,
            $surname
        );
        db::alteration_message('================================<br />creating shop admin group ', 'created');

        $permissionProviderFactory->CreateGroup(
            $code = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_code'),
            $name = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_group_name'),
            $parentGroup = null,
            $permissionCode = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_permission_code'),
            $roleTitle = EcommerceConfig::get('SecondHandProduct', 'second_hand_admin_role_title'),
            $otherPermissionCodes = array(),
            $member
        );
    }
}

