<?php

namespace Rutatiina\PaymentMade\Traits;

use Illuminate\Support\Facades\Auth;

trait TxnTypeBasedSpecifics
{
    private function txnTypeBasedSpecifics()
    {
        switch ($this->settings->document_type) {

            case 'invoice':

                //Handle discount on invoices
                if (isset($this->txn['discount']) && $this->txn['discount'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => 'txn_type',
                        'type_id'       => 4, //Discount Voucher
                        'name'          => 'Discount',
                        'description'   => 'Discount Allowed',
                        'quantity'      => 1,
                        'rate'          => $this->txn['discount'],
                        'total'         => $this->txn['discount'],
                        'units'         => null,
                        'batch'         => null,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];

                    //reduce the invoice total
                    $this->txn['total'] -= $this->txn['discount'];

                    //Reduce the Amount Receviable
                    $this->txn['ledgers']['debit']['total'] -= $this->txn['discount'];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => 31, //Discount Allowed
                        'effect'        => 'debit',
                        'total'         => $this->txn['discount'],
                        'contact_id'    => $this->txn['debit_contact_id']
                    ];
                }

                //Taxes on invoices: Generate the ledgers
                foreach($this->txn['taxes'] as $key => $tax) {

                    //Reduce the value of the sales / revenue [credit]
                    $this->txn['ledgers']['credit']['total'] -= $tax['total'];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => $tax['on_sale_financial_account_code'],
                        'effect'        => $tax['on_sale_effect'],
                        'total'         => $tax['total'],
                        'contact_id'    => null
                    ];
                }

                break;

            case 'bill' :

                //Handle discount on Bills
                if (isset($this->txn['discount']) && $this->txn['discount'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => 'txn_type',
                        'type_id'       => 4, //Discount Voucher
                        'name'          => 'discount',
                        'description'   => 'Discount Received',
                        'quantity'      => 1,
                        'rate'          => $this->txn['discount'],
                        'total'         => $this->txn['discount'],
                        'units'         => null,
                        'batch'         => NULL,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];

                    //Reduce the Amount Payable
                    $this->txn['ledgers']['credit']['total'] -= $this->txn['discount'];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'        => 52, //Discount Received
                        'effect'            => 'credit',
                        'total'             => $this->txn['discount'],
                        'contact_id'   		=> $this->txn['credit_contact_id']
                    ];
                }

                //Taxes on Bills >> Generate the ledgers
                foreach($this->txn['taxes'] as $key => $tax) {
                    //Reduce the value of the Expense / cost [debit]
                    $this->txn['ledgers']['debit']['total'] -= $tax['total'];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'        => $tax['on_bill_financial_account_code'],
                        'effect'            => $tax['on_bill_effect'],
                        'total'             => $tax['total'],
                        'contact_id'   		=> null
                    ];
                }

                break;

            case 'receipt' :


                if (isset($data['forex_gain']) && is_numeric($this->txn['forex_gain']) && $this->txn['forex_gain'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => null,
                        'type_id'       => null,
                        'name'          => 'Forex gain',
                        'description'   => 'Forex gain',
                        'quantity'      => 1,
                        'rate'          => $this->txn['forex_gain'],
                        'total'         => $this->txn['forex_gain'],
                        'units'         => null,
                        'batch'         => null,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => 90, //Foreign Exchange Gain
                        'effect'        => 'credit',
                        'total'         => $this->txn['forex_gain'],
                        'contact_id'    => $this->txn['debit_contact_id'],
                        'base_currency' =>  Auth::user()->tenant->base_currency,
                        'quote_currency' =>  Auth::user()->tenant->base_currency,
                        'exchange_rate' =>  1,
                    ];

                }

                if (isset($data['forex_loss']) && is_numeric($this->txn['forex_loss']) && $this->txn['forex_loss'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => null,
                        'type_id'       => null,
                        'name'          => 'Forex loss',
                        'description'   => 'Forex loss',
                        'quantity'      => 1,
                        'rate'          => $this->txn['forex_loss'],
                        'total'         => $this->txn['forex_loss'],
                        'units'         => null,
                        'batch'         => null,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => 91, //Foreign Exchange loss
                        'effect'        => 'debit',
                        'total'         => $this->txn['forex_loss'],
                        'contact_id'    => $this->txn['debit_contact_id'],
                        'base_currency' =>  Auth::user()->tenant->base_currency,
                        'quote_currency' =>  Auth::user()->tenant->base_currency,
                        'exchange_rate' =>  1,
                    ];

                }

                //Taxes on receipts: Generate the ledgers
                foreach($this->txn['taxes'] as $key => $tax) {

                    //Reduce the value of the sales / revenue [credit]
                    $this->txn['ledgers']['credit']['total'] -= $tax['total'];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => $tax['on_sale_financial_account_code'],
                        'effect'        => $tax['on_sale_effect'],
                        'total'         => $tax['total'],
                        'contact_id'    => null
                    ];
                }

                break;

            case 'payment' :

                if (isset($data['forex_gain']) && is_numeric($this->txn['forex_gain']) && $this->txn['forex_gain'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => null,
                        'type_id'       => null,
                        'name'          => 'Forex gain',
                        'description'   => 'Forex gain',
                        'quantity'      => 1,
                        'rate'          => $this->txn['forex_gain'],
                        'total'         => $this->txn['forex_gain'],
                        'units'         => null,
                        'batch'         => null,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => 90, //Foreign Exchange Gain
                        'effect'        => 'credit',
                        'total'         => $this->txn['forex_gain'],
                        'contact_id'    => $this->txn['debit_contact_id'],
                        'base_currency' =>  Auth::user()->tenant->base_currency,
                        'quote_currency' =>  Auth::user()->tenant->base_currency,
                        'exchange_rate' =>  1,
                    ];

                }

                if (isset($data['forex_loss']) && is_numeric($this->txn['forex_loss']) && $this->txn['forex_loss'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => null,
                        'type_id'       => null,
                        'name'          => 'Forex loss',
                        'description'   => 'Forex loss',
                        'quantity'      => 1,
                        'rate'          => $this->txn['forex_loss'],
                        'total'         => $this->txn['forex_loss'],
                        'units'         => null,
                        'batch'         => null,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];

                    $this->txn['ledgers'][] = [
                        'financial_account_code'    => 91, //Foreign Exchange loss
                        'effect'        => 'debit',
                        'total'         => $this->txn['forex_loss'],
                        'contact_id'    => $this->txn['debit_contact_id'],
                        'base_currency' =>  Auth::user()->tenant->base_currency,
                        'quote_currency' =>  Auth::user()->tenant->base_currency,
                        'exchange_rate' =>  1,
                    ];

                }

                break;

            default:

                //Display discount on txn
                if (isset($this->txn['discount']) && $this->txn['discount'] > 0) {

                    $this->txn['items'][] = [
                        'type'          => 'txn_type',
                        'type_id'       => 4, //Discount Voucher
                        'name'          => 'Discount',
                        'description'   => null,
                        'quantity'      => 1,
                        'rate'          => $this->txn['discount'],
                        'total'         => $this->txn['discount'],
                        'batch'         => NULL,
                        'expiry'        => null,
                        'taxes'         => null,
                        'contact_id'    => null,
                    ];
                }

                break;
        }
    }
}
