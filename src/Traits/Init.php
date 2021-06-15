<?php

namespace Rutatiina\PaymentMade\Traits;

trait Init
{
    private $txn = [];
    private $settings = null;

    public $txnEntreeSlug = '';
    public $errors = [];
    public $txnInsertData = [];

    //this function is to be deleted
    private function txnEntreeId($name) {

        $name = strtolower($name);

        $entree_names = [
            'estimate'              => 10,
            'retainer_invoice'      => 9,
            'invoice'               => 1,
            'recurring_invoice'     => 12,
            'credit_note'           => 62,
            'purchase_order'        => 53,

            'bill'                  => 13,
            'recurring_bill'        => 14,
            'debit_note'            => 57, //Debit Note
            'sales_order'           => 11,
            'invoice_receipt'       => 3,
            'receipt'               => 3,
            'payment'               => 4,
            'expense'               => 5,
            'recurring_expense'     => 15,

            'delivery_note'         => 59,
            'goods_received_note'   => 54,
            'goods_issued_note'     => 60, //Goods issued note
            'goods_returned_note'   => 61, //Goods returned note#

            'journal'               => 0,

            'invoice_payment_by_wallet'     => 16,
            'wallet_top_up_cash'     => 17,
            'wallet_top_up_yopayments'     => 18,
            'wallet_top_up_paypal'     => 19,
            'wallet_top_up_rave'     => 20,
        ];

        if (array_key_exists($name, $entree_names)) {
            return $entree_names[$name];
        } else {
            return 0;
        }
    }
}
