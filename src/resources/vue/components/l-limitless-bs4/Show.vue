<template>

    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-light d-print-none">
            <div class="page-header-content header-elements-md-inline">
                <div class="page-title d-flex">
                    <h4><i class="icon-files-empty2 mr-2"></i> <span class="font-weight-semibold">Payment Voucher</span></h4>
                    <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
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
                        <span class="breadcrumb-item active">Payment</span>
                    </div>

                    <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
                </div>

                <div class="header-elements">
                    <div class="breadcrumb justify-content-center">
                        <router-link to="/payments-made/create"
                                     class="btn btn-outline btn-primary border-primary text-primary-800 border-2 btn-sm rounded font-weight-bold mr-2">
                            <i class="icon-files-empty2 mr-1"></i>
                            New Payment
                        </router-link>

                        <button type="button" @click="$root.showSideBarRight=true" class="btn btn-outline btn-primary border-primary text-primary-800 border-2 btn-sm rounded btn-icon" >
                            <i class="icon-cog52"></i>
                        </button>

                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content border-0 p-0">

            <loading-txn-animation></loading-txn-animation>

            <!-- Content area -->
            <div class="content" v-if="!this.$root.loadingTxn && txnData">

                <!-- txn template -->
                <div v-if="txnData.status === 'draft'"
                     class="card border-left-3 border-warning rounded-0 max-width-960 ml-auto mr-auto rg-print-border-0">
                    <div class="card-header header-elements-inline d-print-none text-danger">
                        <h6 class="card-title font-weight-bold">
                            Approve {{txnData.document_name}}<br>
                            <small>You are viewing a draft</small>
                        </h6>
                        <div class="header-elements">
                            <button type="button"
                                    class="btn bg-warning font-weight-bold"
                                    @click="txnApprove('/payments-made/receipts/'+txnData.id+'/approve')"><i class="icon-printer mr-2"></i> Click here to Approve</button>
                        </div>
                    </div>
                </div>

                <div class="card max-width-960 m-auto rg-print-border-0" v-if="!this.$root.loadingTxn">

                    <div class="card-header bg-transparent header-elements-inline d-print-none">
                        <h6 class="card-title" v-if="txnData.type">{{txnData.document_name}} #{{txnData.number}}</h6>
                        <div class="header-elements">

                            <router-link :to="'/payments-made/receipts/'+$route.params.id+'/copy'"
                                         class="btn btn-light btn-sm">
                                <i class="icon-copy"></i>
                                Copy
                            </router-link>

                            <button type="button" class="btn btn-light btn-sm ml-3" onclick="window.print();"><i class="icon-printer mr-2"></i> Print</button>

                        </div>
                    </div>

                    <div class="card-body">

                        <div class="row"
                             v-if="$root.tenant.logo">
                            <div class="col-sm-6 mb-2">
                                <img :src="'/timthumb.php?src=storage/' + $root.tenant.logo + '&h=27&q=100'" class="" :alt="$root.tenant.name" >
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-4">
                                    <ul class="list list-unstyled mb-0">
                                        <li>
                                            <h5 class="rg-font-weight-600">{{$root.tenant.name}}</h5>
                                        </li>
                                        <li v-if="$root.tenant.street_line_1"><small class="text-muted">Street #1</small> {{$root.tenant.street_line_1}}</li>
                                        <li v-if="$root.tenant.street_line_2"><small class="text-muted">Street #2</small> {{$root.tenant.street_line_2}}</li>
                                        <li v-if="$root.tenant.city"><small class="text-muted">City</small> {{$root.tenant.city}}</li>
                                        <li v-if="$root.tenant.state_province"><small class="text-muted">Province</small> {{$root.tenant.state_province}}</li>
                                        <li v-if="$root.tenant.phone"><small class="text-muted">Phone no.</small> {{$root.tenant.phone}}</li>
                                        <li v-if="$root.tenant.website"><small class="text-muted">Website</small> {{$root.tenant.website}}</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="mb-4">
                                    <div class="text-sm-right">
                                        <h4 class="text-primary mb-2 mt-md-2">{{txnData.document_name}} #{{txnData.number}}</h4>
                                        <ul class="list list-unstyled mb-0">
                                            <li>Date: <span class="font-weight-semibold">{{txnData.date}}</span></li>
                                            <li v-if="txnData.due_date">Due date: <span class="font-weight-semibold">{{txnData.due_date}}</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-md-flex flex-md-wrap">
                            <div class="mb-4 mb-md-2">
                                <span class="text-muted" v-if="txnData.type">{{txnData.document_name}} To:</span>
                                <ul v-if="txnData.contact" class="list list-unstyled mb-0">
                                    <li><h5 class="my-2">{{txnData.contact.contact_salutation}} {{txnData.contact_name}}</h5></li>
                                    <li v-if="txnData.contact.shipping_address_street1 && txnData.contact.shipping_address_street2">
                                        <span class="font-weight-semibold">{{txnData.contact.shipping_address_street1}} {{txnData.contact.shipping_address_street2}}</span>
                                    </li>
                                    <li v-if="txnData.contact.shipping_address_city">{{txnData.contact.shipping_address_city}}</li>
                                    <li v-if="txnData.contact.shipping_address_state">{{txnData.contact.shipping_address_state}}</li>
                                    <li v-if="txnData.contact.shipping_address_country">{{txnData.contact.shipping_address_country}}</li>
                                    <li v-if="txnData.contact.contact_work_phone">{{txnData.contact.contact_work_phone}}</li>
                                    <li v-if="txnData.contact.contact_email"><a href="#">{{txnData.contact.contact_email}}</a></li>
                                </ul>
                            </div>

                            <!--
                            <div class="mb-2 ml-auto">
                                <span class="text-muted">Summary:</span>
                                <div class="d-flex flex-wrap wmin-md-400">
                                    <ul class="list list-unstyled mb-0">
                                        <li><h5 class="my-2">Total Due:</h5></li>
                                        <li v-if="txnData.reference">Reference:</li>
                                    </ul>

                                    <ul class="list list-unstyled text-right mb-0 ml-auto">
                                        <li><h5 class="font-weight-semibold my-2">{{rgNumberFormat(txnData.total, 2)}}</h5></li>
                                        <li v-if="txnData.reference">{{txnData.reference}}</li>
                                    </ul>
                                </div>
                            </div>
                            -->
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-lg">
                            <thead>
                            <tr class="bg-light">
                                <th class="font-weight-bold">Description</th>
                                <th class="font-weight-bold text-right">Rate</th>
                                <th class="font-weight-bold text-right">Quantity</th>
                                <th class="font-weight-bold text-right">Total <small> {{txnData.base_currency}}</small></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="item in txnData.items">
                                <td>
                                    <h6 class="mb-0">{{item.name}}</h6>
                                    <span class="">{{item.description}}</span>
                                </td>
                                <td class="text-right">{{rgNumberFormat(item.rate, 2)}}</td>
                                <td class="text-right">{{rgNumberFormat(item.quantity)}}</td>
                                <td class="text-right">
                                    <span class="font-weight-semibold">{{rgNumberFormat(item.total, 2)}}</span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-body pr-0">
                        <div class="d-md-flex flex-md-wrap">
                            <div class="pt-2 mb-3 text-muted">
                                <h6>Authorized Stamp / Signature</h6>
                            </div>

                            <div class="mb-3 wmin-md-400 ml-auto">
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                        <tr>
                                            <th>Subtotal:</th>
                                            <td class="text-right">{{rgNumberFormat(txnData.taxable_amount, 2)}}</td>
                                        </tr>

                                        <tr v-for="item in txnData.items"
                                            v-if="['txn_type', 'tax'].includes(item.type)">
                                            <th>{{item.description}}</th>
                                            <td class="text-right">{{rgNumberFormat(item.total, $root.tenant.decimal_places)}}</td>
                                        </tr>

                                        <tr>
                                            <th>Total:</th>
                                            <td class="text-right">
                                                <span>{{txnData.base_currency}}</span>
                                                <span class="h5 font-weight-semibold">{{rgNumberFormat(txnData.total, 2)}}</span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-right mt-3 mr-3 d-print-none">
                                    <button type="button"
                                            class="btn btn-primary btn-labeled btn-labeled-left">
                                        <b><i class="icon-paperplane"></i></b> Send {{txnData.document_name}}
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6>Amount in words:</h6>
                        <p class="text-muted">{{txnData.total_in_words}}</p>
                    </div>

                    <div class="card-footer">
                        <span class="text-muted">Thank you for working with us. Always contact us for any feedback.</span>
                    </div>
                </div>
                <!-- /invoice template -->

            </div>
            <!-- /content area -->

        </div>
        <!-- /content area -->


        <!-- Footer -->
        <div class="navbar navbar-expand-lg navbar-light">
            <div class="text-center d-lg-none w-100">
                <button type="button" class="navbar-toggler dropdown-toggle" data-toggle="collapse" data-target="#navbar-footer">
                    <i class="icon-unfold mr-2"></i>
                    Footer
                </button>
            </div>

            <div class="navbar-collapse collapse" id="navbar-footer">
					<span class="navbar-text">
						&copy; 2015 - 2018. <a href="#">Limitless Web App Kit</a> by <a href="http://themeforest.net/user/Kopyov" target="_blank">Eugene Kopyov</a>
					</span>

                <ul class="navbar-nav ml-lg-auto">
                    <li class="nav-item"><a href="https://kopyov.ticksy.com/" class="navbar-nav-link" target="_blank"><i class="icon-lifebuoy mr-2"></i> Support</a></li>
                    <li class="nav-item"><a href="http://demo.interface.club/limitless/docs/" class="navbar-nav-link" target="_blank"><i class="icon-file-text2 mr-2"></i> Docs</a></li>
                    <li class="nav-item"><a href="https://themeforest.net/item/limitless-responsive-web-application-kit/13080328?ref=kopyov" class="navbar-nav-link font-weight-semibold"><span class="text-pink-400"><i class="icon-cart2 mr-2"></i> Purchase</span></a></li>
                </ul>
            </div>
        </div>
        <!-- /footer -->

    </div>
    <!-- /main content -->

</template>

<script>

    export default {
        //components: {},
        data() {
            return {}
        },
        watch: {
            $route (to, from) {
                if (this.txnShowId !== this.$route.params.id) this.txnFetchData()
            }
        },
        mounted() {
            this.$root.appMenu('accounting')

            this.txnFetchData() //get the details of the transaction

            this.txnShowId = this.$route.params.id

        },
        methods: {},
        ready:function(){},
        beforeUpdate: function () {},
        updated: function () {
            this.txnShowId = this.$route.params.id;
            document.title = this.txnData.document_name + ' ' + this.txnData.number;
        }
    }
</script>
