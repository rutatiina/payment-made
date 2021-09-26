<?php

namespace Rutatiina\PaymentMade\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Rutatiina\PaymentMade\Models\PaymentMadeSetting;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Rutatiina\Item\Traits\ItemsVueSearchSelect;
use Yajra\DataTables\Facades\DataTables;
use Rutatiina\FinancialAccounting\Models\Account;

class PaymentMadeSettingsController extends Controller
{
    use FinancialAccountingTrait;
    use ItemsVueSearchSelect;

    private  $txnEntreeSlug = 'offer';

    public function __construct()
    {
		$this->middleware('permission:estimates.view');
		$this->middleware('permission:estimates.create', ['only' => ['create','store']]);
		$this->middleware('permission:estimates.update', ['only' => ['edit','update']]);
		$this->middleware('permission:estimates.delete', ['only' => ['destroy']]);
	}

    public function index()
	{
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        return [
            'financial_accounts' => Account::all(),
            'settings' => PaymentMadeSetting::first()
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
            'document_name' => ['required', 'string', 'max:50'],
            'number_prefix' => ['string', 'max:20', 'nullable'],
            'number_postfix' => ['string', 'max:20', 'nullable'],
            'minimum_number_length' => ['required', 'numeric'],
            'minimum_number' => ['required', 'numeric'],
            //'maximum_number' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return ['status' => false, 'messages' => $validator->errors()->all()];
        }

        //save data posted
        $settings = PaymentMadeSetting::first();
        $settings->document_name = $request->document_name;
        $settings->number_prefix = $request->number_prefix;
        $settings->number_postfix = $request->number_postfix;
        $settings->minimum_number_length = $request->minimum_number_length;
        $settings->minimum_number = $request->minimum_number;
        //$settings->maximum_number = $request->maximum_number;
        $settings->debit_financial_account_code = $request->debit_financial_account_code;
        $settings->credit_financial_account_code = $request->credit_financial_account_code;
        $settings->save();

        return [
            'status'    => true,
            'messages'  => ['Settings updated'],
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
	    //
	}

	#-----------------------------------------------------------------------------------
}
