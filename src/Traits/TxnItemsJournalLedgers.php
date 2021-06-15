<?php

namespace Rutatiina\PaymentMade\Traits;

trait TxnItemsJournalLedgers
{
    private function txnItemsJournalLedgers()
    {
        //only split ledger entries if the items of the txn are for different contacts
        $generateLedgerEntries = ($this->txnEntreeSlug == 'journal') ? true : false;

        if ($generateLedgerEntries) {
            $this->txn['ledgers'] = [];

            foreach ($this->txnInsertData['items'] as $item) {

                $this->txn['ledgers'][] = [
                    'financial_account_code' => $item['type_id'],
                    'effect' => $item['effect'],
                    'total' => $item['rate'], //$item['total'],
                    'contact_id' => $item['contact_id']
                ];

            }
        }
    }
}
