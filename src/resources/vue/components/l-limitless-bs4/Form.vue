<template>

    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-light">
            <div class="page-header-content header-elements-md-inline">
                <div class="page-title d-flex">
                    <h4>
                        <i class="icon-file-plus"></i>
                        {{pageTitle}}
                    </h4>
                </div>

            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="/" class="breadcrumb-item">
                            <i class="icon-home2 mr-2"></i>
                            <span class="badge badge-primary badge-pill font-weight-bold rg-breadcrumb-item-tenant-name"> {{this.$root.tenant.name | truncate(30) }} </span>
                        </a>
                        <span class="breadcrumb-item">Accounting</span>
                        <span class="breadcrumb-item">Purchases</span>
                        <span class="breadcrumb-item">Payments</span>
                        <span class="breadcrumb-item active">{{pageAction}}</span>
                    </div>

                    <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
                </div>

                <div class="header-elements">
                    <div class="breadcrumb justify-content-center">
                        <router-link :to="txnUrlStore" class=" btn btn-danger btn-sm rounded-round font-weight-bold">
                            <i class="icon-drawer3 mr-2"></i>
                            Payments
                        </router-link>
                    </div>
                </div>

            </div>

        </div>
        <!-- /page header -->

        <!-- Content area -->
        <div class="content border-0 padding-0">

            <!-- Form horizontal -->
            <div class="card shadow-none rounded-0 border-0">

                <div class="card-body p-0">

                    <loading-animation></loading-animation>

                    <form v-if="!this.$root.loading"
                          @submit="txnFormSubmit"
                          action=""
                          method="post"
                          class="max-width-1040"
                          style="margin-bottom: 100px;"
                          autocomplete="off">

                        <fieldset id="fieldset_select_contact" class="select_contact_required">

                            <div class="form-group row">
                                <label class="col-form-label col-lg-2 text-danger font-weight-bold">Customer</label>
                                <div class="col-lg-5">
                                    <model-list-select :list="txnContacts"
                                                       v-model="txnAttributes.contact"
                                                       @searchchange="txnFetchSuppliers"
                                                       @input="receiptContactInput"
                                                       option-value="id"
                                                       option-text="display_name"
                                                       placeholder="select contact">
                                    </model-list-select>
                                </div>

                                <div v-show="txnAttributes.contact_id" class="col-lg-1 p-0" >
                                    <model-list-select :list="txnAttributes.contact.currencies"
                                                       v-model="txnAttributes.contact.currency"
                                                       option-value="code"
                                                       option-text="code"
                                                       placeholder="select currency">
                                    </model-list-select>
                                </div>

                                <div class="col-lg-3 pr-0"
                                     v-show="txnAttributes.contact_id && txnAttributes.base_currency != txnAttributes.quote_currency">
                                    <div class="input-group">
											<span class="input-group-prepend">
												<span class="input-group-text">Exchange rate:</span>
											</span>
                                        <input type="text"
                                               v-model="txnAttributes.exchange_rate"
                                               class="form-control text-right"
                                               placeholder="Exchange rate">
                                    </div>
                                </div>

                            </div>

                        </fieldset>

                        <fieldset class="">

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label font-weight-bold">
                                    Payment # & Date:
                                </label>
                                <div class="col-lg-3">
                                    <input type="text" name="number" v-model="txnAttributes.number" class="form-control input-roundless" placeholder="Offer number">
                                </div>
                                <div class="col-lg-2" title="Invoice date">
                                    <date-picker v-model="txnAttributes.date"
                                                 valueType="format"
                                                 :lang="vue2DatePicker.lang"
                                                 class="font-weight-bold w-100 h-100"
                                                 placeholder="Invoice date">
                                    </date-picker>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Reference:
                                </label>
                                <div class="col-lg-5">
                                    <input type="text" name="reference" v-model="txnAttributes.reference" class="form-control input-roundless" placeholder="Enter reference">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Amount paid
                                </label>
                                <div class="col-lg-5">
                                    <div class="input-group">
                                        <span class="input-group-prepend">
                                            <span class="input-group-text font-weight-bold">{{txnAttributes.base_currency}}</span>
                                        </span>
                                        <input type="text" v-model="txnAttributes.total" class="form-control font-weight-semibold text-right" placeholder="Amount received">
                                    </div>
                                </div>
                                <div class="col-lg-5 col-form-label">
                                    <div class="custom-control custom-checkbox custom-control-inline">
                                        <input type="checkbox" v-model="receiptAutoSetItemsTotalCheckbox" class="custom-control-input" id="txn-auto-pay" >
                                        <label class="custom-control-label" for="txn-auto-pay">Auto pay bills(s)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label text-danger">
                                    Paid Through *
                                </label>
                                <div class="col-lg-5">
                                    <model-list-select :list="receiptDebitAccounts"
                                                       v-model="txnAttributes.credit_financial_account_code"
                                                       option-value="code"
                                                       option-text="name"
                                                       placeholder="select account">
                                    </model-list-select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label"> </label>
                                <div class="col-lg-5">
                                    <div class="custom-control custom-checkbox custom-control-inline">
                                        <input type="checkbox" v-model="receiptOnInvoices" class="custom-control-input" id="txn-is-recurring" >
                                        <label class="custom-control-label" for="txn-is-recurring">Make payment for Bill(s) issued.</label>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        <!--<div class="max-width-1040 clearfix ml-20" style="border-bottom: 1px solid #ddd;"></div>-->

                        <div class="card mb-0" v-if="receiptOnInvoices">

                            <fieldset>
                            <div class="form-group row mb-0 mt-0">
                                <label class="col-lg-2 col-form-label text-right border-0 rounded-left"
                                       style="white-space: nowrap;">
                                    Show Bills from:
                                </label>
                                <div class="col-lg-10">
                                    <multi-list-select
                                        :list="txnContacts"
                                        option-value="id"
                                        option-text="display_name"
                                        :selected-items="receiptContacts"
                                        placeholder="Select customer / contact"
                                        class="border-0 rounded-0"
                                        @select="receiptContactsOnSelect"
                                        @searchchange="txnFetchContacts">
                                    </multi-list-select>
                                </div>
                            </div>
                            </fieldset>

                            <hr class="m-0">

                            <table class="table">
                                <thead class="thead-default">
                                <tr>
                                    <th width="" class="h6">Date / <span class="text-muted text-size-small">Due</span></th>
                                    <th width="" class="h6">
                                        Bill#
                                        <small class="pull-right pt-5">
                                            <a href="/" v-on:click.prevent="receiptAllItemsPaidInFull">(Pay all fully)</a>
                                        </small>
                                    </th>
                                    <th width="" class="h6 text-right">Bill amount</th>
                                    <th width="" class="h6 text-right">Amount due</th>
                                    <th width="" class="h6 text-left">
                                        Receipt amount
                                        <small class="pull-right pt-5">
                                            <a href="/" v-on:click.prevent="receiptAllItemsRateReset">(Clear amounts)</a>
                                        </small>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>

                                    <tr v-if="loadingContactInvoices">
                                        <td class="text-center" colspan="5"><h1><i class="icon-spinner2 spinner"></i></h1></td>
                                    </tr>

                                    <tr v-if="!loadingContactInvoices && receiptContacts.length === 0">
                                        <td class="text-center" colspan="5"><h4>Please select contact to continue</h4></td>
                                    </tr>

                                    <tr v-if="!loadingContactInvoices && receiptContacts.length > 0 && txnAttributes.items.length === 0">
                                        <td class="text-center text-danger" colspan="5"><h4>Oops: No Bills found</h4></td>
                                    </tr>

                                    <tr v-for="(item, index) in txnAttributes.items"
                                        v-if="!loadingContactInvoices && item.bill">
                                        <td class="">
                                            <span class="text-semibold">{{item.bill.date}}</span><br>
                                            <span class="text-muted text-size-small">Due {{item.bill.due_date}}</span>
                                        </td>
                                        <td class="">
                                            <div class="text-nowrap h6">
                                                <span class="font-weight-semibold">{{item.bill.number}}</span> -
                                                <span>{{item.bill.contact_name}}</span>
                                            </div>
                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       v-model="item.paidInFull"
                                                       @change="receiptItemPaidInFull(index)"
                                                       :id="'contact-bill-'+item.bill.id" >
                                                <label class="custom-control-label" v-model="item.paidInFull" :for="'contact-bill-'+item.bill.id">Paid in full</label>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-primary font-weight-semibold">{{rgNumberFormat(item.bill.total, 2)}}</span>
                                            <small>{{item.bill.base_currency}}</small>
                                        </td>
                                        <td class="text-right h6">
                                            <span class="text-danger font-weight-semibold">{{rgNumberFormat(item.bill.balance, 2)}}</span>
                                            <small>{{item.bill.base_currency}}</small>
                                        </td>
                                        <td class="text-right align-middle">
                                            <div class="input-group">
                                                <span class="input-group-prepend">
                                                    <span class="input-group-text">{{item.bill.base_currency}}</span>
                                                </span>
                                                <input type="text"
                                                       v-model="item.rate"
                                                       v-on:keyup="receiptItemsTotal"
                                                       class="form-control font-weight-semibold text-right"
                                                       placeholder="0.00">
                                                <span class="input-group-append">
                                                    <button type="button"
                                                        @click="receiptItemRateReset(index)"
                                                        class="btn btn-outline bg-danger border-danger text-danger-800 btn-icon"
                                                        title="Delete row">
                                                            <i class="icon-cross3"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>

                                </tbody>

                                <tfoot>

                                <tr v-if="txnAttributes.items.length > 0">
                                    <td class="text-bold" colspan="3"></td>
                                    <td class="pl-15 text-bold">
                                        <span v-if="txnAttributes.base_currency"
                                              class="badge badge-primary badge-pill font-weight-bold rg-breadcrumb-item-tenant-name">
                                            {{txnAttributes.base_currency}}
                                        </span>
                                        Total due
                                    </td>
                                    <td class="text-right">{{rgNumberFormat(receiptTotalDue, 2)}}</td>
                                </tr>

                                <tr v-if="txnAttributes.items.length > 0">
                                    <td class="p-15 no-border" colspan="3"></td>
                                    <td class="p-15 no-border-right text-bold size4of5">
                                        <span v-if="txnAttributes.base_currency"
                                              class="badge badge-primary badge-pill font-weight-bold rg-breadcrumb-item-tenant-name">
                                            {{txnAttributes.base_currency}}
                                        </span>
                                        Amount Received
                                    </td>
                                    <td class="no-border-left font-weight-bold h4 text-right">
                                        {{rgNumberFormat(txnAttributes.total, 2)}}
                                    </td>
                                </tr>

                                <tr v-if="txnAttributes.items.length > 0 && receiptAmountUnallocated">
                                    <td class="p-15 no-border" colspan="3"></td>
                                    <td class="p-15 no-border-right text-bold size4of5 text-danger">
                                        <span v-if="txnAttributes.base_currency"
                                              class="badge badge-primary badge-pill font-weight-bold rg-breadcrumb-item-tenant-name">
                                            {{txnAttributes.base_currency}}
                                        </span>
                                        Amount unallocated
                                    </td>
                                    <td class="no-border-left h5 text-right text-danger">
                                        {{rgNumberFormat(receiptAmountUnallocated, 2)}}
                                    </td>
                                </tr>

                                </tfoot>

                            </table>

                        </div>

                        <fieldset class="mt-3">

                            <!--https://stackoverflow.com/questions/53409139/how-to-upload-multiple-images-files-with-javascript-and-axios-formdata-->
                            <!--https://laracasts.com/discuss/channels/vue/upload-multiple-files-and-relate-them-to-post-model-->
                            <div class="form-group row">
                                <label class="col-form-label col-lg-2">Attach files</label>
                                <div class="col-lg-10">
                                    <input ref="filesAttached" type="file" multiple class="form-control border-0 p-1 h-auto">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Customer notes:
                                </label>
                                <div class="col-lg-10">
                                    <textarea v-model="txnAttributes.contact_notes" class="form-control input-roundless" rows="2" placeholder="Contact notes"></textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Terms and conditions:
                                </label>
                                <div class="col-lg-10">
                                    <textarea v-model="txnAttributes.terms_and_conditions" class="form-control input-roundless" rows="2" placeholder="Mention your company's Terms and Conditions."></textarea>
                                </div>
                            </div>

                        </fieldset>

                        <div class="text-left col-md-10 offset-md-2 p-0">

                            <div class="btn-group ml-1">
                                <button type="button" class="btn btn-outline bg-purple-300 border-purple-300 text-purple-800 btn-icon border-2 dropdown-toggle" data-toggle="dropdown">
                                    <i class="icon-cog"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-left">
                                    <a href="/" class="dropdown-item" v-on:click.prevent="txnOnSave('draft', false)">
                                        <i class="icon-file-text3"></i> Save as draft
                                    </a>
                                    <a href="/" class="dropdown-item" v-on:click.prevent="txnOnSave('approved', false)">
                                        <i class="icon-file-check2"></i> Save and approve
                                    </a>
                                    <a href="/" class="dropdown-item" v-on:click.prevent="txnOnSave('approved', true)">
                                        <i class="icon-mention"></i> Save, Approve and Send
                                    </a>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger font-weight-bold">
                                <i class="icon-file-plus2 mr-1"></i> {{txnSubmitBtnText}}
                            </button>

                        </div>

                    </form>

                </div>
            </div>
            <!-- /form horizontal -->


        </div>
        <!-- /content area -->

    </div>
    <!-- /main content -->

</template>

<script>

    export default {
        name: 'AccoutingSalesReceiptsForm',
        components: {

        },
        data() {
            return {
                loadingContactInvoices: false,
                receiptContacts: [],
                receiptOnInvoices: true,
                receiptAutoSetItemsTotalCheckbox: false,
                receiptDebitAccounts: [],
                receiptAmountUnallocated: 0,
                receiptPaymentModes: [
                    { value: 'Bank Deposit', text: 'Bank Deposit' },
                    { value: 'Bank Remittance', text: 'Bank Remittance' },
                    { value: 'Bank Transfer', text: 'Bank Transfer' },
                    { value: 'Cash', text: 'Cash' },
                    { value: 'Check', text: 'Check' },
                    { value: 'Credit Card', text: 'Credit Card' },
                    { value: 'Mobile money', text: 'Mobile money' }
                ]
            }
        },
        computed: {
            receiptTotalDue: function () {
                let currentObj = this;
                let t = 0
                currentObj.txnAttributes.items.forEach(function (item) {
                    //console.log(item.txn)
                    if (typeof item.bill !== 'undefined' && typeof item.bill.balance !== 'undefined') {
                        //console.log(item.bill.balance)
                        t += currentObj.rgNumber(item.bill.balance)
                    }
                })
                return t
            }
        },
        watch: {
            'receiptContacts': function () {
                this.fetchContactBills()
            },
            'receiptAutoSetItemsTotalCheckbox': function () {
                this.receiptAutoSetItemsTotal()
            },
            'txnAttributes.total': function () {
                this.receiptAutoSetItemsTotal()
            }
        },
        created: function () {
            this.pageTitle = 'Create Sales Order'
            this.pageAction = 'Create'
        },
        mounted() {
            this.$root.appMenu('accounting')

            //console.log(this.$route.fullPath)
            this.txnCreateData()
            this.txnFetchSuppliers('-initiate-')
            //this.fetchContactBills()
            this.fetchReceiptDebitAccounts()

            //this.txnFetchAccounts()
        },
        methods: {
            async fetchContactBills() {

                let contact_ids = []

                this.receiptContacts.forEach(function (contact) {
                    contact_ids.push(contact.id)
                })

                //do not continue if no contacts are selected
                if (contact_ids.length === 0) {
                    this.loadingContactInvoices = false
                    this.txnAttributes.items = []
                    return false
                }
                //*/

                try {

                    this.loadingContactInvoices = true

                    return await axios.post('/payments-made/bills', {contact_ids: contact_ids})
                        .then(response => {
                            this.loadingContactInvoices = false
                            if (response.data.status === true) {
                                this.txnAttributes.items = response.data.items
                                this.receiptAutoSetItemsTotal()
                            } else {
                                this.txnAttributes.items = []
                            }
                        })
                        .catch(function (error) {
                            // handle error
                            console.log(error); //test
                        })
                        .finally(function (response) {
                            // always executed this is supposed
                        })

                } catch (e) {
                    console.log(e); //test
                }
            },
            async fetchReceiptDebitAccounts() {

                try {

                    return await axios.post('/payments-made/credit-accounts')
                        .then(response => {
                            this.receiptDebitAccounts = response.data
                        })
                        .catch(function (error) {
                            // handle error
                            console.log(error); //test
                        })
                        .finally(function (response) {
                            // always executed this is supposed
                        })

                } catch (e) {
                    console.log(e); //test
                }
            },
            receiptContactsOnSelect(items, lastSelectItem) {
                this.receiptContacts = items
                //console.log(lastSelectItem)
            },
            receiptContactInput(contact, row) {
                this.txnContactSelect(contact, row)
                this.receiptContacts.push(contact)
            },
            receiptAutoSetItemsTotal() {

                let currentObj = this;

                //console.log(currentObj.receiptAutoSetItemsTotalCheckbox)

                if (currentObj.receiptAutoSetItemsTotalCheckbox) {

                    let amount_received = currentObj.rgNumber(currentObj.txnAttributes.total)
                    let amount_balance = 0;

                    currentObj.txnAttributes.items.forEach(function (item) {

                        item.rate = 0
                        item.paidInFull = false

                        amount_balance = currentObj.rgNumber(item.bill.balance) //console.log(amount_balance);
                        if (amount_received >= amount_balance) {
                            item.rate = amount_balance.toFixed(2)
                            //item.total = amount_balance.toFixed(2)
                        } else if (amount_received < amount_balance && amount_received > 0) {
                            item.rate = amount_received.toFixed(2)
                            //item.total = amount_received.toFixed(2)
                        }

                        amount_received = currentObj.rgNumber(currentObj.rgNumber(amount_received) - currentObj.rgNumber(amount_balance))

                    })

                }

                currentObj.receiptItemsTotal()
            },
            receiptItemsTotal() {
                let currentObj = this
                let au = 0
                currentObj.txnAttributes.items.forEach(function (item) {
                    item.total = item.rate
                    au += currentObj.rgNumber(item.total)
                })
                currentObj.receiptAmountUnallocated = (currentObj.txnAttributes.total - au)
                //console.log(currentObj.receiptAmountUnallocated)
            },
            receiptItemRateReset(index) {
                this.txnAttributes.items[index].rate = 0
                this.txnAttributes.items[index].paidInFull = false
                this.receiptAutoSetItemsTotalCheckbox = false
                this.receiptItemsTotal()
            },
            receiptItemPaidInFull(index) {
                let currentObj = this
                currentObj.receiptAutoSetItemsTotalCheckbox = false
                if (currentObj.txnAttributes.items[index].paidInFull) {
                    currentObj.txnAttributes.items[index].rate = currentObj.rgNumber(currentObj.txnAttributes.items[index].bill.balance).toFixed(currentObj.$root.tenant.decimal_places)
                }
                this.receiptItemsTotal()
            },
            receiptAllItemsRateReset() {
                this.txnAttributes.items.forEach(function (item) {
                    item.rate = 0
                    item.paidInFull = false
                })
                this.receiptAutoSetItemsTotalCheckbox = false
                this.receiptItemsTotal()
            },
            receiptAllItemsPaidInFull() {
                let currentObj = this
                currentObj.receiptAutoSetItemsTotalCheckbox = false
                currentObj.txnAttributes.items.forEach(function (item) {
                    item.paidInFull = true
                    item.rate = currentObj.rgNumber(item.bill.balance).toFixed(currentObj.$root.tenant.decimal_places)
                })
                currentObj.receiptItemsTotal()
            },
        },
        beforeUpdate: function () {
            //
        },
        updated: function () {
            //this.txnComponentUpdates()
        },
        destroyed: function () {
            //
        }
    }
</script>
