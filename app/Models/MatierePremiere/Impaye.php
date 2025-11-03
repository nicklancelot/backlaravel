<?php
namespace App\Models\MatierePremiere;

use Illuminate\Database\Eloquent\Model;

class Impaye extends Model
{
    protected $table = 'impayes';
    
    protected $fillable = [
        'reception_id',
        'date_paiement',
        'numero_facture',
        'designation',
        'encaissement',
        'prix_unitaire',
        'quantite',
        'montant_paye'
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    // CALCULS DANS LE MODÈLE (pas dans la table)
    public function getPrixTotalAttribute()
    {
        return $this->prix_unitaire * $this->quantite;
    }

    public function getResteAPayerAttribute()
    {
        return $this->prix_total - $this->montant_paye;
    }

    public function getTotalPayeAttribute()
    {
        return $this->montant_paye;
    }

    public function getSoldeImpayeAttribute()
    {
        return $this->reste_a_payer > 0 ? $this->reste_a_payer : 0;
    }

    // Méthode pour vérifier si le paiement est complet
    public function getPaiementCompletAttribute()
    {
        return $this->reste_a_payer <= 0;
    }
}