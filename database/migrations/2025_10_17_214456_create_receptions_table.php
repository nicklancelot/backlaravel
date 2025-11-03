<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['FG', 'GG', 'CG']);
            $table->dateTime('dateHeure');
            $table->string('designation');
            $table->string('provenance');
            
            // Informations fournisseur
            $table->string('nom_fournisseur');
            $table->string('prenom_fournisseur');
            $table->string('id_fiscale');
            $table->string('localisation');
            $table->string('contact');
            
            // Champs communs pour tous les types
            $table->decimal('poids_brut', 10, 2)->nullable();
            $table->decimal('poids_net', 10, 2)->nullable();
            $table->string('unite')->default('Kg');
            
            // Champs spécifiques pour Clous (FG)
            $table->decimal('poids_packaging', 10, 2)->nullable();
            $table->decimal('taux_dessiccation', 5, 2)->nullable();
            $table->decimal('taux_humidite_fg', 5, 2)->nullable();
            
            // Champs spécifiques pour Griffes (GG)
            $table->decimal('poids_agreé', 10, 2)->nullable();
            $table->decimal('densite', 10, 2)->nullable();
            
            // Champs spécifiques pour Feuilles (CG)
            $table->decimal('taux_humidite_cg', 5, 2)->nullable();
            $table->enum('statut', ['En attente de livraison','Non payé','Payé','Paiement incomplet', 'Livré'])->default('Non payé');
            $table->timestamps();
        });
    }

  
    public function down(): void
    {
        Schema::dropIfExists('receptions');
    }
};
