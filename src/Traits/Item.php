<?php

namespace Rutatiina\PaymentMade\Traits;

trait Item
{
    public function __construct()
    {}

    public function itemCreate()
    {
        return [
            'selectedTaxes' => [], #required
            'selectedItem' => json_decode('{}'), #required
            'displayTotal' => 0,
            'name' => '',
            'description' => '',
            'rate' => 0,
            'quantity' => 1,
            'total' => 0,
            'taxes' => [],

            'type' => '',
            'type_id' => '',
            'contact_id' => '',
            'tax_id' => '',
            'units' => '',
            'batch' => '',
            'expiry' => ''
        ];

    }

}
