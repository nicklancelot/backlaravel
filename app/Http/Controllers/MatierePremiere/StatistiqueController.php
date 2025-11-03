<?php

namespace App\Http\Controllers\MatierePremiere;

use App\Http\Controllers\Controller;
use App\Models\MatierePremiere\Reception;
use Illuminate\Http\Request;

class StatistiqueController extends Controller
{
    /**
     * Obtenir les quantités non livrées par type de matière première
     */
    public function quantitesNonLivrees()
    {
        try {
            // Récupérer les réceptions non livrées groupées par type
            $quantites = Reception::where('statut', '!=', Reception::STATUT_LIVRE)
                ->selectRaw('type, SUM(poids_net) as quantite_totale_kg')
                ->groupBy('type')
                ->get();

            // Formater les résultats avec les libellés en français
            $resultats = $quantites->map(function ($item) {
                return [
                    'type' => $item->type,
                    'type_libelle' => $this->getTypeLibelle($item->type),
                    'quantite_kg' => (float) $item->quantite_totale_kg,
                    'quantite_formatee' => number_format($item->quantite_totale_kg, 2, ',', ' ') . ' kg'
                ];
            });

            // Calculer le total général
            $totalGeneral = $quantites->sum('quantite_totale_kg');

            return response()->json([
                'statut' => 'succes',
                'message' => 'Quantités non livrées récupérées avec succès',
                'donnees' => [
                    'quantites_par_type' => $resultats,
                    'total_general' => [
                        'quantite_kg' => (float) $totalGeneral,
                        'quantite_formatee' => number_format($totalGeneral, 2, ',', ' ') . ' kg'
                    ],
                    'nombre_types' => $quantites->count(),
                    'date_mise_a_jour' => now()->format('d/m/Y H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'erreur',
                'message' => 'Erreur lors de la récupération des quantités non livrées',
                'erreur' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtenir les détails des réceptions non livrées par type
     */
    public function detailsNonLivreesParType($type)
    {
        try {
            // Valider le type
            $typesValides = [Reception::TYPE_CLOUS, Reception::TYPE_GRIFFES, Reception::TYPE_FEUILLES];
            
            if (!in_array($type, $typesValides)) {
                return response()->json([
                    'statut' => 'erreur',
                    'message' => 'Type de matière première non valide'
                ], 422);
            }

            $receptions = Reception::where('type', $type)
                ->where('statut', '!=', Reception::STATUT_LIVRE)
                ->with(['facturation', 'impaye', 'ficheLivraison'])
                ->orderBy('dateHeure', 'desc')
                ->get();

            $quantiteTotale = $receptions->sum('poids_net');

            return response()->json([
                'statut' => 'succes',
                'message' => 'Détails des réceptions non livrées pour ' . $this->getTypeLibelle($type),
                'donnees' => [
                    'type' => $type,
                    'type_libelle' => $this->getTypeLibelle($type),
                    'quantite_totale_kg' => (float) $quantiteTotale,
                    'quantite_totale_formatee' => number_format($quantiteTotale, 2, ',', ' ') . ' kg',
                    'nombre_receptions' => $receptions->count(),
                    'receptions' => $receptions->map(function ($reception) {
                        return [
                            'id' => $reception->id,
                            'id_fiscale' => $reception->id_fiscale,
                            'date_reception' => $reception->dateHeure,
                            'designation' => $reception->designation,
                            'provenance' => $reception->provenance,
                            'poids_net' => (float) $reception->poids_net,
                            'statut' => $reception->statut,
                            'fournisseur' => $reception->nom_fournisseur . ' ' . $reception->prenom_fournisseur,
                            'a_facturation' => !is_null($reception->facturation),
                            'a_impaye' => !is_null($reception->impaye),
                            'a_fiche_livraison' => !is_null($reception->ficheLivraison)
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'erreur',
                'message' => 'Erreur lors de la récupération des détails',
                'erreur' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques complètes des matières premières
     */
    public function statistiquesCompletes()
    {
        try {
            // Statistiques par statut
            $statsParStatut = Reception::selectRaw('statut, COUNT(*) as nombre, SUM(poids_net) as quantite_kg')
                ->groupBy('statut')
                ->get();

            // Statistiques par type (tous statuts)
            $statsParType = Reception::selectRaw('type, COUNT(*) as nombre, SUM(poids_net) as quantite_kg')
                ->groupBy('type')
                ->get();

            // Statistiques par type (non livrées seulement)
            $statsNonLivrees = Reception::where('statut', '!=', Reception::STATUT_LIVRE)
                ->selectRaw('type, COUNT(*) as nombre, SUM(poids_net) as quantite_kg')
                ->groupBy('type')
                ->get();

            // Totaux généraux
            $totalReceptions = Reception::count();
            $totalQuantite = Reception::sum('poids_net');
            $totalNonLivree = Reception::where('statut', '!=', Reception::STATUT_LIVRE)->sum('poids_net');
            $tauxLivraison = $totalQuantite > 0 ? round(($totalQuantite - $totalNonLivree) / $totalQuantite * 100, 2) : 0;

            return response()->json([
                'statut' => 'succes',
                'message' => 'Statistiques complètes récupérées avec succès',
                'donnees' => [
                    'totaux' => [
                        'total_receptions' => $totalReceptions,
                        'total_quantite_kg' => (float) $totalQuantite,
                        'total_quantite_non_livree_kg' => (float) $totalNonLivree,
                        'taux_livraison' => $tauxLivraison . '%'
                    ],
                    'par_statut' => $statsParStatut->map(function ($item) {
                        return [
                            'statut' => $item->statut,
                            'nombre_receptions' => $item->nombre,
                            'quantite_kg' => (float) $item->quantite_kg
                        ];
                    }),
                    'par_type' => $statsParType->map(function ($item) {
                        return [
                            'type' => $item->type,
                            'type_libelle' => $this->getTypeLibelle($item->type),
                            'nombre_receptions' => $item->nombre,
                            'quantite_kg' => (float) $item->quantite_kg
                        ];
                    }),
                    'non_livrees_par_type' => $statsNonLivrees->map(function ($item) {
                        return [
                            'type' => $item->type,
                            'type_libelle' => $this->getTypeLibelle($item->type),
                            'nombre_receptions' => $item->nombre,
                            'quantite_kg' => (float) $item->quantite_kg,
                            'quantite_formatee' => number_format($item->quantite_kg, 2, ',', ' ') . ' kg'
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'erreur',
                'message' => 'Erreur lors de la récupération des statistiques',
                'erreur' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper pour obtenir le libellé du type
     */
    private function getTypeLibelle($type)
    {
        switch ($type) {
            case Reception::TYPE_CLOUS:
                return 'Clous';
            case Reception::TYPE_GRIFFES:
                return 'Griffes';
            case Reception::TYPE_FEUILLES:
                return 'Feuilles';
            default:
                return $type;
        }
    }
}