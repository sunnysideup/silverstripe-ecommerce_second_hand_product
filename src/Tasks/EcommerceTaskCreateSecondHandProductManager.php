<?php

namespace Sunnysideup\EcommerceSecondHandProduct\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Sunnysideup\Ecommerce\Config\EcommerceConfig;
use Sunnysideup\EcommerceSecondHandProduct\SecondHandProduct;
use Sunnysideup\PermissionProvider\Api\PermissionProviderFactory;

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
        $email = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_email');
        if (! $email) {
            $email = 'secondhandproducts@' . $_SERVER['HTTP_HOST'];
        }
        $firstName = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_first_name');
        if (! $firstName) {
            $firstName = 'Second Hand';
        }
        $surname = EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_user_surname');
        if (! $surname) {
            $surname = 'Sales';
        }
        DB::alteration_message('================================<br />creating second hand products admin group and second hand products sales manager', 'created');

        return PermissionProviderFactory::inst()
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setSurname($surname)
            ->setGroupName(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_name'))
            ->setCode(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_group_code'))
            ->setPermissionCode(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_code'))
            ->setRoleTitle(EcommerceConfig::get(SecondHandProduct::class, 'second_hand_admin_permission_title'))
            ->setPermissionArray(
                [
                    'SITETREE_VIEW_ALL',
                    'CMS_ACCESS_SecondHandProductAdmin',
                ]
            )
            ->CreateGroupAndMember();
    }
}
