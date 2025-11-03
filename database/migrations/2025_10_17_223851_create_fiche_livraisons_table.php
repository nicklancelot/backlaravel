<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fiche_livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->onDelete('cascade');
            $table->date('date_livraison');
            $table->string('lieu_depart');
            $table->string('destination');
            
            // Informations livreur
            $table->string('livreur_nom');
            $table->string('livreur_prenom');
            $table->string('livreur_telephone');
            $table->string('livreur_vehicule');
            
            // Informations destinateur
            $table->string('destinateur_nom');
            $table->string('destinateur_prenom');
            $table->string('destinateur_fonction');
            $table->string('destinateur_contact');
            
            // Produits
            $table->enum('type_produit', ['Feuille', 'Clous', 'Griffes']);
            $table->decimal('poids_net', 10, 2);
            $table->decimal('ristourne_regionale', 10, 2)->default(0);
            $table->decimal('ristourne_communale', 10, 2)->default(0);
            
            // Fiche de stockage (Sortie)
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('quantite_a_livrer', 10, 2);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fiche_livraisons');
    }
};