<?php

namespace Rutatiina\PaymentMade\Traits;

use Rutatiina\FinancialAccounting\Models\AccountBalance;

trait AccountBalanceUpdate
{
    private function accountBalanceUpdate($reverse = false)
    {
        //get the current contact
        //$contact  = self::$contact; // static::contact();
        //$currency   = $contact->currency;

        $ledgers = $this->txn['ledgers'];
        //print_r($ledgers); exit;

        if ($reverse) {

            $ledgers = $this->txn['original']['ledgers'];

            foreach($ledgers as &$ledger) {
                $ledger['total'] = $ledger['total'] * -1;
            }
            unset($ledger);
        }

        //Log::info(':: accountBalanceUpdate ------------------------------------------------');

        foreach($ledgers as $ledger) {

            if (empty($ledger['financial_account_code'])) {
                return true;
            }

            //Defaults
            $debit  = ($ledger['effect'] == 'debit') ? $ledger['total'] : 0;
            $credit = ($ledger['effect'] == 'credit') ? $ledger['total'] : 0;

            $currencies = [];
            $currencies[$ledger['base_currency']]   = $ledger['base_currency'];
            $currencies[$ledger['quote_currency']]  = $ledger['quote_currency'];

            foreach ($currencies as $currency) {

                //for multi-currency, apply the exchange rate
                if ($currency == $ledger['base_currency']) {
                    //Do nothing because the values are in the base currency
                } else {
                    $debit = $debit * $ledger['exchange_rate'];
                    $credit = $credit * $ledger['exchange_rate'];
                }

                //1. find the last record
                $accountBalance = AccountBalance::whereDate('date', '<=', $ledger['date'])
                    ->where('currency', $currency)
                    ->where('financial_account_code', $ledger['financial_account_code'])
                    ->orderBy('date', 'desc')
                    ->first();

                //var_dump($accountBalance); exit;
                //Log::info('>>Last account balance entry for account id::'.$ledger['financial_account_code'].' in '.$currency.' date: '.$ledger['date'].': '.$ledger['effect'].' '.$ledger['total']);
                //Log::info($ledger);
                //Log::info($accountBalance);

                switch ($accountBalance) {
                    case null:

                        //create a new balance record
                        $account_balance = new AccountBalance;
                        $account_balance->tenant_id = $ledger['tenant_id'];
                        $account_balance->date = $ledger['date'];
                        $account_balance->financial_account_code = $ledger['financial_account_code'];
                        $account_balance->currency = $currency;
                        $account_balance->debit = 0;
                        $account_balance->credit = 0;
                        $account_balance->save();

                        break;

                    default:

                        //create a new row with the last balances
                        if ($ledger['date'] == $accountBalance->date) {
                            //do nothing because the records for this dates balances already exists
                        } else {
                            $account_balance = new AccountBalance;
                            $account_balance->tenant_id = $ledger['tenant_id'];
                            $account_balance->date = $ledger['date'];
                            $account_balance->financial_account_code = $ledger['financial_account_code'];
                            $account_balance->currency = $currency;
                            $account_balance->debit = $accountBalance->debit;
                            $account_balance->credit = $accountBalance->credit;
                            $account_balance->save();
                        }

                        break;

                }

                if ($debit) {

                    $increment = AccountBalance::whereDate('date', '>=', $ledger['date'])
                        ->where('currency', $currency)
                        ->where('financial_account_code', $ledger['financial_account_code'])
                        ->increment('debit', $debit);
                    //Log::info('debit increment: '.$debit);
                    //Log::info($increment);

                }

                if ($credit) {

                    $increment = AccountBalance::whereDate('date', '>=', $ledger['date'])
                        ->where('currency', $currency)
                        ->where('financial_account_code', $ledger['financial_account_code'])
                        ->increment('credit', $credit);
                    //Log::info('credit increment: '.$credit);
                    //Log::info($increment);

                }

            }

        }

        return true;

    }
}
