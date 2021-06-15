<?php

namespace Rutatiina\PaymentMade\Traits;

use Rutatiina\FinancialAccounting\Models\Entree;

trait Entree
{
    public function __construct()
    {}

    public function entree($idOrSlug)
    {
        if (is_numeric($idOrSlug)) {
            $txnEntree = Entree::find($idOrSlug);
        } else {
            $txnEntree = Entree::where('slug', $idOrSlug)->first();
        }

        if ($txnEntree) {
            //do nothing
        } else {
            return false;
        }

        $txnEntree->load('config', 'config.txn_type');

        return $txnEntree->toArray();

    }

}
