<?php

namespace Rutatiina\PaymentMade\Traits;

trait TxnItemsContactsIdsLedgers
{
    private function txnItemsContactsIdsLedgers()
    {
        //only split ledger entries if the items of the txn are for different contacts
        $splitLedgerEntries = (count($this->txn['items_contacts_ids']) > 1) ? true : false;

        //this is to handle a case where one person pays off someone else's invoice, the payment has to be reflected on the other persons statement
        $ledgerEffect = ($this->settings->document_type == 'receipt') ? 'credit' : 'debit';

        if ($splitLedgerEntries) {
            $this->txn['ledgers'][$ledgerEffect] = [];
            $txnItemsCollection = collect($this->txn['items']);

            foreach ($this->txn['items_contacts_ids'] as $itemsContactsId) {

                //get the total of all the items attributed to that contact
                $txnItemsCollectionFilteredByContactId = $txnItemsCollection->where('contact_id', $itemsContactsId);
                $ledgerTotal = $txnItemsCollectionFilteredByContactId->sum('total');

                $this->txn['ledgers'][$itemsContactsId] = [
                    'financial_account_code' => $this->txn[$ledgerEffect],
                    'effect' => $ledgerEffect,
                    'total' => $ledgerTotal,
                    'contact_id' => $itemsContactsId
                ];

            }
        }
    }
}
