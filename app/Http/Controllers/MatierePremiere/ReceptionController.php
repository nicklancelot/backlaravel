<?php

namespace App\Http\Controllers\MatierePremiere;

use App\Http\Controllers\Controller;
use App\Models\MatierePremiere\Reception;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    // Lister tous les PV de réception
    public function index()
    {
        try {
            $receptions = Reception::all();
            return response()->json([
                'status' => 'success',
                'data' => $receptions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des données'
            ], 500);
        }
    }

    // Enregistrer un nouveau PV
    public function store(Request $request)
    {
        try {
            // Validation de base
            $validator = validator($request->all(), [
                'type' => 'required|in:FG,GG,CG',
                'dateHeure' => 'required|date',
                'designation' => 'required|string|max:255',
                'provenance' => 'required|string|max:255',
                'nom_fournisseur' => 'required|string|max:255',
                'prenom_fournisseur' => 'required|string|max:255',
                'id_fiscale' => 'required|string|max:255',
                'localisation' => 'required|string|max:255',
                'contact' => 'required|string|max:255',
                'poids_brut' => 'required|numeric|min:0',
                'poids_net' => 'required|numeric|min:0',
                'unite' => 'sometimes|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validation conditionnelle selon le type
            $typeSpecificErrors = $this->validateTypeSpecificFields($request);
            if (!empty($typeSpecificErrors)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $typeSpecificErrors
                ], 422);
            }

            $reception = Reception::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'PV de réception créé avec succès',
                'data' => $reception
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du PV'
            ], 500);
        }
    }

    // Afficher un PV spécifique
    public function show($id)
    {
        try {
            $reception = Reception::find($id);
            
            if (!$reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PV de réception non trouvé'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $reception
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du PV'
            ], 500);
        }
    }

    // Modifier un PV existant
    public function update(Request $request, $id)
    {
        try {
            $reception = Reception::find($id);
            
            if (!$reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PV de réception non trouvé'
                ], 404);
            }

            // Validation de base
            $validator = validator($request->all(), [
                'type' => 'sometimes|in:FG,GG,CG',
                'dateHeure' => 'sometimes|date',
                'designation' => 'sometimes|string|max:255',
                'provenance' => 'sometimes|string|max:255',
                'nom_fournisseur' => 'sometimes|string|max:255',
                'prenom_fournisseur' => 'sometimes|string|max:255',
                'id_fiscale' => 'sometimes|string|max:255',
                'localisation' => 'sometimes|string|max:255',
                'contact' => 'sometimes|string|max:255',
                'poids_brut' => 'sometimes|numeric|min:0',
                'poids_net' => 'sometimes|numeric|min:0',
                'unite' => 'sometimes|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validation conditionnelle si le type est modifié
            if ($request->has('type')) {
                $typeSpecificErrors = $this->validateTypeSpecificFields($request);
                if (!empty($typeSpecificErrors)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed',
                        'errors' => $typeSpecificErrors
                    ], 422);
                }
            }

            $reception->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'PV de réception modifié avec succès',
                'data' => $reception
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la modification du PV'
            ], 500);
        }
    }

    // Supprimer un PV
    public function destroy($id)
    {
        try {
            $reception = Reception::find($id);
            
            if (!$reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PV de réception non trouvé'
                ], 404);
            }

            $reception->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'PV de réception supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du PV'
            ], 500);
        }
    }

    // Validation conditionnelle selon le type
    private function validateTypeSpecificFields(Request $request)
    {
        $type = $request->type;
        $errors = [];

        switch ($type) {
            case 'FG': // Clous
                if (!$request->has('poids_packaging') || $request->poids_packaging === null) {
                    $errors['poids_packaging'] = ['Le poids packaging est requis pour les Clous (FG)'];
                }
                if (!$request->has('taux_dessiccation') || $request->taux_dessiccation === null) {
                    $errors['taux_dessiccation'] = ['Le taux de dessiccation est requis pour les Clous (FG)'];
                }
                if (!$request->has('taux_humidite_fg') || $request->taux_humidite_fg === null) {
                    $errors['taux_humidite_fg'] = ['Le taux d\'humidité est requis pour les Clous (FG)'];
                }
                break;
            
            case 'GG': // Griffes
                if (!$request->has('poids_agreé') || $request->poids_agreé === null) {
                    $errors['poids_agreé'] = ['Le poids agréé est requis pour les Griffes (GG)'];
                }
                if (!$request->has('densite') || $request->densite === null) {
                    $errors['densite'] = ['La densité est requise pour les Griffes (GG)'];
                }
                break;
            
            case 'CG': // Feuilles
                if (!$request->has('taux_humidite_cg') || $request->taux_humidite_cg === null) {
                    $errors['taux_humidite_cg'] = ['Le taux d\'humidité est requis pour les Feuilles (CG)'];
                }
                break;
        }

        return $errors;
    }

     public function getTransitions($id)
    {
        try {
            $reception = Reception::find($id);
            
            if (!$reception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PV de réception non trouvé'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'current_status' => $reception->statut,
                    'available_transitions' => $reception->getAvailableTransitions(),
                    'status_details' => $this->getStatusDetails($reception->statut)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des transitions'
            ], 500);
        }
    }

    // Détails supplémentaires selon le statut
    private function getStatusDetails($statut)
    {
        $details = [
            'statut' => $statut,
            'description' => '',
            'actions_possibles' => []
        ];

        switch ($statut) {
            case Reception::STATUT_NON_PAYE:
                $details['description'] = 'Le PV est en attente de paiement';
                $details['actions_possibles'] = [
                    'facturation' => 'Passer à la facturation (paiement complet)',
                    'impaye' => 'Marquer comme impayé (paiement partiel ou absence de paiement)'
                ];
                break;

            case Reception::STATUT_PAIEMENT_INCOMPLET:
                $details['description'] = 'Le paiement est incomplet';
                $details['actions_possibles'] = [
                    'impaye' => 'Ajuster le solde impayé'
                ];
                break;

            case Reception::STATUT_PAYE:
                $details['description'] = 'Le PV est payé, prêt pour la livraison';
                $details['actions_possibles'] = [
                    'fiche_livraison' => 'Créer une fiche de livraison'
                ];
                break;

            case Reception::STATUT_EN_ATTENTE:
                $details['description'] = 'En attente de livraison - aucune action possible';
                $details['actions_possibles'] = [];
                break;

            default:
                $details['description'] = 'Statut non reconnu';
                $details['actions_possibles'] = [];
                break;
        }

        return $details;
    }

    


}