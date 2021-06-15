<template>
    <!-- Right sidebar -->
    <div v-click-outside="hideSideBarRight"
         v-if="$root.showSideBarRight"
         class="sidebar sidebar-light sidebar-right sidebar-expand-md  position-fixed bg-white" style="z-index: 1031;">

        <!-- Sidebar mobile toggler -->
        <div class="sidebar-mobile-toggler text-center">
            <a href="#" class="sidebar-mobile-expand">
                <i class="icon-screen-full"></i>
                <i class="icon-screen-normal"></i>
            </a>
            <span class="font-weight-semibold">Right sidebar</span>
            <a href="#" class="sidebar-mobile-right-toggle">
                <i class="icon-arrow-right8"></i>
            </a>
        </div>
        <!-- /sidebar mobile toggler -->


        <!-- Sidebar content -->
        <div class="sidebar-content position-fixed  bg-white" style="border-left: 2px solid rgba(0,0,0,0.125); top:0px; padding-top: 80px; min-width: 400px;">

            <!-- Sidebar search -->
            <div class="card">
                <div class="card-header">
                    <h2>Settings</h2>
                </div>

                <div class="card-body">
                    <form @submit="formSubmit">

                        <div class="form-group">
                            <div class="row">
                                <div class="col pr-0">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text w-100 rounded-0">Document name</span>
                                    </span>
                                </div>
                                <div class="col pl-0">
                                    <input type="text" v-model="settings.document_name" class="form-control rounded-0" placeholder="Document name" maxlength="50">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col pr-0">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text w-100 rounded-0">Number Prefix</span>
                                    </span>
                                </div>
                                <div class="col pl-0">
                                    <input type="text" v-model="settings.number_prefix" class="form-control rounded-0" placeholder="Number Prefix" maxlength="20">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col pr-0">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text w-100 rounded-0">Min. Number Length</span>
                                    </span>
                                </div>
                                <div class="col pl-0">
                                    <input type="text" v-model="settings.minimum_number_length" class="form-control rounded-0" placeholder="Min. Number Length">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col pr-0">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text w-100 rounded-0">Number Postfix</span>
                                    </span>
                                </div>
                                <div class="col pl-0">
                                    <input type="text" v-model="settings.number_postfix" class="form-control rounded-0" placeholder="Number Postfix" maxlength="20">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col pr-0">
                                    <span class="input-group-prepend">
                                        <span class="input-group-text w-100 rounded-0">Minimum Number</span>
                                    </span>
                                </div>
                                <div class="col pl-0">
                                    <input type="text" v-model="settings.minimum_number" class="form-control rounded-0" placeholder="Minimum Number">
                                </div>
                            </div>
                        </div>

                        <h5>Double entry settings</h5>


                        <div class="form-group">
                            <label class="font-weight-bold">Financial account to Debit</label>
                            <model-list-select :list="financialAccounts"
                                               v-model="settings.debit_financial_account_code"
                                               option-value="code"
                                               option-text="name"
                                               class="font-weight-bold"
                                               placeholder="Select Financial account to debit">
                            </model-list-select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Financial account to Credit</label>
                            <model-list-select :list="financialAccounts"
                                               v-model="settings.credit_financial_account_code"
                                               option-value="code"
                                               option-text="name"
                                               class="font-weight-bold"
                                               placeholder="Select Financial account to credit">
                            </model-list-select>
                        </div>

                        <button type="submit" class="btn btn-outline btn-primary border-primary text-primary-800 border-2 rounded font-weight-bold">
                            <i class="icon-files-empty2 mr-1"></i>
                            Update Estimate settings
                        </button>


                    </form>
                </div>
            </div>
            <!-- /sidebar search -->


        </div>
        <!-- /sidebar content -->

    </div>
    <!-- /right sidebar -->
</template>

<script>

    export default {
        data() {
            return {
                settings: {},
                financialAccounts: []
            }
        },
        watch: {},
        mounted() {
            this.fetchSettings();
        },
        methods: {
            hideSideBarRight(event) {
                this.$root.showSideBarRight = false;
            },
            fetchSettings() {

                axios.get('/payments-made/settings')
                    .then((response) => {
                        this.settings = response.data.settings;
                        this.financialAccounts = response.data.financial_accounts;
                    })
            },
            formSubmit(e) {

                e.preventDefault();

                PNotify.removeAll();

                let PNotifySettings = this.$root.PNotifySettings;

                let pNotify = new PNotify(PNotifySettings);

                axios.post('/payments-made/settings', this.settings)
                    .then((response) => {
                        this.axiosResponseHandle({
                            response: response,
                            pNotify: pNotify,
                            onSuccess: function() {},
                            onError: function() {}
                        })
                    })
            }
        },
        updated: function () {}
    }
</script>
