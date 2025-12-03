<?php

namespace App\Policies;

use App\Models\OrdenTrabajo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrdenTrabajoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver_ordenes');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('ver_ordenes');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('crear_ordenes');
    }

    /**
     * Determine whether the user can update the model (general update).
     */
    public function update(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        // Generally restricting updates to those who can create, or specific roles logic
        return $user->can('crear_ordenes');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('eliminar_ordenes');
    }

    /**
     * Determine whether the user can manage photos.
     */
    public function managePhotos(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_fotos');
    }

    /**
     * Determine whether the user can manage diagnosis.
     */
    public function manageDiagnosis(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_diagnostico');
    }

    /**
     * Determine whether the user can manage quotes.
     */
    public function manageQuotes(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_cotizaciones');
    }

    /**
     * Determine whether the user can manage purchase orders.
     */
    public function managePurchaseOrders(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_compras');
    }

    /**
     * Determine whether the user can manage spare parts delivery.
     */
    public function manageSpareParts(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_repuestos');
    }

    /**
     * Determine whether the user can manage execution.
     */
    public function manageExecution(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_ejecucion');
    }

    /**
     * Determine whether the user can manage invoicing.
     */
    public function manageInvoicing(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('gestionar_facturacion');
    }
    
    /**
     * Determine whether the user can close the order.
     */
    public function closeOrder(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        return $user->can('cerrar_orden');
    }

    /**
     * Determine whether the user can advance the stage.
     * Checks if the user has the permission required for the CURRENT stage to move forward.
     */
    public function advanceStage(User $user, OrdenTrabajo $ordenTrabajo): bool
    {
        $currentStage = $ordenTrabajo->etapa_actual;

        return match ($currentStage) {
            'Toma de fotografías' => $user->can('gestionar_fotos'),
            'Diagnóstico' => $user->can('gestionar_diagnostico'),
            'Cotizaciones' => $user->can('gestionar_cotizaciones'),
            'Órdenes de Compra' => $user->can('gestionar_compras'),
            'Entrega de repuestos' => $user->can('gestionar_repuestos'),
            'Ejecución' => $user->can('gestionar_ejecucion'),
            'Facturación' => $user->can('gestionar_facturacion'),
            'Finalizado' => $user->can('cerrar_orden'), // Should usually be already closed, but logically admin might reopen
            default => false,
        };
    }
}
