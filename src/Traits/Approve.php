<?php

namespace Rutatiina\PaymentMade\Traits;

trait Approve
{
    private function approve()
    {
        $status = strtolower($this->txn['status']);

        //do not continue if txn status is draft
        if ($status == 'draft') return true;

        //inventory checks and inventory balance update if needed
        $this->inventory();

        //Update the account balances
        $this->accountBalanceUpdate();

        //Update the contact balances
        $this->contactBalanceUpdate();

        //Save the recurring details
        //--todo--

        return true;
    }

}
