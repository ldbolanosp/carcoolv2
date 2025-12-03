<?php

namespace App\Http\Controllers;

use App\Models\FinanceEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        // Obtener mes y año
        $mesParam = $request->get('mes'); // Formato YYYY-MM
        if ($mesParam) {
            $date = Carbon::createFromFormat('Y-m', $mesParam);
        } else {
            $date = Carbon::now();
        }

        $anio = $date->year;
        $mes = $date->month;

        // Generar días del mes
        $primerDia = $date->copy()->startOfMonth();
        $ultimoDia = $date->copy()->endOfMonth();
        
        $diasMes = [];
        $curr = $primerDia->copy();
        while ($curr <= $ultimoDia) {
            $diasMes[] = $curr->format('Y-m-d');
            $curr->addDay();
        }

        // Obtener registros del mes
        $registros = FinanceEntry::whereYear('date', $anio)
            ->whereMonth('date', $mes)
            ->get();

        // Estructurar datos para la vista: [rubro][fecha] = monto
        $datosMes = [];
        foreach ($registros as $r) {
            $datosMes[$r->category][$r->date->format('Y-m-d')] = $r->amount;
        }

        // Resumen Provisión
        $resumenProvision = $this->calcularResumenProvision($anio, $mes);

        // Historial de Provisiones
        $provisiones = FinanceEntry::where('category', 'Impuesto Provisionado')
            ->whereYear('date', $anio)
            ->whereMonth('date', $mes)
            ->orderBy('date', 'asc')
            ->get();

        return view('content.finanzas.index', [
            'anio' => $anio,
            'mes' => $mes,
            'dias_mes' => $diasMes,
            'datos_mes' => $datosMes,
            'resumen_provision' => $resumenProvision,
            'provisiones' => $provisiones,
        ]);
    }

    private function calcularResumenProvision($anio, $mes)
    {
        $resumen = [
            'Impuestos de Ventas' => 0,
            'Retención 5.31' => 0,
            'Impuestos de Compras' => 0,
            'Impuesto Provisionado' => 0,
            'Impuesto Neto' => 0,
            'Pendiente de Provisión' => 0
        ];

        $data = FinanceEntry::select('category', DB::raw('SUM(amount) as total'))
            ->whereYear('date', $anio)
            ->whereMonth('date', $mes)
            ->whereIn('category', array_keys($resumen))
            ->groupBy('category')
            ->pluck('total', 'category');

        foreach ($data as $cat => $val) {
            $resumen[$cat] = (float) $val;
        }

        // Cálculo base: impuesto neto (puede ser positivo o negativo)
        // (Compras + Retención) - Ventas
        // Nota: En el modelo original era: (imp_compras + retencion - imp_ventas)
        // Si da negativo, es "a pagar" (más ventas que compras).
        $impuestoNeto = ($resumen['Impuestos de Compras'] + $resumen['Retención 5.31']) - $resumen['Impuestos de Ventas'];
        $provisionado = $resumen['Impuesto Provisionado'];

        $resumen['Impuesto Neto'] = $impuestoNeto;

        // Lógica copiada del modelo CodeIgniter
        // Caso 1: impuesto a pagar (negativo)
        if ($impuestoNeto < 0) {
            $pendiente = abs($impuestoNeto) - $provisionado;
            $resumen['Pendiente de Provisión'] = max($pendiente, 0);
        }
        // Caso 2: impuesto a favor (positivo)
        elseif ($impuestoNeto > 0) {
            // Si hay provisión previa, se suma al saldo a favor
            if ($provisionado > 0) {
                $resumen['Pendiente de Provisión'] = $impuestoNeto + $provisionado;
            } else {
                $resumen['Pendiente de Provisión'] = 0; // Opcional: $impuestoNeto si queremos mostrar saldo a favor
            }
        }
        // Caso 3: impuesto neutro
        else {
            $resumen['Pendiente de Provisión'] = 0;
        }

        return $resumen;
    }

    public function getDataByDate(Request $request)
    {
        $fecha = $request->get('fecha');
        if (!$fecha) {
            return response()->json(['existe' => false]);
        }

        $datos = FinanceEntry::where('date', $fecha)
            ->pluck('amount', 'category');

        $existe = $datos->isNotEmpty();

        return response()->json([
            'existe' => $existe,
            'datos' => $datos
        ]);
    }

    public function storeDaily(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'ventas' => 'required|numeric',
            'impventas' => 'required|numeric',
            'retenciones' => 'required|numeric',
            'costos' => 'required|numeric',
            'impcompras' => 'required|numeric',
            'gastos' => 'required|numeric',
        ]);

        $fecha = $request->input('fecha');

        $datos = [
            'Gastos Fijos' => $request->input('gastos'),
            'Ventas' => $request->input('ventas'),
            'Costos' => $request->input('costos'),
            'Impuestos de Compras' => $request->input('impcompras'),
            'Impuestos de Ventas' => $request->input('impventas'),
            'Retención 5.31' => $request->input('retenciones'),
        ];

        // Verificar si es 'nuevo' y ya existe (opcional, validación extra)
        if ($request->input('modo') === 'nuevo' && FinanceEntry::where('date', $fecha)->exists()) {
            // Podríamos retornar error, pero updateOrCreate maneja esto bien.
            // Seguimos lógica de sobreescribir/actualizar.
        }

        foreach ($datos as $rubro => $monto) {
            FinanceEntry::updateOrCreate(
                ['date' => $fecha, 'category' => $rubro],
                ['amount' => $monto]
            );
        }

        $mesStr = Carbon::parse($fecha)->format('Y-m');
        return redirect()->route('finanzas.index', ['mes' => $mesStr])
            ->with('success', 'Datos guardados correctamente.');
    }

    public function storeProvision(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'monto' => 'required|numeric',
        ]);

        FinanceEntry::create([
            'date' => $request->input('fecha'),
            'category' => 'Impuesto Provisionado',
            'amount' => $request->input('monto'),
        ]);

        $mesStr = Carbon::parse($request->input('fecha'))->format('Y-m');
        return redirect()->route('finanzas.index', ['mes' => $mesStr])
            ->with('success', 'Provisión guardada correctamente.');
    }
}
