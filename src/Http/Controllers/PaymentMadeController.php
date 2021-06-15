<?php

namespace Rutatiina\PaymentMade\Http\Controllers;

use Rutatiina\FinancialAccounting\Models\Account;
use Rutatiina\PaymentMade\Models\PaymentMadeSetting;
use Rutatiina\PaymentMade\Classes\PreValidation;
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

use Rutatiina\PaymentMade\Classes\Store as TxnStore;
use Rutatiina\PaymentMade\Classes\Approve as TxnApprove;
use Rutatiina\PaymentMade\Classes\Read as TxnRead;
use Rutatiina\PaymentMade\Classes\Copy as TxnCopy;
use Rutatiina\PaymentMade\Classes\Number as TxnNumber;
use Rutatiina\PaymentMade\Traits\Item as TxnItem;
use Rutatiina\PaymentMade\Classes\Update as TxnUpdate;

class PaymentMadeController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;
    use TxnItem;

    // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:payments.view');
        $this->middleware('permission:payments.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:payments.update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:payments.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $query = PaymentMade::query();

        if ($request->contact)
        {
            $query->where(function ($q) use ($request)
            {
                $q->where('debit_contact_id', $request->contact);
                $q->orWhere('credit_contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        $txns->load('debit_account');

        return [
            'tableData' => $txns
        ];
    }

    private function nextNumber()
    {
        $txn = PaymentMade::latest()->first();
        $settings = PaymentMadeSetting::first();

        return $settings->number_prefix . (str_pad((optional($txn)->number + 1), $settings->minimum_number_length, "0", STR_PAD_LEFT)) . $settings->number_postfix;
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new PaymentMade())->rgGetAttributes();

        $txnAttributes['number'] = $this->nextNumber();

        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['isRecurring'] = false;
        $txnAttributes['recurring'] = [
            'date_range' => [],
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [];

        unset($txnAttributes['txn_entree_id']); //!important
        unset($txnAttributes['txn_type_id']); //!important
        unset($txnAttributes['debit_contact_id']); //!important
        unset($txnAttributes['credit_contact_id']); //!important

        $data = [
            'pageTitle' => 'Record Payment', #required
            'pageAction' => 'Record', #required
            'txnUrlStore' => '/payments-made', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
    }

    public function store(Request $request)
    {
        //$data = $request->all();

        // >> format posted data
        $preValidation = (new PreValidation())->run($request->all());

        if ($preValidation['status'] == false)
        {
            return $preValidation;
        }

        $data = $preValidation['data'];

        // << format posted data

        $TxnStore = new TxnStore();
        $TxnStore->txnInsertData = $data;
        $insert = $TxnStore->run();

        if ($insert == false)
        {
            return [
                'status' => false,
                'messages' => $TxnStore->errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Payment saved'],
            'number' => 0,
            'callback' => URL::route('payments-made.show', [$insert->id], false)
        ];
    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        if (FacadesRequest::wantsJson())
        {
            $TxnRead = new TxnRead();
            return $TxnRead->run($id);
        }
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        //get the receipt details
        $paymentMade = PaymentMade::findOrFail($id);

        $paymentMade->load('contact', 'debit_account', 'credit_account', 'items.invoice');

        $contact = $paymentMade->contact;

        $txnAttributes = $paymentMade->toArray();

        $txnAttributes['_method'] = 'PATCH';

        //the contact parameter has to be formatted like the data for the drop down
        $txnAttributes['contact'] = [
            'id' => $contact->id,
            'tenant_id' => $contact->tenant_id,
            'display_name' => $contact->display_name,
            'currencies' => $contact->currencies_and_exchange_rates,
            'currency' => $contact->currency_and_exchange_rate,
        ];

        foreach ($txnAttributes['items'] as &$item)
        {
            $item['selectedTaxes'] = [];

            $item['txn_contact_id'] = $item['invoice']['debit_contact_id'];
            $item['txn_number'] = $item['invoice']['number_string'];
            $item['max_receipt_amount'] = $item['invoice']['balance'];
            $item['txn_exchange_rate'] = $item['invoice']['exchange_rate'];
        }

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

        // >> format posted data
        $preValidation = (new PreValidation())->run($request->all());

        if ($preValidation['status'] == false)
        {
            return $preValidation;
        }

        $data = $preValidation['data'];

        // << format posted data

        $TxnStore = new TxnUpdate();
        $TxnStore->txnInsertData = $data;
        $insert = $TxnStore->run();

        if ($insert == false)
        {
            return [
                'status' => false,
                'messages' => $TxnStore->errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Payment made updated'],
            'number' => 0,
            'callback' => URL::route('payments-made.show', [$insert->id], false)
        ];
    }

    public function destroy()
    {
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $TxnApprove = new TxnApprove();
        $approve = $TxnApprove->run($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => $TxnApprove->errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Payment approved'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $TxnCopy = new TxnCopy();
        $txnAttributes = $TxnCopy->run($id);

        $data = [
            'pageTitle' => 'Copy Receipts', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/financial-accounts/purchases/payments', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
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
        ]);

        if ($validator->fails())
        {
            $response = ['status' => false, 'message' => ''];

            foreach ($validator->errors()->all() as $field => $messages)
            {
                $response['message'] .= "\n" . $messages;
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
        $query->whereIn('debit_contact_id', $contact_ids);
        //$query->where('total_paid', '>', 0); //total_paid < total //todo ASAP

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

                'txn_contact_id' => $txn->debit_contact_id,
                'txn_number' => $txn->number,
                'max_receipt_amount' => $txn->balance,
                'txn_exchange_rate' => $txn->exchange_rate,

                'contact_id' => $txn->debit_contact_id,
                'description' => 'Invoice #' . $txn->number,
                'displayTotal' => 0,
                'name' => 'Invoice #' . $txn->number,
                'quantity' => 1,
                'rate' => 0,
                'selectedItem' => json_decode('{}'),
                'selectedTaxes' => [],
                'total' => 0,
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
