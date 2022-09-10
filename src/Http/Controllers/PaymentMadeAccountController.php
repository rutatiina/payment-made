<?php

namespace Rutatiina\PaymentMade\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Rutatiina\Item\Traits\ItemsVueSearchSelect;
use Rutatiina\FinancialAccounting\Models\Account;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\PaymentReceived\Models\PaymentReceivedSetting;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;

class PaymentMadeAccountController extends Controller
{
    use FinancialAccountingTrait;
    use ItemsVueSearchSelect;

    public function __construct()
    {
        //$this->middleware('permission:estimates.view');
        //$this->middleware('permission:estimates.create', ['only' => ['create','store']]);
        //$this->middleware('permission:estimates.update', ['only' => ['edit','update']]);
        //$this->middleware('permission:estimates.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $query = Account::setCurrency(Auth::user()->tenant->base_currency)->query();
        $query->with('financial_account_category');
        $query->where('payment', 1);
        $query->orderBy('name', 'asc');

        if ($request->search)
        {
            $request->request->remove('page');

            $query->where(function($q) use ($request) {
                $columns = (new Account)->getSearchableColumns();
                foreach($columns as $column)
                {
                    $q->orWhere($column, 'like', '%'.Str::replace(' ', '%', $request->search).'%');
                }
            });
        }

        $AccountPaginate = $query->paginate(20);


        $financialAccounts = Account::select(['code', 'name', 'type'])
        //->whereIn('type', ['expense', 'equity']) //'liability', was remove because Account payables is a liability
        ->orderBy('name', 'asc')
        ->limit(100)
        ->get()
        ->each->setAppends([])
        ->groupBy('type');

        return [
            'tableData' => $AccountPaginate,
            'financialAccounts' => $financialAccounts
        ];
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //print_r($request->all()); exit;

        //validate data posted
        $validator = Validator::make($request->all(), [
            'account' => ['required'],
        ]);

        if ($validator->fails())
        {
            return ['status' => false, 'messages' => $validator->errors()->all()];
        }

        //update the account
        Account::where('code', $request->account)->update([
            'payment' => 1
        ]);

        return [
            'status' => true,
            'messages' => ['Account configuration updated'],
        ];

    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request)
    {
        //
    }

    public function destroy($id)
    {
        if (Account::where('id', $id)->update(['payment' => 0]))
        {
            return [
                'status' => true,
                'messages' => ['Account configuration updated'],
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => ['Error: failed to update accout config. Please try again']
            ];
        }
    }

    #-----------------------------------------------------------------------------------
}
