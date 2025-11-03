<?php

namespace App\Models\MatierePremiere;

use Illuminate\Database\Eloquent\Model;

class Facturation extends Model
{
    protected $table = 'facturations';
    
    protected $fillable = [
        'reception_id',
        'date_paiement',
        'numero_facture',
        'designation',
        'encaissement',
        'prix_unitaire',
        'quantite',
        'paiement_avance',
        'montant_paye',
        'prix_total' // Inclure dans fillable
    ];

    // Calcul automatique du prix_total avant sauvegarde
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->prix_total = $model->prix_unitaire * $model->quantite;
        });
    }

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    // Accesseurs pour les calculs supplémentaires
    public function getResteAPayerAttribute()
    {
        return $this->prix_total - $this->montant_paye;
    }

    public function getSoldeImpayeAttribute()
    {
        return $this->reste_a_payer > 0 ? $this->reste_a_payer : 0;
    }

    public function getPaiementCompletAttribute()
    {
        return $this->reste_a_payer <= 0;
    }

    public function getPourcentagePayeAttribute()
    {
        if ($this->prix_total == 0) return 0;
        return ($this->montant_paye / $this->prix_total) * 100;
    }
}