# Upgrade to Silverstripe 6

This document outlines the key changes required to upgrade to version 6.x of this module, compatible with Silverstripe CMS 6.

## ⚠️ BREAKING CHANGES

### Requirements

- **PHP 8.3+** is now required.
- **Silverstripe CMS ^6.0** is now required.
- **sunnysideup/ecommerce ^33.0** is now required.

### Class & API Updates

- The `PageTypes()` method in `CMSPageAddControllerSecondHandProducts` has been renamed to `RecordTypes()`.
- The `SiteTreeHints()` method in `CMSPageAddControllerSecondHandProducts` has been renamed to `TreeHints()`.
- In `SecondHandProduct`, the `onBeforeDelete()` method is now `protected`.
- The `SecondHandValidator` class now extends `SilverStripe\Forms\Validation\RequiredFieldsValidator` instead of the deprecated `SilverStripe\Forms\RequiredFields`.
- All `BuildTask` classes have been refactored to use the `Symfony\Component\Console` interfaces (`execute`, `InputInterface`, `PolyOutput`). `run()` method is no longer used. This affects:
    - `EcommerceTaskSecondCheckSoldItems`
    - `EcommerceTaskSecondHandDeleteOldImages`
    - `EcommerceTaskSecondHandPublishAll`
    - `EcommerceTaskSecondHandRemoveOldies`
    - `EcommerceTaskSecondHandSoldCodes`
- The method signature for `delete_file` in `EcommerceTaskSecondHandDeleteOldImages` now requires a `PolyOutput` object: `public static function delete_file($file, PolyOutput $output)`.
- The `$icon` static property in `SecondHandProduct` and `SecondHandProductGroup` has been renamed to `$cms_icon`.
- The `$description` static property in `SecondHandProduct` and `SecondHandProductGroup` has been renamed to `$class_description`.
- The `CMSEditLink()` method has been replaced with `getCMSEditLink()` in `RecentlySoldRestoreAction`, `SecondHandArchive` and `SecondHandProduct`. Ensure all calls are updated.

## API Changes & New Features

- All relevant methods now include the `Override` attribute for better static analysis and to indicate they are overriding parent methods.
- PHP native types (`string`, `int`, `array`, `bool`) have been added to class properties and constants where previously missing.
- Many classes now use strict typing with `declare(strict_types=1);`.
- `DataObject::get_one()` calls have been updated to the new `MyClass::get()->...->first()` syntax.
- Build tasks now support command-line options. For example, `ecommerce:secondhand:checksolditems` now accepts `--codes` and `--markassold` options.

## 🚨 CRITICAL REVIEW REQUIRED / RISKY

- **Incomplete `doAdd` method**: The `doAdd` method in `CMSPageAddControllerSecondHandProducts` is completely commented out.
    - **This will break the "Add New" functionality for second-hand products in the CMS. You will need to review and re-implement this method based on Silverstripe 6 conventions.**
- **`@TODO` notes**: The codebase now contains `@TODO (SS6 upgrade)` comments. You must search for and address these items as they indicate incomplete or potentially problematic code.
    - `EcommerceTaskSecondHandDeleteOldImages.php`: A `user_error` is present if a file cannot be deleted.
    - `EcommerceTaskSecondHandRemoveOldies.php`: A `user_error` is present if a product cannot be archived.
- **Removed `doCancel` method**: The `doCancel` method in `CMSPageAddControllerSecondHandProducts` has been removed. You may need to re-implement it if custom cancel logic is required.

## Deprecations and Removals

- Unnecessary `use` statements for classes like `SilverStripe\View\ArrayData` and `SilverStripe\ORM\ArrayList` have been removed in favor of their new namespaces.
- The `Page` class is no longer explicitly imported in `SecondHandProduct`.
- The `run()` method in all build tasks is obsolete and has been replaced by `execute()`.
