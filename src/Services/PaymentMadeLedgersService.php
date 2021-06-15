<?php

namespace Rutatiina\PaymentMade\Services;

use Rutatiina\PaymentMade\Models\PaymentMadeLedger;

class PaymentMadeLedgersService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function store($data)
    {
        foreach ($data['ledgers'] as &$ledger)
        {
            $ledger['receipt_id'] = $data['id'];
            PaymentMadeLedger::create($ledger);
        }
        unset($ledger);

    }

}
