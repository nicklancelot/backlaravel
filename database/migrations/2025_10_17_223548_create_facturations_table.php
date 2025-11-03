<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facturations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->onDelete('cascade');
            $table->date('date_paiement');
            $table->string('numero_facture')->unique();
            $table->string('designation');
            $table->string('encaissement');
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('quantite', 10, 2);
            $table->decimal('paiement_avance', 15, 2)->default(0);
            $table->decimal('montant_paye', 15, 2);
            $table->decimal('prix_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('facturations');
    }
};