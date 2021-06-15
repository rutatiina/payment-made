<?php

namespace Rutatiina\PaymentMade\Traits;

use Rutatiina\FinancialAccounting\Models\ContactBalance;

trait ContactBalanceUpdate
{
    private function contactBalanceUpdate($reverse = false) {

        $ledgers = $this->txn['ledgers'];

        if ($reverse) {

            $ledgers = $this->txn['original']['ledgers'];

            foreach($ledgers as &$ledger) {
                $ledger['total'] = $ledger['total'] * -1;
            }
            unset($ledger);
        }

        foreach($ledgers as $ledger) {

            if (empty($ledger['financial_account_code'])) {
                return true;
            }

            if (empty($ledger['contact_id'])) {
                return true;
            }

            //Defaults
            $debit = ($ledger['effect'] == 'debit') ? $ledger['total'] : 0;
            $credit = ($ledger['effect'] == 'credit') ? $ledger['total'] : 0;

            $currencies = [];
            $currencies[$ledger['base_currency']]   = $ledger['base_currency'];
            $currencies[$ledger['quote_currency']]  = $ledger['quote_currency'];

            foreach ($currencies as $currency) {

                if ($currency == $ledger['base_currency']) {
                    //Do nothing because the values are in the base currency
                } else {
                    $debit = $debit * $ledger['exchange_rate'];
                    $credit = $credit * $ledger['exchange_rate'];
                }

                //1. find the last record
                $contactBalance = ContactBalance::where('date', '<=', $ledger['date'])
                    //->where('tenant_id', $ledger['tenant_id']) //TenantIdScope
                    ->where('currency', $currency)
                    ->where('financial_account_code', $ledger['financial_account_code'])
                    ->where('contact_id', $ledger['contact_id'])
                    ->orderBy('date', 'DESC')
                    ->first();

                //var_dump($contactBalance->num_rows()); exit;

                switch ($contactBalance) {
                    case null:

                        //create a new balance record
                        $contactBalanceInsert = new ContactBalance;
                        $contactBalanceInsert->tenant_id = $ledger['tenant_id'];
                        $contactBalanceInsert->contact_id = $ledger['contact_id'];
                        $contactBalanceInsert->date = $ledger['date'];
                        $contactBalanceInsert->financial_account_code = $ledger['financial_account_code'];
                        $contactBalanceInsert->currency = $currency;
                        $contactBalanceInsert->debit = 0;
                        $contactBalanceInsert->credit = 0;
                        $contactBalanceInsert->save();

                        break;

                    default:

                        //create a new row with the last balances
                        if ($ledger['date'] == $contactBalance->date) {
                            //do nothing because the records for this dates balances already exists
                        } else {
                            $contactBalanceInsert = new ContactBalance;
                            $contactBalanceInsert->tenant_id = $ledger['tenant_id'];
                            $contactBalanceInsert->contact_id = $ledger['contact_id'];
                            $contactBalanceInsert->date = $ledger['date'];
                            $contactBalanceInsert->financial_account_code = $ledger['financial_account_code'];
                            $contactBalanceInsert->currency = $currency;
                            $contactBalanceInsert->debit = $contactBalance->debit;
                            $contactBalanceInsert->credit = $contactBalance->credit;
                            $contactBalanceInsert->save();
                        }

                        break;
                }

                if ($debit) {

                    $increment = ContactBalance::where('date', '>=', $ledger['date'])
                        ->where('currency', $currency)
                        ->where('financial_account_code', $ledger['financial_account_code'])
                        ->where('contact_id', $ledger['contact_id'])
                        ->increment('debit', $debit);

                } elseif ($credit) {

                    $increment = ContactBalance::where('date', '>=', $ledger['date'])
                        ->where('currency', $currency)
                        ->where('financial_account_code', $ledger['financial_account_code'])
                        ->where('contact_id', $ledger['contact_id'])
                        ->increment('credit', $credit);

                }

            }

        }

        return true;

    }
}
