<?php
// routes/api.php
use App\Http\Controllers\MatierePremiere\ConfirmationLivraisonController;
use App\Http\Controllers\MatierePremiere\ReceptionController;
use App\Http\Controllers\MatierePremiere\FacturationController;
use App\Http\Controllers\MatierePremiere\ImpayeController;
use App\Http\Controllers\MatierePremiere\FicheLivraisonController;
use App\Http\Controllers\MatierePremiere\StatistiqueController;
use Illuminate\Support\Facades\Route;


// Routes pour les PV de réception
Route::get('receptions', [ReceptionController::class, 'index']);
Route::post('receptions', [ReceptionController::class, 'store']);
Route::get('receptions/{id}', [ReceptionController::class, 'show']);
Route::put('receptions/{id}', [ReceptionController::class, 'update']);
Route::delete('receptions/{id}', [ReceptionController::class, 'destroy']);
Route::get('receptions/{id}/transitions', [ReceptionController::class, 'getTransitions']);

// Routes pour facturation
Route::get('facturations', [FacturationController::class, 'index']);
Route::post('facturations', [FacturationController::class, 'store']);
Route::get('facturations/{id}', [FacturationController::class, 'show']);
Route::put('facturations/{id}', [FacturationController::class, 'update']);
Route::delete('facturations/{id}', [FacturationController::class, 'destroy']);
Route::get('facturations/check-reception/{receptionId}', [FacturationController::class, 'checkReception']);
Route::get('facturations/status/{status}', [FacturationController::class, 'getByStatus']);

// Routes pour les impayés 
Route::get('impayes', [ImpayeController::class, 'index']);
Route::post('impayes', [ImpayeController::class, 'store']); // Créer ajustement
Route::put('impayes/{id}', [ImpayeController::class, 'update']); // Modifier ajustement
Route::get('impayes/{id}', [ImpayeController::class, 'show']); // Voir un ajustement
Route::get('impayes/check-reception/{receptionId}', [ImpayeController::class, 'checkReception']); // Vérifier existence

// Routes pour les fiches de livraison
Route::get('fiche-livraisons', [FicheLivraisonController::class, 'index']);
Route::post('fiche-livraisons', [FicheLivraisonController::class, 'store']);
Route::get('fiche-livraisons/{id}', [FicheLivraisonController::class, 'show']);
Route::put('fiche-livraisons/{id}', [FicheLivraisonController::class, 'update']);
Route::delete('fiche-livraisons/{id}', [FicheLivraisonController::class, 'destroy']);


//route pour livreeee
Route::post('receptions/{id}/livrer', [ConfirmationLivraisonController::class, 'marquerCommeLivre']);
Route::post('receptions/livrer-multiple', [ConfirmationLivraisonController::class, 'marquerMultipleCommeLivre']);


// Routes pour les statistiques
Route::get('statistiques/quantites-non-livrees', [StatistiqueController::class, 'quantitesNonLivrees']);
Route::get('statistiques/details-non-livrees/{type}', [StatistiqueController::class, 'detailsNonLivreesParType']);
Route::get('statistiques/completes', [StatistiqueController::class, 'statistiquesCompletes']);

