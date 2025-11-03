<?php
namespace App\Models\MatierePremiere;

use Illuminate\Database\Eloquent\Model;

class FicheLivraison extends Model
{
    protected $table = 'fiche_livraisons';
    
    protected $fillable = [
        'reception_id',
        'date_livraison',
        'lieu_depart',
        'destination',
        'livreur_nom',
        'livreur_prenom',
        'livreur_telephone',
        'livreur_vehicule',
        'destinateur_nom',
        'destinateur_prenom',
        'destinateur_fonction',
        'destinateur_contact',
        'type_produit',
        'poids_net',
        'ristourne_regionale',
        'ristourne_communale',
        'prix_unitaire',
        'quantite_a_livrer'
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}