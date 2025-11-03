<?php

namespace App\Http\Controllers\MatierePremiere;

use App\Http\Controllers\Controller;
use App\Models\MatierePremiere\Facturation;
use App\Models\MatierePremiere\Reception;
use Illuminate\Http\Request;

class FacturationController extends Controller
{
    
    public function index()
    {
        try {
            $facturations = Facturation::with('reception')->get();
            return response()->json([
                'status' => 'success',
                'data' => $facturations
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des facturations',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $reception = Reception::find($request->reception_id);
            
            if (!$reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PV de réception non trouvé'
                ], 404);
            }

            if (!$reception->canTransitionToFacturation()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transition non autorisée depuis le statut actuel'
                ], 422);
            }

            $validator = validator($request->all(), [
                'reception_id' => 'required|exists:receptions,id',
                'date_paiement' => 'required|date',
                'numero_facture' => 'required|string|unique:facturations,numero_facture',
                'designation' => 'required|string|max:255',
                'encaissement' => 'required|string|max:255',
                'prix_unitaire' => 'required|numeric|min:0',
                'quantite' => 'required|numeric|min:0',
                'paiement_avance' => 'required|numeric|min:0',
                'montant_paye' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calcul du prix total avec arrondi à 2 décimales
            $prixTotalCalcule = $request->prix_unitaire * $request->quantite;
            $prixTotalArrondi = round($prixTotalCalcule, 2);
            $montantPayeArrondi = round($request->montant_paye, 2);

            // Validation : montant payé ne peut pas dépasser le prix total
            if ($montantPayeArrondi > $prixTotalArrondi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le montant payé ne peut pas dépasser le prix total',
                    'errors' => [
                        'montant_paye' => [
                            'Le montant payé ('.number_format($montantPayeArrondi, 2).') dépasse le prix total ('.number_format($prixTotalArrondi, 2).')',
                            'Détail calcul: '.$request->prix_unitaire.' × '.$request->quantite.' = '.number_format($prixTotalCalcule, 6)
                        ]
                    ]
                ], 422);
            }

            // Validation : paiement d'avance ne peut pas dépasser le montant payé
            $paiementAvanceArrondi = round($request->paiement_avance, 2);
            if ($paiementAvanceArrondi > $montantPayeArrondi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le paiement d\'avance ne peut pas dépasser le montant payé',
                    'errors' => [
                        'paiement_avance' => [
                            'Le paiement d\'avance ('.number_format($paiementAvanceArrondi, 2).') dépasse le montant payé ('.number_format($montantPayeArrondi, 2).')'
                        ]
                    ]
                ], 422);
            }

            // Création avec calcul automatique du prix_total dans le modèle
            $facturation = Facturation::create([
                'reception_id' => $request->reception_id,
                'date_paiement' => $request->date_paiement,
                'numero_facture' => $request->numero_facture,
                'designation' => $request->designation,
                'encaissement' => $request->encaissement,
                'prix_unitaire' => $request->prix_unitaire,
                'quantite' => $request->quantite,
                'paiement_avance' => $request->paiement_avance,
                'montant_paye' => $request->montant_paye,
                // prix_total sera calculé automatiquement dans le modèle via l'event saving
            ]);
           
            // Mettre à jour le statut selon le solde restant
            $statut = ($facturation->reste_a_payer <= 0) 
                ? Reception::STATUT_PAYE 
                : Reception::STATUT_PAIEMENT_INCOMPLET;

            $reception->update(['statut' => $statut]);

            return response()->json([
                'status' => 'success',
                'message' => 'Facturation créée avec succès',
                'data' => $facturation,
                'calculs' => [
                    'prix_total' => $facturation->prix_total,
                    'reste_a_payer' => $facturation->reste_a_payer,
                    'solde_impaye' => $facturation->solde_impaye,
                    'paiement_complet' => $facturation->paiement_complet,
                    'pourcentage_paye' => $facturation->pourcentage_paye
                ],
                'statut_reception' => $statut
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la facturation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Afficher une facturation spécifique
     */
    public function show($id)
    {
        try {
            $facturation = Facturation::with('reception')->find($id);
            
            if (!$facturation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Facturation non trouvée'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $facturation,
                'calculs' => [
                    'prix_total' => $facturation->prix_total,
                    'reste_a_payer' => $facturation->reste_a_payer,
                    'solde_impaye' => $facturation->solde_impaye,
                    'paiement_complet' => $facturation->paiement_complet,
                    'pourcentage_paye' => $facturation->pourcentage_paye
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la facturation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Modifier une facturation existante
     */
    public function update(Request $request, $id)
    {
        try {
            $facturation = Facturation::with('reception')->find($id);
            
            if (!$facturation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Facturation non trouvée'
                ], 404);
            }

            $validator = validator($request->all(), [
                'date_paiement' => 'sometimes|date',
                'numero_facture' => 'sometimes|string|unique:facturations,numero_facture,' . $id,
                'designation' => 'sometimes|string|max:255',
                'encaissement' => 'sometimes|string|max:255',
                'prix_unitaire' => 'sometimes|numeric|min:0',
                'quantite' => 'sometimes|numeric|min:0',
                'paiement_avance' => 'sometimes|numeric|min:0',
                'montant_paye' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculer les valeurs pour validation avec arrondi
            $prixUnitaire = $request->has('prix_unitaire') ? $request->prix_unitaire : $facturation->prix_unitaire;
            $quantite = $request->has('quantite') ? $request->quantite : $facturation->quantite;
            $paiementAvance = $request->has('paiement_avance') ? $request->paiement_avance : $facturation->paiement_avance;
            $montantPaye = $request->has('montant_paye') ? $request->montant_paye : $facturation->montant_paye;
            
            $prixTotalCalcule = $prixUnitaire * $quantite;
            $prixTotalArrondi = round($prixTotalCalcule, 2);
            $montantPayeArrondi = round($montantPaye, 2);
            $paiementAvanceArrondi = round($paiementAvance, 2);

            // Validation : montant payé ne peut pas dépasser le prix total
            if ($montantPayeArrondi > $prixTotalArrondi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le montant payé ne peut pas dépasser le prix total',
                    'errors' => [
                        'montant_paye' => [
                            'Le montant payé ('.number_format($montantPayeArrondi, 2).') dépasse le prix total ('.number_format($prixTotalArrondi, 2).')',
                            'Détail calcul: '.$prixUnitaire.' × '.$quantite.' = '.number_format($prixTotalCalcule, 6)
                        ]
                    ]
                ], 422);
            }

            // Validation : paiement d'avance ne peut pas dépasser le montant payé
            if ($paiementAvanceArrondi > $montantPayeArrondi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le paiement d\'avance ne peut pas dépasser le montant payé',
                    'errors' => [
                        'paiement_avance' => [
                            'Le paiement d\'avance ('.number_format($paiementAvanceArrondi, 2).') dépasse le montant payé ('.number_format($montantPayeArrondi, 2).')'
                        ]
                    ]
                ], 422);
            }

            // Mise à jour - le prix_total sera recalculé automatiquement dans le modèle
            $facturation->update($request->all());

            // Recalculer et mettre à jour le statut de la réception
            $statut = ($facturation->reste_a_payer <= 0) 
                ? Reception::STATUT_PAYE 
                : Reception::STATUT_PAIEMENT_INCOMPLET;

            $facturation->reception->update(['statut' => $statut]);

            return response()->json([
                'status' => 'success',
                'message' => 'Facturation modifiée avec succès',
                'data' => $facturation,
                'calculs' => [
                    'prix_total' => $facturation->prix_total,
                    'reste_a_payer' => $facturation->reste_a_payer,
                    'solde_impaye' => $facturation->solde_impaye,
                    'paiement_complet' => $facturation->paiement_complet,
                    'pourcentage_paye' => $facturation->pourcentage_paye
                ],
                'statut_reception' => $statut
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la modification de la facturation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Supprimer une facturation
     */
    public function destroy($id)
    {
        try {
            $facturation = Facturation::with('reception')->find($id);
            
            if (!$facturation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Facturation non trouvée'
                ], 404);
            }

            $reception = $facturation->reception;
            
            $facturation->delete();

            // Remettre le statut de la réception à "Non payé"
            $reception->update(['statut' => Reception::STATUT_NON_PAYE]);

            return response()->json([
                'status' => 'success',
                'message' => 'Facturation supprimée avec succès',
                'statut_reception' => Reception::STATUT_NON_PAYE
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la facturation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Vérifier si une facturation existe pour une réception
     */
    public function checkReception($receptionId)
    {
        try {
            $facturation = Facturation::with('reception')->where('reception_id', $receptionId)->first();
            
            return response()->json([
                'status' => 'success',
                'exists' => !is_null($facturation),
                'data' => $facturation,
                'calculs' => $facturation ? [
                    'prix_total' => $facturation->prix_total,
                    'reste_a_payer' => $facturation->reste_a_payer,
                    'solde_impaye' => $facturation->solde_impaye,
                    'paiement_complet' => $facturation->paiement_complet,
                    'pourcentage_paye' => $facturation->pourcentage_paye
                ] : null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Récupérer les facturations par statut
     */
    public function getByStatus($status)
    {
        try {
            $validStatuses = [Reception::STATUT_PAYE, Reception::STATUT_PAIEMENT_INCOMPLET];
            
            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Statut non valide'
                ], 422);
            }

            $facturations = Facturation::whereHas('reception', function($query) use ($status) {
                $query->where('statut', $status);
            })->with('reception')->get();

            return response()->json([
                'status' => 'success',
                'data' => $facturations,
                'count' => $facturations->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des facturations par statut',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}