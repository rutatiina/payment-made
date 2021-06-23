<?php

namespace Rutatiina\PaymentMade\Services;

use Rutatiina\PaymentMade\Models\PaymentMadeItem;
use Rutatiina\PaymentMade\Models\PaymentMadeItemTax;

class PaymentMadeItemService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        //print_r($data['items']); exit;

        //Save the items >> $data['items']
        foreach ($data['items'] as &$item)
        {
            $item['payment_made_id'] = $data['id'];

            $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
            unset($item['taxes']);

            $itemModel = PaymentMadeItem::create($item);

            foreach ($itemTaxes as $tax)
            {
                //save the taxes attached to the item
                $itemTax = new PaymentMadeItemTax;
                $itemTax->tenant_id = $item['tenant_id'];
                $itemTax->payment_made_id = $item['payment_made_id'];
                $itemTax->payment_made_item_id = $itemModel->id;
                $itemTax->tax_code = $tax['code'];
                $itemTax->amount = $tax['total'];
                $itemTax->taxable_amount = $tax['total']; //todo >> this is to be updated in future when taxes are propelly applied to receipts
                $itemTax->inclusive = $tax['inclusive'];
                $itemTax->exclusive = $tax['exclusive'];
                $itemTax->save();
            }
            unset($tax);
        }
        unset($item);

    }

}
