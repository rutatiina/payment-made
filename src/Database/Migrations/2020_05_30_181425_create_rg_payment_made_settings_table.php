<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRgPaymentMadeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->create('rg_payments_made_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

            //>> default columns
            $table->softDeletes();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            //<< default columns

            //>> table columns
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('document_name', 50);
            $table->enum('document_type', ['inventory', 'invoice', 'bill', 'receipt', 'payment', 'other', 'tax', 'discount', 'order'])->nullable();
            $table->string('number_prefix', 20)->nullable();
            $table->string('number_postfix', 20)->nullable();
            $table->unsignedTinyInteger('minimum_number_length')->default(5); //the number length should always be padded if bellow this value e.g. 3 means 001/022/ 1234
            $table->unsignedBigInteger('minimum_number')->default(1)->nullable();
            $table->unsignedBigInteger('maximum_number')->nullable();

            //double entry settings
            $table->unsignedBigInteger('debit_financial_account_code')->nullable();
            $table->unsignedBigInteger('credit_financial_account_code')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->dropIfExists('rg_payments_made_settings');
    }
}
