<?php

namespace Rutatiina\PaymentMade\Traits;

use Illuminate\Support\Facades\Auth;
use Rutatiina\Item\Models\Item;
use Rutatiina\FinancialAccounting\Models\InventoryPurchase;

trait InventoryReverse
{
    private function inventoryReverseAction()
    {
        //inventory checks
        if ($this->txn['original']['debit_account'] && $this->txn['original']['credit_account']) {

            #Check if any of the Accounts is an Inventory account
            if ($this->txn['original']['debit_account']['type'] == 'inventory' && $this->txn['original']['credit_account']['type'] == 'inventory') {

                #Check If its an Issue Or Purchase i.e. Only one of the Accounts is an Inventory Account

                if (strtolower($this->txn['original']['debit_account']['type']) == 'inventory') {

                    return 'purchase';

                } elseif (strtolower($this->txn['original']['credit_account']['type']) == 'inventory') {

                    return 'issue';

                }

            }
        }
        //*/
    }

    private function inventoryReverse()
    {
        $inventoryAction = $this->inventoryReverseAction();

        if ($inventoryAction == 'purchase') {

            $this->inventoryReversePurchase();

        } elseif ($inventoryAction == 'issue') {

            $this->inventoryReverseIssue();

        }
    }

    private function inventoryReverseTrackableItems()
    {
        //Get the details of each item from the db
        $items = [];

        foreach($this->txn['original']['items'] as $index => $item) {

            $modelItem = Item::where('id', $item['type_id'])->where('type', 'product')->first();

            if ($modelItem) {

                $row = $modelItem->toArray();

                if ($row['inventory_tracking'] == 0 || strtolower($row['type']) != 'product')  {
                    continue;
                }

                $item['units'] = (empty($item['units'])) ? 1 : $item['units'];

                $items[] = $item;

            } else {
                continue;
            }
        }
        unset($item);

        return $items;
    }

    private function inventoryReversePurchases($item)
    {
        /*
            We use credit account for `accountCode` because we only need to read purchases when inventory account is being credited / issued
        */
        $query = InventoryPurchase::where('financial_account_code', $this->txn['original']['credit'])
            ->where('item_id', $item['type_id'])
            ->where('currency', $this->txn['original']['base_currency'])
            ->where('date', '<=', $this->txn['original']['date'])
            ->where('balance', '>', 0)
            ->orderBy('date', 'ASC')
            ->get();


        if ($query->isNotEmpty()) {

            $user = auth()->user();

            $purchases = $query->toArray();

            #Evaluate Cost based on Inventory Cost method
            if ($user->tenant->inventory_valuation_method == 'FIFO') {
                foreach($purchases as $key => $purchase) {
                    $purchases[$key]['cost'] = $purchase['value'];
                }
            }

            elseif ($user->tenant->inventory_valuation_method == 'LIFO') {
                //Reverse the array since purchases are read from db in FIFO order i.e. date ASC
                $purchases = array_reverse($purchases);

                foreach($purchases as $key => $purchase) {
                    $purchases[$key]['cost'] = $purchase['value'];
                }
            }

            elseif ($user->tenant->inventory_valuation_method == 'AVCO') {

                $total_purchased_units = 0;
                $total_purchased_value = 0;

                foreach($purchases as $Key => $purchase)
                {
                    $total_purchased_units += $purchase['units'];
                    $total_purchased_value += $purchase['units'] * $purchase['balance'];
                }

                $average_cost = $total_purchased_value / $total_purchased_units;

                foreach($purchases as $key => $purchase)
                {
                    $purchases[$key]['cost'] = $average_cost;
                }
            }

            #Actual Unit Cost Method
            elseif ($user->tenant->inventory_valuation_method == 'AUCO') {

                foreach($purchases as $key => $purchase) {
                    $purchases[$key]['cost'] = $item['cost'] / $item['units'];
                }
            }

            #End of inventory valuation calculation

            return $purchases;

        } else {
            return false;
        }
    }

    private function inventoryReversePurchase()
    {
        $items = $this->inventoryReverseTrackableItems();

        if (empty($items)) {
            $this->errors[] = 'Inventory Reverse Purchase Error: Item(s) are not product or inventory tracking is not enabled.';
            return false;
        }

        foreach($items as $item) {

            $inventoryPurchase = InventoryPurchase::where('txn_id', $this->txn['original']['id'])
                ->where('item_id', $item['type_id'])
                ->where('financial_account_code', $this->txn['original']['debit'])
                ->first();

            if (is_null($inventoryPurchase)) {
                continue;
            }

            $purchase = $inventoryPurchase->toArray();

            //Make sure none of the items have been issued i.e. units == balance
            if ($purchase['units'] == $purchase['balance']) {

                InventoryPurchase::where('txn_id', $this->txn['original']['id'])->delete();

            } else {

                static::$rg_errors[] = 'Error deleting transaction: Inventory already issued.';
                return false;
            }

        }

    }

    private function inventoryReverseIssue()
    {
        $items = $this->inventoryReverseTrackableItems();

        if (empty($items)) {
            $this->errors[] = 'Inventory Reverse Issue Error: Item(s) are not product or inventory tracking is not enabled.';
            return false;
        }

        #Loop through and update the value of the details
        foreach($items as $index => $item) {

            $purchases = $this->inventoryReversePurchases($item);

            if ($purchases == false || empty($purchases) ) {

                $this->errors[] = 'Inventory Reverse Error: No purchases found.';
                return false;

            } else {

                //array_reverse for the delete to work and reverse the fifo filo, avco nethods
                $purchases = array_reverse($purchases);
                //print_r($purchases); exit;

                //Means this was an issue so we add back the inventory
                $issue  = $item['quantity'] * $item['units'];

                foreach($purchases as $purchase) {

                    if ($issue == 0 ) {
                        break; //Stop the loop if no issues are left to be revered
                    }

                    $issued = $purchase['units'] - $purchase['balance'];

                    if ($issue > $issued) {
                        $inventory_purchase = InventoryPurchase::find($purchase['id']);
                        $inventory_purchase->balance = $inventory_purchase->balance + $issued;
                        $inventory_purchase->save();
                    } else {
                        $inventory_purchase = InventoryPurchase::find($purchase['id']);
                        $inventory_purchase->balance = $inventory_purchase->balance + $issue;
                        $inventory_purchase->save();
                    }

                    $issue -= $issued;
                }

                if ($issue > 0 ) {
                    $this->errors[] = 'Error deleting transaction: Issue units are still left.';
                    return false;
                }
            }

        } //End of looping through details


        return true;

    }
}
