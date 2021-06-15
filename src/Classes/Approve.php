<?php

namespace Rutatiina\PaymentMade\Classes;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Rutatiina\FinancialAccounting\Models\Txn;

use Rutatiina\PaymentMade\Traits\Init as TxnTraitsInit;
use Rutatiina\PaymentMade\Traits\Inventory as TxnTraitsInventory;
use Rutatiina\PaymentMade\Traits\TxnItemsContactsIdsLedgers as TxnTraitsTxnItemsContactsIdsLedgers;
use Rutatiina\PaymentMade\Traits\TxnTypeBasedSpecifics as TxnTraitsTxnTypeBasedSpecifics;
use Rutatiina\PaymentMade\Traits\Validate as TxnTraitsValidate;
use Rutatiina\PaymentMade\Traits\AccountBalanceUpdate as TxnTraitsAccountBalanceUpdate;
use Rutatiina\PaymentMade\Traits\ContactBalanceUpdate as TxnTraitsContactBalanceUpdate;
use Rutatiina\PaymentMade\Traits\Approve as TxnTraitsApprove;

class Approve
{
    use TxnTraitsInit;
    use TxnTraitsInventory;
    use TxnTraitsTxnItemsContactsIdsLedgers;
    use TxnTraitsTxnTypeBasedSpecifics;
    use TxnTraitsValidate;
    use TxnTraitsAccountBalanceUpdate;
    use TxnTraitsContactBalanceUpdate;
    use TxnTraitsApprove;

    public function __construct()
    {}

    public function run($id)
    {
        $Txn = Txn::find($id);

        if ($Txn) {
            //txn has been found so continue normally
        } else {
            $this->errors[] = 'Transaction to approve not found';
            return false;
        }

        $Txn->load('ledgers');

        $txnStatus = strtolower($Txn->status);

        if ($txnStatus != 'draft') {
            $this->errors[] = $Txn->status.' transaction cannot be approved';
            return false;
        }

        $this->txn = $Txn->toArray();

        try {

            $approve = $this->approve();

            if ($approve === false) {
                DB::connection('tenant')->rollBack();
                return false;
            }

            //update the status of the txn
            $Txn->status = 'approved';
            $Txn->save();

            DB::connection('tenant')->commit();

            return true;

        } catch (\Exception $e) {

            DB::connection('tenant')->rollBack();
            //print_r($e); exit;
            if (App::environment('local')) {
                $this->errors[] = 'DB Error: Failed to approve transaction.';
                $this->errors[] = 'File: '. $e->getFile();
                $this->errors[] = 'Line: '. $e->getLine();
                $this->errors[] = 'Message: ' . $e->getMessage();
            } else {
                $this->errors[] = 'Fatal Internal Error: Failed to approve transaction. Please contact Admin';
            }

            return false;
        }

    }

}
