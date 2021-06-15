<?php

namespace Rutatiina\PaymentMade\Traits;

use Illuminate\Support\Facades\Auth;
use Rutatiina\Item\Models\Item;
use Rutatiina\FinancialAccounting\Models\InventoryPurchase;

trait Inventory
{
    private function inventoryAction()
    {
        //inventory checks
        //txn must have debit OR credit
        if ($this->txn['accounts']['debit'] || $this->txn['accounts']['credit']) {

            #Check if any of the Accounts is an Inventory account
            if ($this->txn['accounts']['debit']['type'] == 'inventory' || $this->txn['accounts']['credit']['type'] == 'inventory') {

                #Check If its an Issue Or Purchase i.e. Only one of the Accounts is an Inventory Account

                if (strtolower($this->txn['accounts']['debit']['type']) == 'inventory') {

                    return 'purchase';

                } elseif (strtolower($this->txn['accounts']['credit']['type']) == 'inventory') {

                    return 'issue';

                }

            }
        }
        //*/
    }

    private function inventory()
    {
        $inventoryAction = $this->inventoryAction();

        if ($inventoryAction == 'purchase') {

            $this->inventoryPurchase();

        } elseif ($inventoryAction == 'issue') {

            $this->inventoryIssue();

        }
    }

    private function inventoryTrackableItems()
    {
        //Get the details of each item from the db
        $items = [];

        foreach($this->txn['items'] as $index => $item) {

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

    private function inventoryPurchases($item)
    {
        /*
            We use credit account for `accountCode` because we only need to read purchases when inventory account is being credited / issued
        */
        $query = InventoryPurchase::where('financial_account_code', $this->txn['credit_financial_account_code'])
            ->where('item_id', $item['type_id'])
            ->where('currency', $this->txn['base_currency'])
            ->where('date', '<=', $this->txn['date'])
            ->where('balance', '>', 0)
            ->orderBy('date', 'ASC')
            ->get();


        if ($query->isNotEmpty()) {

            $user = auth()->user();

            $purchases = $query->toArray();
            //print_r($purchases); exit;

            #Evaluate Cost based on Inventory Cost method
            if (strtoupper($user->tenant->inventory_valuation_method) == 'FIFO') {
                foreach($purchases as $key => $purchase) {
                    $purchases[$key]['cost'] = $purchase['value_per_unit'];
                }
            }

            elseif (strtoupper($user->tenant->inventory_valuation_method) == 'LIFO') {
                //Reverse the array since purchases are read from db in FIFO order i.e. date ASC
                $purchases = array_reverse($purchases);

                foreach($purchases as $key => $purchase) {
                    $purchases[$key]['cost'] = $purchase['value_per_unit'];
                }
            }

            elseif (strtoupper($user->tenant->inventory_valuation_method) == 'AVCO') {

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
            elseif (strtoupper($user->tenant->inventory_valuation_method) == 'AUCO') {

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

    private function inventoryPurchase()
    {
        $items = $this->inventoryTrackableItems();

        if (empty($items)) {
            $this->errors[] = 'Inventory Purchase Error: Item(s) are not product or inventory tracking is not enabled.';
            return false;
        }

        foreach($items as $item) {

            //$item_id = (empty($item['parent_id']))? $item['id'] : $item['parent_id']; //Set at item selection

            $item['units'] = (empty($item['units'])) ? 1 : $item['units'];
            $units = $item['units'] * $item['quantity'];

            //NOTE:: Units & balances fields is only set on purchases
            // Units fields NEVER edited/updated
            // balances fields is whats updated cz it shows the units available
            $InventoryPurchase = new InventoryPurchase;
            $InventoryPurchase->tenant_id = Auth::user()->tenant->id;
            $InventoryPurchase->txn_id = $this->txn['id'];
            $InventoryPurchase->date = $this->txn['date'];
            $InventoryPurchase->financial_account_code = $this->txn['debit_financial_account_code'];
            $InventoryPurchase->item_id = $item['type_id'];
            $InventoryPurchase->batch = $item['batch'];
            $InventoryPurchase->expiry = $item['expiry'];
            $InventoryPurchase->value_per_unit = ($item['rate'] / $item['units']);
            $InventoryPurchase->currency = $this->txn['base_currency'];
            $InventoryPurchase->units = $units;
            $InventoryPurchase->balance = $units;
            $InventoryPurchase->save();

        }

    }

    private function inventoryIssue()
    {
        $items = $this->inventoryTrackableItems();

        if (empty($items)) {
            $this->errors[] = 'Inventory Issue Error: Item(s) are not product or inventory tracking is not enabled.';
            return false;
        }

        #Loop through and update the value of the details
        $txn_issued_details = [];

        foreach($items as $index => $item) {

            $purchases = $this->inventoryPurchases($item);
            //print_r($purchases);

            if ($purchases == false || empty($purchases) ) {

                $this->errors[] = 'Inventory Error: No purchases found.';
                return false;

            } else {

                $issued = $item['units'] * $item['quantity'];

                #Find the value based on the purchases
                foreach($purchases as $purchase) {

                    if ($issued <= 0) {
                        break;
                    }

                    if ($issued > $purchase['balance']) {
                        $issued_units = $purchase['balance'];
                        $issued = $issued - $purchase['balance'];
                    } else {
                        $issued_units = $issued;
                        $issued = 0;
                    }

                    //update the inventory balance
                    //Negative issued_units because its an Issue i.e. inventory going out
                    //On issues, only balances fields is edited
                    //NOTE:: units field is NOT / NEVER updated
                    $inventory_purchase = InventoryPurchase::find($purchase['id']);
                    $inventory_purchase->decrement('balance', $issued_units);


                    //Note $purchase['cost'] is the cost of @unit
                    //Do not change the bellow code reasons (Purchases of diff cost, diff catch, diff expiry)
                    $txn_issued_details[] = [
                        'tenant_id'     => Auth::user()->tenant->id,
                        'type'          => $item['type'],
                        'type_id'       => $item['type_id'],
                        'name'          => $item['name'],
                        'description'   => $item['description'],
                        'quantity'      => $issued_units,
                        'rate'          => $purchase['value_per_unit'],
                        'total'         => $purchase['value_per_unit'] * $issued_units,
                        'units'         => 1,
                        'batch'         => $purchase['batch'],
                        'expiry'        => $purchase['expiry'],
                    ];
                }

                #If total issued is more than total purchased, record a negative
                if ($issued > 0) {
                    $this->errors[] = 'Inventory Error: Unit(s) for issue are more than purchase(s).';
                    return false;
                }
            }

        } //End of looping through details


        if (empty($txn_issued_details)) {
            $this->errors[] = 'Inventory Error: Cannot issue items that are not available.';
            return false;
        }

        //Update the txn details with the updated detials with calculated inventory value
        //$txn['data'], $txn['items'], $txn[number], $txn[ledgers], $txn[recurring]

        //Get the total
        $total = 0;
        foreach($txn_issued_details as $value) {
            $total = $total + ($value['quantity'] * $value['rate']);
        }

        $this->txn['items'] = $txn_issued_details;
        $this->txn['total'] = $total;
        $this->txn['taxable_amount'] = $total;

        return true;

    }

    private function inventoryAvailability()
    {
        $inventoryAction = $this->inventoryAction();

        if ($inventoryAction == 'issue') {

            $items = $this->inventoryTrackableItems();

            foreach($items as $index => $item) {

                $purchases = $this->inventoryPurchases($item);

                if ($purchases == false || empty($purchases)) {

                    $this->errors[] = 'Inventory Error: No purchases found.';
                    return false;

                }
            }

        } else {
            return true;
        }
    }
}
