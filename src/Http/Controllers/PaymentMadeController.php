<?php

namespace Rutatiina\PaymentMade\Http\Controllers;

use Rutatiina\FinancialAccounting\Models\Account;
use Rutatiina\PaymentMade\Models\PaymentMadeSetting;
use Rutatiina\PaymentMade\Classes\PreValidation;
use Rutatiina\PaymentMade\Services\PaymentMadeService;
use URL;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Rutatiina\Bill\Models\Bill;
use Rutatiina\PaymentMade\Models\PaymentMade;
use Rutatiina\FinancialAccounting\Classes\Transaction;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Rutatiina\Banking\Models\Account as BankAccount;
use Rutatiina\Contact\Traits\ContactTrait;
use Yajra\DataTables\Facades\DataTables;

class PaymentMadeController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;

    // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:payments-made.view');
        $this->middleware('permission:payments-made.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:payments-made.update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:payments-made.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $query = PaymentMade::query();

        if ($request->contact)
        {
            $query->where(function ($q) use ($request)
            {
                $q->where('contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        $txns->load('debit_financial_account');

        return [
            'tableData' => $txns
        ];
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $settings = PaymentMadeSetting::has('financial_account_to_debit')->with(['financial_account_to_debit'])->firstOrFail();

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new PaymentMade())->rgGetAttributes();

        $txnAttributes['number'] = PaymentMadeService::nextNumber();
        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['payment_mode'] = optional($settings)->payment_mode_default;
        $txnAttributes['credit_financial_account_code'] = optional($settings)->financial_account_to_credit->code;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [];

        return [
            'pageTitle' => 'Record Payment', #required
            'pageAction' => 'Record', #required
            'txnUrlStore' => '/payments-made', #required
            'txnAttributes' => $txnAttributes, #required
        ];
    }

    public function store(Request $request)
    {
        //$data = $request->all();

        $storeService = PaymentMadeService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => PaymentMadeService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Payment saved'],
            'number' => 0,
            'callback' => route('payments-made.show', [$storeService->id], false)
        ];
    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        $txn = PaymentMade::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends([
            'taxes',
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('ui.limitless::layout_2-ltr-default.appVue');
        }

        //get the receipt details
        $txnAttributes = PaymentMadeService::edit($id);

        $data = [
            'pageTitle' => 'Edit Payment', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/payments-made/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    //to be revised - i thought user shld not be able to update payment
    public function update(Request $request)
    {
        //return $request->all();

        $storeService = PaymentMadeService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => PaymentMadeService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Payment made updated'],
            'number' => 0,
            'callback' => route('payments-made.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
    {
        $destroy = PaymentMadeService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Payment made deleted'],
                'callback' => route('payments-made.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => PaymentMadeService::$errors
            ];
        }
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $approve = PaymentMadeService::approve($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => PaymentMadeService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Payment made approved'],
        ];

    }

    public function creditAccounts()
    {
        //list accounts that are either payment accounts or bank accounts
        return Account::where('payment', 1)->orWhere('bank_account_id', '>', 0)->get();
    }

    public function datatables(Request $request)
    {

        $txns = Transaction::setRoute('show', route('accounting.purchases.payments.show', '_id_'))
            ->setRoute('edit', route('accounting.purchases.payments.edit', '_id_'))
            ->setSortBy($request->sort_by)
            ->paginate(false)
            ->findByEntree($this->txnEntreeSlug);

        return Datatables::of($txns)->make(true);
    }

    public function exportToExcel(Request $request)
    {
        $txns = collect([]);

        $txns->push([
            'DATE',
            'NUMBER',
            'METHOD',
            'REFERENCE',
            'SUPPLIER / VENDOR',
            'PAID THROUGH',
            'AMOUNT',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id)
        {

            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->number,
                $txn->credit_account->name,
                $txn->reference,
                $txn->contact_name,
                $txn->debit_account->name,
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-payments-export-' . date('Y-m-d-H-m-s') . '.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }

    public function bills(Request $request)
    {
        $contact_ids = $request->contact_ids;

        $validator = Validator::make($request->all(), [
            'contact_ids' => ['required', 'array'],
            'contact_ids.*' => ['numeric', 'nullable'],
        ]);

        if ($validator->fails())
        {
            $response = ['status' => false, 'messages' => []];

            foreach ($validator->errors()->all() as $field => $messages)
            {
                $response['messages'][] = $messages;
            }

            return json_encode($response);
        }

        /*
         * array with empty value was being posted e.g. array(1) { [0]=> NULL }
         * so to correct that, loop through and delete non values
         */
        foreach ($contact_ids as $key => $contact_id)
        {
            if (!is_numeric($contact_id))
            {
                unset($contact_ids[$key]);
            }
        }

        //var_dump($contact_ids); exit;


        if (empty($contact_id))
        {
            return [
                'currencies' => [],
                'txns' => [],
                'notes' => ''
            ];
        }

        $query = Bill::query();
        $query->orderBy('date', 'ASC');
        $query->orderBy('id', 'ASC');
        $query->whereIn('contact_id', $contact_ids);
        $query->whereColumn('total_paid', '<', 'total');

        $txns = $query->get();

        $currencies = [];
        $items = [];

        foreach ($txns as $index => $txn)
        {
            //$txns[$index] = Transaction::transaction($txn);
            $currencies[$txn['base_currency']][] = $txn['id'];

            $itemTxn = [
                'id' => $txn->id,
                'date' => $txn->date,
                'due_date' => $txn->due_date,
                'number' => $txn->number,
                'base_currency' => $txn->base_currency,
                'contact_name' => $txn->contact_name,
                'total' => $txn->total,
                'balance' => $txn->balance,
            ];

            $items[] = [
                'bill' => $itemTxn,
                'paidInFull' => false,

                'txn_contact_id' => $txn->contact_id,
                'txn_number' => $txn->number,
                'max_receipt_amount' => $txn->balance,
                'txn_exchange_rate' => $txn->exchange_rate,

                'contact_id' => $txn->contact_id,
                'description' => 'Invoice #' . $txn->number,
                'displayTotal' => 0,
                'amount' => 0,
                'selectedItem' => json_decode('{}'),
                'selectedTaxes' => [],
                'amount_withheld' => 0,
                'bill_id' => $txn->id
            ];
        }

        $notes = '';
        foreach ($currencies as $currency => $txn_ids)
        {
            $notes .= count($txn_ids) . ' ' . $currency . ' invoice(s). ';
        }

        $contactSelect2Options = [];


        /*
        foreach ($contact_ids as $contact_id) {

			$contact = Contact::find($contact_id);

			foreach ($contact->currencies as $currency) {

				$contactSelect2Options[] = array(
					'id' => $currency,
					'text' => $currency,
					'exchange_rate' => Forex::exchangeRate($currency, Auth::user()->tenant->base_currency),
				);
			}

		}
        */

        return [
            'status' => true,
            'items' => $items,
            'currencies' => $contactSelect2Options,
            'txns' => $txns->toArray(),
            'notes' => $notes
        ];
    }

}
