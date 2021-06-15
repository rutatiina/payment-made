<?php

namespace Rutatiina\PaymentMade\Classes;

use Illuminate\Support\Facades\Auth;
use Rutatiina\Item\Models\Item;
use Rutatiina\FinancialAccounting\Models\InventoryPurchase;

class PreValidation
{
    public function run($data)
    {
        $data['items'] = (empty($data['items'])) ? [] : $data['items'];

        $data['forex_gain'] = 0;
        $data['forex_loss'] = 0;

        //Add the missing parameters to the items array
        foreach ($data['items'] as $txn_id => &$item)
        {
            $maxPayable = $item['rate'] + $item['amount_withheld'];

            //If the user has entered more amount to receipt (Only applies to receipts)
            if ($maxPayable > $item['max_receipt_amount'])
            {
                return [
                    'status' => false,
                    'messages' => ['Amount paid is more than total Bill balance.']
                ];
            }

            if (empty($item['rate']))
            {
                unset($data['items'][$txn_id]);
                continue;
            }

            //$item['type_id']        = $txn_id; //removed this so that receipts work properly in vue
            //$item['contact_id']     = $item['txn_contact_id']; //removed this so that receipts work properly in vue
            $item['name'] = 'Payment on Bill no. ' . $item['txn_number'];
            $item['description'] = null;
            $item['quantity'] = 1;
            //$item['total']          = $item['rate']; //removed this so that receipts work properly in vue

            //Calculate the forex gain or loss
            if ($data['exchange_rate'] < $item['txn_exchange_rate'])
            {
                //This is a gain (because you pay more)
                $data['forex_gain'] += $item['rate'] * ($data['txn_exchange_rate'] - $item['exchange_rate']);
            }

            if ($data['exchange_rate'] > $item['txn_exchange_rate'])
            { //This is a loss (because you pay less)
                $data['forex_gain'] += $item['rate'] * ($data['exchange_rate'] - $item['txn_exchange_rate']);
            }

            unset($item['txn']); #vue
            unset($item['paidInFull']); #vue

        }
        unset($item);

        //Auth the total received
        $total = 0;
        foreach ($data['items'] as $item)
        {
            $total += $item['rate'] + $item['amount_withheld'];
        }

        if ($total != $data['total'])
        {
            return [
                'status' => false,
                'messages' => ['Amount paid not equal sum allocated to bills.']
            ];
        }

        $data['total'] = $total;
        $data['taxable_amount'] = $total;

        return [
            'status' => true,
            'data' => $data
        ];
    }
}
