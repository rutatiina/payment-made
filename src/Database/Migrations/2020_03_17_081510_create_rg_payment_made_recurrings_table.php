<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRgPaymentMadeRecurringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->create('rg_payments_made_recurrings', function (Blueprint $table) {
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
            $table->unsignedBigInteger('payment_made_id');
            $table->unsignedBigInteger('txn_entree_id');
            $table->unsignedBigInteger('txn_type_id');
            $table->string('frequency', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('day_of_month', 10);
            $table->string('month', 10);
            $table->string('day_of_week', 10);
            $table->date('last_processed_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('tenant')->dropIfExists('rg_payments_made_recurrings');
    }
}
