<?php

namespace Rutatiina\PaymentMade\Models;

use Illuminate\Database\Eloquent\Model;
use Rutatiina\Tenant\Scopes\TenantIdScope;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMadeItem extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logName = 'TxnItem';
    protected static $logFillable = true;
    protected static $logAttributes = ['*'];
    protected static $logAttributesToIgnore = ['updated_at'];
    protected static $logOnlyDirty = true;

    protected $connection = 'tenant';

    protected $table = 'rg_payments_made_items';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new TenantIdScope);
    }

    public function getTaxesAttribute($value)
    {
        $_array_ = json_decode($value);
        if (is_array($_array_)) {
            return $_array_;
        } else {
            return [];
        }
    }

    public function payments_made()
    {
        return $this->belongsTo('Rutatiina\PaymentMade\Models\PaymentMade', 'payment_made_id');
    }

    public function bill()
    {
        return $this->hasOne('Rutatiina\Bill\Models\Bill', 'id', 'bill_id');
    }

    public function taxes()
    {
        return $this->hasMany('Rutatiina\PaymentMade\Models\PaymentMadeItemTax', 'payment_made_item_id', 'id');
    }

}
