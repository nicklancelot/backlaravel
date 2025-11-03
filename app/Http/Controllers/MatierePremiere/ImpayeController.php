<?php

namespace App\Http\Controllers\MatierePremiere;

use App\Http\Controllers\Controller;
use App\Models\MatierePremiere\Impaye;
use App\Models\MatierePremiere\Reception;
use Illuminate\Http\Request;

class ImpayeController extends Controller
{
    
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

            // Vérifier s'il existe déjà un impayé pour cette réception
            $existingImpaye = Impaye::where('reception_id', $request->reception_id)->first();
            
            if ($existingImpaye) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Un ajustement de solde existe déjà pour cette réception. Utilisez la modification pour ajuster.'
                ], 422);
            }

            if (!$reception->canTransitionToImpaye()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transition non autorisée depuis le statut actuel'
                ], 422);
            }

            $validator = validator($request->all(), [
                'reception_id' => 'required|exists:receptions,id',
                'date_paiement' => 'required|date',
                'numero_facture' => 'required|string',
                'designation' => 'required|string|max:255',
                'encaissement' => 'required|string|max:255',
                'prix_unitaire' => 'required|numeric|min:0',
                'quantite' => 'required|numeric|min:0',
                'montant_paye' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calcul avec arrondi pour Ariary (unités entières)
            $prixTotalCalcule = $request->prix_unitaire * $request->quantite;
            $prixTotalArrondi = round($prixTotalCalcule, 0); // Arrondi à l'unité d'Ariary
            $montantPayeArrondi = round($request->montant_paye, 0); // Arrondi à l'unité d'Ariary

            // Validation : montant payé ne peut pas dépasser le prix total
            if ($montantPayeArrondi > $prixTotalArrondi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le montant payé ne peut pas dépasser le prix total',
                    'errors' => [
                        'montant_paye' => [
                            'Le montant payé ('.number_format($montantPayeArrondi, 0, ',', ' ').' Ar) dépasse le prix total ('.number_format($prixTotalArrondi, 0, ',', ' ').' Ar)'
                        ]
                    ]
                ], 422);
            }

            $impaye = Impaye::create($request->all());

            // Condition de paiement complet avec tolérance 1 Ariary
            $statut = (abs($impaye->reste_a_payer) <= 1) 
                ? Reception::STATUT_PAYE 
                : Reception::STATUT_PAIEMENT_INCOMPLET;

            $reception->update(['statut' => $statut]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ajustement du solde enregistré avec succès',
                'data' => $impaye,
                'calculs' => [
                    'prix_total' => $impaye->prix_total,
                    'reste_a_payer' => $impaye->reste_a_payer,
                    'solde_impaye' => $impaye->solde_impaye,
                    'paiement_complet' => $impaye->paiement_complet
                ],
                'statut_reception' => $statut
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'ajustement du solde',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Modifier un ajustement de solde existant
     */
    public function update(Request $request, $id)
    {
        try {
            $impaye = Impaye::with('reception')->find($id);
            
            if (!$impaye) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ajustement de solde non trouvé'
                ], 404);
            }

            $validator = validator($request->all(), [
                'date_paiement' => 'sometimes|date',
                'numero_facture' => 'sometimes|string',
                'designation' => 'sometimes|string|max:255',
                'encaissement' => 'sometimes|string|max:255',
                'prix_unitaire' => 'sometimes|numeric|min:0',
                'quantite' => 'sometimes|numeric|min:0',
                'montant_paye' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculer les valeurs pour validation avec arrondi Ariary
            $prixUnitaire = $request->has('prix_unitaire') ? $request->prix_unitaire : $impaye->prix_unitaire;
            $quantite = $request->has('quantite') ? $request->quantite : $impaye->quantite;
            $montantPaye = $request->has('montant_paye') ? $request->montant_paye : $impaye->montant_paye;
            
            $prixTotalCalcule = $prixUnitaire * $quantite;
            $prixTotalArrondi = round($prixTotalCalcule, 0); // Arrondi à l'unité d'Ariary
            $montantPayeArrondi = round($montantPaye, 0); // Arrondi à l'unité d'Ariary

            // Validation : montant payé ne peut pas dépasser le prix total
            if ($montantPayeArrondi > $prixTotalArrondi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Le montant payé ne peut pas dépasser le prix total',
                    'errors' => [
                        'montant_paye' => [
                            'Le montant payé ('.number_format($montantPayeArrondi, 0, ',', ' ').' Ar) dépasse le prix total ('.number_format($prixTotalArrondi, 0, ',', ' ').' Ar)'
                        ]
                    ]
                ], 422);
            }

            $impaye->update($request->all());

            // Recalculer et mettre à jour le statut de la réception avec tolérance 1 Ariary
            $statut = (abs($impaye->reste_a_payer) <= 1) 
                ? Reception::STATUT_PAYE 
                : Reception::STATUT_PAIEMENT_INCOMPLET;

            $impaye->reception->update(['statut' => $statut]);

            return response()->json([
                'status' => 'success',
                'message' => 'Ajustement du solde modifié avec succès',
                'data' => $impaye,
                'calculs' => [
                    'prix_total' => $impaye->prix_total,
                    'reste_a_payer' => $impaye->reste_a_payer,
                    'solde_impaye' => $impaye->solde_impaye,
                    'paiement_complet' => $impaye->paiement_complet
                ],
                'statut_reception' => $statut
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la modification de l\'ajustement',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Récupérer un ajustement de solde spécifique
     */
    public function show($id)
    {
        try {
            $impaye = Impaye::with('reception')->find($id);
            
            if (!$impaye) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ajustement de solde non trouvé'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $impaye,
                'calculs' => [
                    'prix_total' => $impaye->prix_total,
                    'reste_a_payer' => $impaye->reste_a_payer,
                    'solde_impaye' => $impaye->solde_impaye,
                    'paiement_complet' => $impaye->paiement_complet
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de l\'ajustement'
            ], 500);
        }
    }

    /**
     * Vérifier si un ajustement existe pour une réception
     */
    public function checkReception($receptionId)
    {
        try {
            $impaye = Impaye::where('reception_id', $receptionId)->first();
            
            return response()->json([
                'status' => 'success',
                'exists' => !is_null($impaye),
                'data' => $impaye,
                'calculs' => $impaye ? [
                    'prix_total' => $impaye->prix_total,
                    'reste_a_payer' => $impaye->reste_a_payer,
                    'solde_impaye' => $impaye->solde_impaye,
                    'paiement_complet' => $impaye->paiement_complet
                ] : null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification'
            ], 500);
        }
    }

    /**
     * Lister tous les ajustements de solde
     */
    public function index()
    {
        try {
            $impayes = Impaye::with('reception')->get();
            return response()->json([
                'status' => 'success',
                'data' => $impayes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des ajustements de solde'
            ], 500);
        }
    }
}