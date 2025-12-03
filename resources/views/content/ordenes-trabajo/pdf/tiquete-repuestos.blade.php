<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tiquete de Repuestos</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .info {
            margin-bottom: 10px;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items th {
            text-align: left;
            border-bottom: 1px dashed #000;
        }
        .items td {
            padding: 2px 0;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <strong>TIQUETE DE REPUESTOS</strong><br>
        Orden de Trabajo #{{ $orden->id }}
    </div>

    <div class="info">
        <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}<br>
        <strong>Placa:</strong> {{ $orden->vehiculo->placa }}<br>
        <strong>Vehículo:</strong> {{ $orden->vehiculo->marca->nombre ?? '' }} {{ $orden->vehiculo->modelo->nombre ?? '' }}
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td style="width: 15%; vertical-align: top;">{{ number_format($item['quantity'], 0) }}</td>
                    <td>{{ $item['name'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" style="text-align: center;">No hay repuestos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Firma de Recibido
        <br><br><br>
        __________________________
    </div>
</body>
</html>
