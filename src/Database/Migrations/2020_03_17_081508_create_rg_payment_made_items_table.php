<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRgPaymentMadeItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->create('rg_payments_made_items', function (Blueprint $table) {
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
            $table->unsignedBigInteger('payments_made_id');

            $table->unsignedBigInteger('bill_id')->nullable();

            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('name', 100);
            $table->string('description', 250)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedDecimal('rate', 20,5);
            $table->unsignedDecimal('total', 20, 5);
            $table->unsignedDecimal('amount_withheld', 20, 5);
            $table->unsignedInteger('units')->nullable();
            $table->string('batch', 100)->nullable();
            $table->date('expiry')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->dropIfExists('rg_payments_made_items');
    }
}
