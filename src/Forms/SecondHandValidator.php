<?php


namespace Sunnysideup\EcommerceSecondHandProduct\Forms;

use Override;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class SecondHandValidator extends RequiredFieldsValidator
{
    #[Override]
    public function php($data)
    {
        if (isset($data['SellingOnBehalf']) && (int) $data['SellingOnBehalf'] === 99) {
            $this->validationError(
                'SellingOnBehalf',
                'Please indicate if you are selling on behalf of a customer or not.',
                'required'
            );
            return false;
        }

        return parent::php($data);
    }
}
