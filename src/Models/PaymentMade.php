<?php

namespace Rutatiina\PaymentMade\Models;

use Rutatiina\Bill\Models\Bill;
use Illuminate\Database\Eloquent\Model;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMade extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logName = 'Txn';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_payments_made';

    protected $primaryKey = 'id';

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'number_string',
        'total_in_words',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);

        self::deleted(function($txn) { // before delete() method call this
             $txn->items()->each(function($row) {
                
                //revert the total_paid
                if (isset($row['bill_id']))
                {
                    Bill::where('id', $row['bill_id'])->decrement('total_paid', $row['amount']);
                }

                $row->delete();
             });
             $txn->comments()->each(function($row) {
                $row->delete();
             });
             $txn->ledgers()->each(function($row) {
                $row->delete();
             });
        });

        self::restored(function($txn) { // before delete() method call this
             $txn->items()->each(function($row) {
                
                //revert the total_paid
                if (isset($row['bill_id']))
                {
                    Bill::where('id', $row['bill_id'])->increment('total_paid', $row['amount']);
                }

                $row->restore();
             });
             $txn->comments()->each(function($row) {
                $row->restore();
             });
             $txn->ledgers()->each(function($row) {
                $row->restore();
             });
        });

    }

    public function rgGetAttributes()
    {
        $attributes = [];
        $describeTable =  \DB::connection('tenant')->select('describe ' . $this->getTable());

        foreach ($describeTable  as $row) {

            if (in_array($row->Field, ['id', 'created_at', 'updated_at', 'deleted_at', 'tenant_id', 'user_id'])) continue;

            if (in_array($row->Field, ['currencies', 'taxes'])) {
                $attributes[$row->Field] = [];
                continue;
            }

            if ($row->Default == '[]') {
                $attributes[$row->Field] = [];
            } else {
                $attributes[$row->Field] = ''; //$row->Default; //null affects laravel validation
            }
        }

        //add the relationships
        $attributes['type'] = [];
        $attributes['debit_account'] = [];
        $attributes['credit_account'] = [];
        $attributes['items'] = [];
        $attributes['ledgers'] = [];
        $attributes['comments'] = [];
        $attributes['contact'] = [];

        return $attributes;
    }

    public function getContactAddressArrayAttribute()
    {
        return preg_split("/\r\n|\n|\r/", $this->contact_address);
    }

    public function getNumberStringAttribute()
    {
        return $this->number_prefix.(str_pad(($this->number), $this->number_length, "0", STR_PAD_LEFT)).$this->number_postfix;
    }

    public function getTotalInWordsAttribute()
    {
        $f = new \NumberFormatter( locale_get_default(), \NumberFormatter::SPELLOUT );
        return ucfirst($f->format($this->total));
    }

    public function debit_financial_account()
    {
        return $this->hasOne('Rutatiina\FinancialAccounting\Models\Account', 'code', 'debit_financial_account_code');
    }

    public function credit_financial_account()
    {
        return $this->hasOne('Rutatiina\FinancialAccounting\Models\Account', 'code', 'credit_financial_account_code');
    }

    public function items()
    {
        return $this->hasMany('Rutatiina\PaymentMade\Models\PaymentMadeItem', 'payment_made_id')->orderBy('id', 'asc');
    }

    public function ledgers()
    {
        return $this->hasMany('Rutatiina\PaymentMade\Models\PaymentMadeLedger', 'payment_made_id')->orderBy('id', 'asc');
    }

    public function comments()
    {
        return $this->hasMany('Rutatiina\PaymentMade\Models\PaymentMadeComment', 'payment_made_id')->latest();
    }

    public function contact()
    {
        return $this->hasOne('Rutatiina\Contact\Models\Contact', 'id', 'contact_id');
    }

    public function item_taxes()
    {
        return $this->hasMany('Rutatiina\PaymentMade\Models\PaymentMadeItemTax', 'payment_made_id', 'id');
    }

    public function getTaxesAttribute()
    {
        $grouped = [];
        $this->item_taxes->load('tax'); //the values of the tax are used by the display of the document on the from end

        foreach($this->item_taxes as $item_tax)
        {
            if (isset($grouped[$item_tax->tax_code]))
            {
                $grouped[$item_tax->tax_code]['amount'] += $item_tax['amount'];
                $grouped[$item_tax->tax_code]['inclusive'] += $item_tax['inclusive'];
                $grouped[$item_tax->tax_code]['exclusive'] += $item_tax['exclusive'];
            }
            else
            {
                $grouped[$item_tax->tax_code] = $item_tax;
            }
        }
        return $grouped;
    }

}
