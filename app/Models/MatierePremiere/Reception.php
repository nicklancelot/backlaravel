<?php
namespace App\Models\MatierePremiere;

use Illuminate\Database\Eloquent\Model;

class Reception extends Model
{
    protected $table = 'receptions';
    
    protected $fillable = [
        'type',
        'dateHeure',
        'designation',
        'provenance',
        'nom_fournisseur',
        'prenom_fournisseur',
        'id_fiscale',
        'localisation',
        'contact',
        'poids_brut',
        'poids_net',
        'unite',
        'poids_packaging',
        'taux_dessiccation',
        'taux_humidite_fg',
        'poids_agreé',
        'densite',
        'taux_humidite_cg',
        'statut'
    ];

    // Constantes pour les statuts
    const STATUT_EN_ATTENTE = 'En attente de livraison';
    const STATUT_NON_PAYE = 'Non payé';
    const STATUT_PAYE = 'Payé';
    const STATUT_PAIEMENT_INCOMPLET = 'Paiement incomplet';
    const STATUT_LIVRE = 'Livré';

    // Constantes pour les types
    const TYPE_CLOUS = 'FG';
    const TYPE_GRIFFES = 'GG';
    const TYPE_FEUILLES = 'CG';

    // Relations
    public function facturation()
    {
        return $this->hasOne(Facturation::class);
    }

    public function impaye()
    {
        return $this->hasOne(Impaye::class);
    }

    public function ficheLivraison()
    {
        return $this->hasOne(FicheLivraison::class);
    }

    // Méthodes pour vérifier les transitions possibles
 public function canTransitionToFacturation()
    {
        return $this->statut === self::STATUT_NON_PAYE;
    }

    public function canTransitionToImpaye()
    {
        return in_array($this->statut, [self::STATUT_NON_PAYE, self::STATUT_PAIEMENT_INCOMPLET]);
    }

    public function canTransitionToFicheLivraison()
    {
        return $this->statut === self::STATUT_PAYE;
    }

    // ✅ AJOUTER CETTE MÉTHODE POUR "LIVRÉ"
    public function canTransitionToLivre()
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function getAvailableTransitions()
    {
        $transitions = [];

        if ($this->canTransitionToFacturation()) {
            $transitions[] = 'facturation';
        }

        if ($this->canTransitionToImpaye()) {
            $transitions[] = 'impaye';
        }

        if ($this->canTransitionToFicheLivraison()) {
            $transitions[] = 'fiche_livraison';
        }

        // ✅ CORRECTION : Utiliser la bonne méthode
        if ($this->canTransitionToLivre()) {
            $transitions[] = 'livre';
        }

        return $transitions;
    }

    public function marquerCommeLivre()
    {
        return $this->update(['statut' => self::STATUT_LIVRE]);
    }
}