<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\Vehiculo;
use App\Models\OrdenTrabajo;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\CotizacionOrdenTrabajo;
use App\Models\FacturaOrdenTrabajo;
use App\Models\OrdenCompraOrdenTrabajo;

class MigrateCarcoolDataSeeder extends Seeder
{
    /**
     * Conexión a la base de datos fuente (cctallerv3)
     */
    protected $sourceConnection;

    /**
     * Mapeos de IDs antiguos a nuevos
     */
    protected $marcaMap = [];
    protected $modeloMap = [];
    protected $clienteMap = [];
    protected $vehiculoMap = [];
    protected $ordenTrabajoMap = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando migración de datos desde cctallerv3...');

        // Configurar conexión fuente
        config(['database.connections.mysql_source' => config('database.connections.mysql')]);
        config(['database.connections.mysql_source.database' => 'cctallerv3']);
        $this->sourceConnection = DB::connection('mysql_source');

        try {
            DB::beginTransaction();

            // Orden de migración: marcas → modelos → clientes → vehículos → órdenes (con cotizaciones, facturas, OC)
            $this->migrateMarcas();
            $this->migrateModelos();
            $this->migrateClientes();
            $this->migrateVehiculos();
            $this->migrateOrdenesTrabajo();

            DB::commit();
            $this->command->info('✅ Migración completada exitosamente!');
            $this->displaySummary();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error en la migración: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Migrar marcas desde car_makes
     */
    protected function migrateMarcas(): void
    {
        $this->command->info('📦 Migrando marcas desde car_makes...');

        // Obtener marcas únicas (sin duplicados)
        $marcas = $this->sourceConnection->table('car_makes')
            ->select('make_name')
            ->distinct()
            ->get();

        $bar = $this->command->getOutput()->createProgressBar($marcas->count());
        $bar->start();

        foreach ($marcas as $marcaAntigua) {
            $nombre = trim($marcaAntigua->make_name);

            // Verificar si ya existe
            $marcaExistente = Marca::where('nombre', $nombre)->first();

            if (!$marcaExistente) {
                $marcaNueva = Marca::create([
                    'nombre' => $nombre,
                    'activo' => true,
                ]);
                $this->marcaMap[$nombre] = $marcaNueva->id;
            } else {
                $this->marcaMap[$nombre] = $marcaExistente->id;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("  ✓ Marcas procesadas: " . count($this->marcaMap));
    }

    /**
     * Migrar modelos desde car_models
     */
    protected function migrateModelos(): void
    {
        $this->command->info('📦 Migrando modelos desde car_models...');

        // Obtener modelos únicos (sin duplicados por make_name + model_name)
        $modelos = $this->sourceConnection->table('car_models')
            ->select('make_name', 'model_name')
            ->distinct()
            ->get();

        $bar = $this->command->getOutput()->createProgressBar($modelos->count());
        $bar->start();

        foreach ($modelos as $modeloAntiguo) {
            $makeName = trim($modeloAntiguo->make_name);
            $modelName = trim($modeloAntiguo->model_name);

            // Obtener marca ID (o crear si no existe)
            $marcaId = $this->marcaMap[$makeName] ?? null;
            if (!$marcaId) {
                $marca = Marca::firstOrCreate(['nombre' => $makeName], ['activo' => true]);
                $marcaId = $marca->id;
                $this->marcaMap[$makeName] = $marcaId;
            }

            // Verificar si el modelo ya existe para esta marca
            $modeloExistente = Modelo::where('marca_id', $marcaId)
                ->where('nombre', $modelName)
                ->first();

            $key = $makeName . '|' . $modelName;
            if (!$modeloExistente) {
                $modeloNuevo = Modelo::create([
                    'marca_id' => $marcaId,
                    'nombre' => $modelName,
                    'activo' => true,
                ]);
                $this->modeloMap[$key] = $modeloNuevo->id;
            } else {
                $this->modeloMap[$key] = $modeloExistente->id;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("  ✓ Modelos procesados: " . count($this->modeloMap));
    }

    /**
     * Migrar clientes desde customers
     */
    protected function migrateClientes(): void
    {
        $this->command->info('👥 Migrando clientes desde customers...');

        // Obtener mapeo de idtypes
        $idtypes = $this->sourceConnection->table('idtypes')->get()->keyBy('id');

        // Obtener clientes
        $clientes = $this->sourceConnection->table('customers')->get();

        $bar = $this->command->getOutput()->createProgressBar($clientes->count());
        $bar->start();

        $processed = 0;
        $skipped = 0;

        foreach ($clientes as $clienteAntiguo) {
            // Mapear tipo de identificación usando la tabla idtypes
            $tipoIdentificacion = 'Física'; // Default
            if ($clienteAntiguo->idtype && isset($idtypes[$clienteAntiguo->idtype])) {
                $typeName = $idtypes[$clienteAntiguo->idtype]->name;
                if (in_array($typeName, ['Física', 'Jurídica', 'DIMEX', 'NITE'])) {
                    $tipoIdentificacion = $typeName;
                }
            }

            $idnumber = $clienteAntiguo->idnumber;

            // Verificar si ya existe por numero_identificacion
            if ($idnumber) {
                $clienteExistente = Cliente::where('numero_identificacion', $idnumber)->first();
                if ($clienteExistente) {
                    $this->clienteMap[$clienteAntiguo->id] = $clienteExistente->id;
                    $skipped++;
                    $bar->advance();
                    continue;
                }
            }

            // Crear cliente
            try {
                $clienteNuevo = Cliente::create([
                    'tipo_identificacion' => $tipoIdentificacion,
                    'numero_identificacion' => $idnumber ?: 'SIN_ID_' . $clienteAntiguo->id,
                    'nombre' => $clienteAntiguo->name ?: '-',
                    'correo_electronico' => $clienteAntiguo->email ?: null,
                    'telefono' => $clienteAntiguo->phone ?: null,
                    'direccion' => $clienteAntiguo->address && $clienteAntiguo->address !== '-' ? $clienteAntiguo->address : null,
                ]);

                $this->clienteMap[$clienteAntiguo->id] = $clienteNuevo->id;
                $processed++;
            } catch (\Exception $e) {
                // Si hay error de duplicado en correo, buscar el existente
                if (strpos($e->getMessage(), 'correo_electronico') !== false && $clienteAntiguo->email) {
                    $clienteExistente = Cliente::where('correo_electronico', $clienteAntiguo->email)->first();
                    if ($clienteExistente) {
                        $this->clienteMap[$clienteAntiguo->id] = $clienteExistente->id;
                        $skipped++;
                    }
                } else {
                    throw $e;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("  ✓ Clientes creados: {$processed}, existentes: {$skipped}");
    }

    /**
     * Migrar vehículos desde vehicles
     */
    protected function migrateVehiculos(): void
    {
        $this->command->info('🚗 Migrando vehículos desde vehicles...');

        $vehiculos = $this->sourceConnection->table('vehicles')->get();

        $bar = $this->command->getOutput()->createProgressBar($vehiculos->count());
        $bar->start();

        $processed = 0;
        $skipped = 0;

        foreach ($vehiculos as $vehiculoAntiguo) {
            $placa = $vehiculoAntiguo->license_plate;

            // Verificar si ya existe por placa
            if ($placa) {
                $vehiculoExistente = Vehiculo::where('placa', $placa)->first();
                if ($vehiculoExistente) {
                    $this->vehiculoMap[$vehiculoAntiguo->id] = $vehiculoExistente->id;
                    $skipped++;
                    $bar->advance();
                    continue;
                }
            }

            // Obtener marca ID
            $makeName = trim($vehiculoAntiguo->make);
            $marcaId = $this->marcaMap[$makeName] ?? null;
            if (!$marcaId) {
                $marca = Marca::firstOrCreate(['nombre' => $makeName], ['activo' => true]);
                $marcaId = $marca->id;
                $this->marcaMap[$makeName] = $marcaId;
            }

            // Obtener modelo ID
            $modelName = trim($vehiculoAntiguo->model);
            $key = $makeName . '|' . $modelName;
            $modeloId = $this->modeloMap[$key] ?? null;
            if (!$modeloId) {
                $modelo = Modelo::firstOrCreate(
                    ['marca_id' => $marcaId, 'nombre' => $modelName],
                    ['activo' => true]
                );
                $modeloId = $modelo->id;
                $this->modeloMap[$key] = $modeloId;
            }

            // Convertir color (agregar # si no lo tiene)
            $color = $vehiculoAntiguo->color;
            if ($color && !str_starts_with($color, '#')) {
                $color = '#' . $color;
            }
            if (!$color || !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $color = '#000000';
            }

            // Generar placa única si no tiene
            if (!$placa) {
                $placa = 'SIN_PLACA_' . $vehiculoAntiguo->id;
                $counter = 1;
                while (Vehiculo::where('placa', $placa)->exists()) {
                    $placa = 'SIN_PLACA_' . $vehiculoAntiguo->id . '_' . $counter;
                    $counter++;
                }
            }

            try {
                $vehiculoNuevo = Vehiculo::create([
                    'placa' => $placa,
                    'marca_id' => $marcaId,
                    'modelo_id' => $modeloId,
                    'ano' => $vehiculoAntiguo->year ?: 2000,
                    'color' => $color,
                    'numero_chasis' => $vehiculoAntiguo->chassis_num ?: null,
                    'numero_unidad' => $vehiculoAntiguo->unit ?: null,
                ]);

                $this->vehiculoMap[$vehiculoAntiguo->id] = $vehiculoNuevo->id;
                $processed++;
            } catch (\Exception $e) {
                // Si hay error de duplicado en chasis, buscar el existente
                if (strpos($e->getMessage(), 'numero_chasis') !== false && $vehiculoAntiguo->chassis_num) {
                    $vehiculoExistente = Vehiculo::where('numero_chasis', $vehiculoAntiguo->chassis_num)->first();
                    if ($vehiculoExistente) {
                        $this->vehiculoMap[$vehiculoAntiguo->id] = $vehiculoExistente->id;
                        $skipped++;
                    }
                } else {
                    throw $e;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("  ✓ Vehículos creados: {$processed}, existentes: {$skipped}");
    }

    /**
     * Migrar órdenes de trabajo desde work_orders (solo status='closed' y work_type='Taller' o 'Domicilio')
     */
    protected function migrateOrdenesTrabajo(): void
    {
        $this->command->info('📋 Migrando órdenes de trabajo desde work_orders...');

        // Solo migrar las que tienen status='closed' y work_type en Taller o Domicilio
        $ordenes = $this->sourceConnection->table('work_orders')
            ->where('status', 'closed')
            ->whereIn('work_type', ['Taller', 'Domicilio'])
            ->get();

        $this->command->info("  Órdenes a migrar: " . $ordenes->count());

        // Obtener cotizaciones, facturas y ordenes de compra
        $cotizaciones = $this->sourceConnection->table('cotizaciones')->get()->groupBy('orden_id');
        $facturas = $this->sourceConnection->table('facturas')->get()->groupBy('orden_id');
        $ordenesCompra = $this->sourceConnection->table('ordenes_compra')->get()->groupBy('orden_id');

        $bar = $this->command->getOutput()->createProgressBar($ordenes->count());
        $bar->start();

        $processed = 0;
        $errors = 0;
        $cotizacionesCreadas = 0;
        $facturasCreadas = 0;
        $ordenesCompraCreadas = 0;

        foreach ($ordenes as $ordenAntigua) {
            // Verificar que existan las relaciones
            $clienteId = $this->clienteMap[$ordenAntigua->customer_id] ?? null;
            $vehiculoId = $this->vehiculoMap[$ordenAntigua->vehicle_id] ?? null;

            if (!$clienteId || $ordenAntigua->customer_id == 0) {
                $errors++;
                $bar->advance();
                continue;
            }

            if (!$vehiculoId) {
                $errors++;
                $bar->advance();
                continue;
            }

            // Mapear tipo de orden
            $tipoOrden = $ordenAntigua->work_type === 'Domicilio' ? 'Domicilio' : 'Taller';

            // Convertir km_actual de string a integer
            $kmActual = null;
            if ($ordenAntigua->kmactual) {
                $kmValue = (int) preg_replace('/[^0-9]/', '', $ordenAntigua->kmactual);
                if ($kmValue > 0 && $kmValue <= 999999999) {
                    $kmActual = $kmValue;
                }
            }

            // Convertir duración de minutos a horas decimales
            $duracionDiagnostico = null;
            if ($ordenAntigua->duracion && $ordenAntigua->duracion > 0) {
                $horas = round($ordenAntigua->duracion / 60, 2);
                if ($horas <= 999999.99) {
                    $duracionDiagnostico = $horas;
                }
            }

            try {
                $ordenNueva = OrdenTrabajo::create([
                    'tipo_orden' => $tipoOrden,
                    'cliente_id' => $clienteId,
                    'vehiculo_id' => $vehiculoId,
                    'motivo_ingreso' => $ordenAntigua->description ?: 'Sin descripción',
                    'km_actual' => $kmActual,
                    'etapa_actual' => 'Finalizado',
                    'estado' => 'Cerrada',
                    'duracion_diagnostico' => $duracionDiagnostico,
                    'diagnosticado_por' => null, // No mapeamos usuarios
                    'detalle_diagnostico' => $ordenAntigua->detalle_tecnico ?: null,
                    'repuestos_entregados' => true,
                    'tiquete_impreso' => true,
                    'created_at' => $ordenAntigua->checkin_date,
                    'updated_at' => $ordenAntigua->last_update,
                ]);

                $this->ordenTrabajoMap[$ordenAntigua->id] = $ordenNueva->id;

                // Migrar cotizaciones de esta orden
                if (isset($cotizaciones[$ordenAntigua->id])) {
                    $cotizacionesOrden = $cotizaciones[$ordenAntigua->id];
                    foreach ($cotizacionesOrden as $cotizacion) {
                        $esAprobada = $ordenAntigua->cotiza_num && 
                            strpos($ordenAntigua->cotiza_num, (string)$cotizacion->cotizanum_alegra) !== false;
                        
                        CotizacionOrdenTrabajo::create([
                            'orden_trabajo_id' => $ordenNueva->id,
                            'numero_cotizacion' => (string)$cotizacion->cotizanum_alegra,
                            'alegra_id' => (string)$cotizacion->cotizanum_alegra,
                            'cliente_nombre' => Cliente::find($clienteId)->nombre ?? '-',
                            'fecha_emision' => $ordenAntigua->checkin_date,
                            'total' => $cotizacion->cotizacion_total,
                            'ruta_pdf' => $cotizacion->pdf_path ?: null,
                            'aprobada' => $esAprobada,
                        ]);
                        $cotizacionesCreadas++;
                    }
                }

                // Migrar facturas de esta orden
                if (isset($facturas[$ordenAntigua->id])) {
                    $facturasOrden = $facturas[$ordenAntigua->id];
                    foreach ($facturasOrden as $factura) {
                        FacturaOrdenTrabajo::create([
                            'orden_trabajo_id' => $ordenNueva->id,
                            'numero_factura' => $factura->factura_numero,
                            'alegra_id' => $factura->factura_numero,
                            'cliente_nombre' => Cliente::find($clienteId)->nombre ?? '-',
                            'fecha_emision' => $ordenAntigua->last_update,
                            'total' => $factura->total,
                            'ruta_pdf' => null,
                        ]);
                        $facturasCreadas++;
                    }
                }

                // Migrar órdenes de compra de esta orden (conservar solo una por orden si hay duplicados)
                if (isset($ordenesCompra[$ordenAntigua->id])) {
                    $ocsOrden = $ordenesCompra[$ordenAntigua->id];
                    $ocsUnicas = $ocsOrden->unique('ocnum_alegra');
                    
                    foreach ($ocsUnicas as $oc) {
                        OrdenCompraOrdenTrabajo::create([
                            'orden_trabajo_id' => $ordenNueva->id,
                            'numero_orden' => (string)$oc->ocnum_alegra,
                            'alegra_id' => (string)$oc->ocnum_alegra,
                            'proveedor_nombre' => $oc->oc_proveedor ?: '-',
                            'fecha_emision' => $ordenAntigua->checkin_date,
                            'total' => $oc->oc_total,
                            'ruta_pdf' => null,
                        ]);
                        $ordenesCompraCreadas++;
                    }
                }

                $processed++;
            } catch (\Exception $e) {
                $this->command->error("Error en orden {$ordenAntigua->id}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("  ✓ Órdenes de trabajo creadas: {$processed}, errores: {$errors}");
        $this->command->info("  ✓ Cotizaciones creadas: {$cotizacionesCreadas}");
        $this->command->info("  ✓ Facturas creadas: {$facturasCreadas}");
        $this->command->info("  ✓ Órdenes de compra creadas: {$ordenesCompraCreadas}");
    }

    /**
     * Mostrar resumen de la migración
     */
    protected function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Resumen de la migración:');
        $this->command->table(
            ['Tabla', 'Registros'],
            [
                ['Marcas', Marca::count()],
                ['Modelos', Modelo::count()],
                ['Clientes', Cliente::count()],
                ['Vehículos', Vehiculo::count()],
                ['Órdenes de Trabajo', OrdenTrabajo::count()],
                ['Cotizaciones', CotizacionOrdenTrabajo::count()],
                ['Facturas', FacturaOrdenTrabajo::count()],
                ['Órdenes de Compra', OrdenCompraOrdenTrabajo::count()],
            ]
        );
    }
}
