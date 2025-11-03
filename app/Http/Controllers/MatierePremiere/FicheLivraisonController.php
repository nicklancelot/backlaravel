<?php
namespace App\Http\Controllers\MatierePremiere;

use App\Http\Controllers\Controller;
use App\Models\MatierePremiere\FicheLivraison;
use App\Models\MatierePremiere\Reception;
use Illuminate\Http\Request;

class FicheLivraisonController extends Controller
{
    public function index()
    {
        try {
            $fiches = FicheLivraison::with('reception')->get();
            return response()->json([
                'status' => 'success',
                'data' => $fiches
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des fiches de livraison'
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

            if (!$reception->canTransitionToFicheLivraison()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transition non autorisée depuis le statut actuel'
                ], 422);
            }

            $validator = validator($request->all(), [
                'reception_id' => 'required|exists:receptions,id',
                'date_livraison' => 'required|date',
                'lieu_depart' => 'required|string|max:255',
                'destination' => 'required|string|max:255',
                'livreur_nom' => 'required|string|max:255',
                'livreur_prenom' => 'required|string|max:255',
                'livreur_telephone' => 'required|string|max:255',
                'livreur_vehicule' => 'required|string|max:255',
                'destinateur_nom' => 'required|string|max:255',
                'destinateur_prenom' => 'required|string|max:255',
                'destinateur_fonction' => 'required|string|max:255',
                'destinateur_contact' => 'required|string|max:255',
                'type_produit' => 'required|in:Feuille,Clous,Griffes',
                'poids_net' => 'required|numeric|min:0',
                'ristourne_regionale' => 'sometimes|numeric|min:0',
                'ristourne_communale' => 'sometimes|numeric|min:0',
                'prix_unitaire' => 'required|numeric|min:0',
                'quantite_a_livrer' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fiche = FicheLivraison::create($request->all());

            $reception->update(['statut' => Reception::STATUT_EN_ATTENTE]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fiche de livraison créée avec succès',
                'data' => $fiche
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de la fiche de livraison'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $fiche = FicheLivraison::with('reception')->find($id);
            
            if (!$fiche) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fiche de livraison non trouvée'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $fiche
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de la fiche de livraison'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $fiche = FicheLivraison::find($id);
            
            if (!$fiche) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fiche de livraison non trouvée'
                ], 404);
            }

            $validator = validator($request->all(), [
                'date_livraison' => 'sometimes|date',
                'lieu_depart' => 'sometimes|string|max:255',
                'destination' => 'sometimes|string|max:255',
                'livreur_nom' => 'sometimes|string|max:255',
                'livreur_prenom' => 'sometimes|string|max:255',
                'livreur_telephone' => 'sometimes|string|max:255',
                'livreur_vehicule' => 'sometimes|string|max:255',
                'destinateur_nom' => 'sometimes|string|max:255',
                'destinateur_prenom' => 'sometimes|string|max:255',
                'destinateur_fonction' => 'sometimes|string|max:255',
                'destinateur_contact' => 'sometimes|string|max:255',
                'type_produit' => 'sometimes|in:Feuille,Clous,Griffes',
                'poids_net' => 'sometimes|numeric|min:0',
                'ristourne_regionale' => 'sometimes|numeric|min:0',
                'ristourne_communale' => 'sometimes|numeric|min:0',
                'prix_unitaire' => 'sometimes|numeric|min:0',
                'quantite_a_livrer' => 'sometimes|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fiche->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Fiche de livraison modifiée avec succès',
                'data' => $fiche
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la modification de la fiche de livraison'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fiche = FicheLivraison::find($id);
            
            if (!$fiche) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fiche de livraison non trouvée'
                ], 404);
            }

            $reception = $fiche->reception;
            $reception->update(['statut' => Reception::STATUT_PAYE]);

            $fiche->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Fiche de livraison supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression de la fiche de livraison'
            ], 500);
        }
    }
}