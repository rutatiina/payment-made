<?php

namespace Rutatiina\PaymentMade\Services;

use Illuminate\Support\Facades\Validator;
use Rutatiina\Contact\Models\Contact;
use Rutatiina\PaymentMade\Models\PaymentMadeSetting;

class PaymentMadeValidateService
{
    public static $errors = [];

    public static function run($requestInstance)
    {
        //$request = request(); //used for the flash when validation fails
        $user = auth()->user();

        //if no ivoice is tagged create the items parameter
        if (!$requestInstance->items)
        {
            $requestInstance->request->add(['exchange_rate' => 1]);
            $requestInstance->request->add([
                'items' => [
                    [
                        //'invoice' => 0,        
                        'txn_contact_id' => $requestInstance->contact_id,
                        'txn_number' => 0,
                        'max_receipt_amount' => $requestInstance->total,
                        //'txn_exchange_rate' => $txn->exchange_rate,
            
                        'bill_id' => 0,
                        'contact_id' => $requestInstance->contact_id,
                        'description' => $requestInstance->description,
                        'amount' => $requestInstance->total,
                        'taxable_amount' => $requestInstance->total,
                        'taxes' => [],
                    ]
                ]
            ]);
        }


        // >> data validation >>------------------------------------------------------------

        //validate the data
        $customMessages = [
            'credit_financial_account_code.required' => "The paid through field is required.",
            'items.*.taxes.*.code.required' => "Tax code is required.",
            'items.*.taxes.*.total.required' => "Tax total is required.",
            //'items.*.taxes.*.exclusive.required' => "Tax exclusive amount is required.",
        ];

        $rules = [
            'contact_id' => 'required|numeric',
            'date' => 'required|date',
            'payment_mode' => 'required',
            'credit_financial_account_code' => 'required',
            'base_currency' => 'required',
            'contact_notes' => 'string|nullable',

            'items' => 'required|array',
            'items.*.description' => 'required',
            'items.*.amount' => 'required|numeric',
            'items.*.taxable_amount' => 'numeric',

            'items.*.taxes' => 'array|nullable',
            'items.*.taxes.*.code' => 'required',
            'items.*.taxes.*.total' => 'required|numeric',
            //'items.*.taxes.*.exclusive' => 'required|numeric',
        ];

        $validator = Validator::make($requestInstance->all(), $rules, $customMessages);

        if ($validator->fails())
        {
            self::$errors = $validator->errors()->all();
            return false;
        }

        // << data validation <<------------------------------------------------------------

        $settings = PaymentMadeSetting::has('financial_account_to_debit')
            ->with(['financial_account_to_debit'])
            ->firstOrFail();
        //Log::info($this->settings);


        $contact = Contact::findOrFail($requestInstance->contact_id);


        $data['id'] = $requestInstance->input('id', null); //for updating the id will always be posted
        $data['user_id'] = $user->id;
        $data['tenant_id'] = $user->tenant->id;
        $data['created_by'] = $user->name;
        $data['app'] = 'web';
        $data['document_name'] = $settings->document_name;
        $data['number'] = $requestInstance->input('number');
        $data['date'] = $requestInstance->input('date');
        $data['debit_financial_account_code'] = $settings->financial_account_to_debit->code;
        $data['credit_financial_account_code'] = $requestInstance->credit_financial_account_code;
        $data['contact_id'] = $requestInstance->contact_id;
        $data['contact_name'] = $contact->name;
        $data['contact_address'] = trim($contact->shipping_address_street1 . ' ' . $contact->shipping_address_street2);
        $data['reference'] = $requestInstance->input('reference', null);
        $data['base_currency'] =  $requestInstance->input('base_currency');
        $data['quote_currency'] =  $requestInstance->input('quote_currency', $data['base_currency']);
        $data['exchange_rate'] = $requestInstance->input('exchange_rate', 1);
        $data['branch_id'] = $requestInstance->input('branch_id', null);
        $data['store_id'] = $requestInstance->input('store_id', null);
        $data['notes'] = $requestInstance->input('notes', null);
        $data['status'] = $requestInstance->input('status', null);
        $data['balances_where_updated'] = $requestInstance->input('balances_where_updated', null);
        $data['payment_mode'] = $requestInstance->input('payment_mode', null);

        //set the transaction total to zero
        $txnTotal = 0;
        $taxableAmount = 0;

        //Formulate the DB ready items array
        $data['items'] = [];
        foreach ($requestInstance->items as $key => $item)
        {
            $itemTaxes = $requestInstance->input('items.'.$key.'.taxes', []);

            $item['taxable_amount'] = $item['amount']; //todo >> this is to be updated in future when taxes are propelly applied to receipts

            $txnTotal           += $item['amount'];
            $taxableAmount      += ($item['taxable_amount']);
            $itemTaxableAmount   = $item['taxable_amount']; //calculate the item taxable amount

            foreach ($itemTaxes as $itemTax)
            {
                $txnTotal           += $itemTax['exclusive'];
                $taxableAmount      -= $itemTax['inclusive'];
                $itemTaxableAmount  -= $itemTax['inclusive']; //calculate the item taxable amount more by removing the inclusive amount
            }

            $data['items'][] = [
                'tenant_id' => $data['tenant_id'],
                'created_by' => $data['created_by'],
                'contact_id' => $item['contact_id'],
                'bill_id' => $item['bill_id'],
                'description' => $item['description'],
                'amount' => $item['amount'],
                'taxable_amount' => $itemTaxableAmount,
                'taxes' => $itemTaxes,
            ];
        }

        $data['taxable_amount'] = $taxableAmount;
        $data['total'] = $txnTotal;

        //print_r($data); exit;

        return $data;

    }

}
