<?php

namespace App\Http\Controllers\MatierePremiere;

use App\Http\Controllers\Controller;
use App\Models\MatierePremiere\Reception;
use Illuminate\Http\Request;

class ConfirmationLivraisonController extends Controller
{
     public function marquerCommeLivre($idReception)
    {
        try {
            $reception = Reception::find($idReception);
            
            if (!$reception) {
                return response()->json([
                    'statut' => 'erreur',
                    'message' => 'Réception non trouvée'
                ], 404);
            }

            // Marquer directement comme livré
            $reception->marquerCommeLivre();

            return response()->json([
                'statut' => 'succes',
                'message' => 'Réception marquée comme livrée',
                'donnees' => $reception
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'erreur',
                'message' => 'Erreur lors du marquage'
            ], 500);
        }
    }

    /**
     * Marquer plusieurs réceptions comme livrées
     */
    public function marquerMultipleCommeLivre(Request $requete)
    {
        try {
            $idsReceptions = $requete->ids_receptions;

            $resultats = [];
            foreach ($idsReceptions as $id) {
                $reception = Reception::find($id);
                if ($reception) {
                    $reception->marquerCommeLivre();
                    $resultats[] = $reception;
                }
            }

            return response()->json([
                'statut' => 'succes',
                'message' => count($resultats) . ' réception(s) marquée(s) comme livrée(s)',
                'donnees' => $resultats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'erreur',
                'message' => 'Erreur lors du marquage multiple'
            ], 500);
        }
    }
}
