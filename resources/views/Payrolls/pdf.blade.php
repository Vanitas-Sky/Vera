<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recibo_Nomina_{{ $detail->employee->rfc }}</title>
    <style>
        @page {
            margin: 30px 40px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #334155;
            line-height: 1.3;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .uuid {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        .layout-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .layout-table td {
            vertical-align: top;
        }

        .info-title {
            font-size: 10px;
            font-weight: bold;
            color: #ffffff;
            background-color: #475569;
            padding: 4px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .info-text {
            font-size: 10px;
            margin: 2px 0;
        }

        .payment-info {
            width: 100%;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 5px;
        }

        .payment-info table {
            width: 100%;
            font-size: 9px;
        }

        .payment-info td {
            padding: 2px 5px;
        }

        .payment-info strong {
            color: #475569;
        }

        .calc-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .calc-table th {
            background-color: #f8fafc;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
        }

        .calc-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .net-box {
            width: 40%;
            float: right;
            margin-top: 15px;
            border: 2px solid #10b981;
            padding: 10px;
            text-align: right;
            background-color: #f0fdf4;
        }

        .net-title {
            font-size: 10px;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
        }

        .net-amount {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 5px;
        }

        .crypto-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            table-layout: fixed;
        }

        .crypto-title {
            font-size: 8px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 2px;
            margin-top: 6px;
        }

        .crypto-string {
            font-size: 7px;
            color: #64748b;
            word-wrap: break-word;
            word-break: break-all;
            line-height: 1.2;
            font-family: monospace;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- Encabezado unificado al estilo Invoice -->
    <div class="header">
        <div class="title">Recibo de Nómina (CFDI 4.0)</div>
        <div class="uuid">Folio Fiscal (UUID): N0M1N4A0-C4E9-49B3-B0E2-{{ str_pad($detail->id, 12, '0', STR_PAD_LEFT) }}</div>
        <div class="uuid">Fecha y Hora de Emisión: {{ now()->format('d/m/Y H:i:s') }} | Tipo de Comprobante: N - Nómina</div>
        <div class="uuid">Periodo Laborado: {{ $detail->period->period_name }} ({{ $detail->period->start_date->format('d/m/Y') }} al {{ $detail->period->end_date->format('d/m/Y') }})</div>
    </div>

    <!-- Datos de Patrón y Empleado -->
    <table class="layout-table">
        <tr>
            <!-- Columna Izquierda: El Patrón (Empresa) -->
            <td style="width: 48%; padding-right: 2%;">
                <div class="info-title">Datos del Patrón (Emisor)</div>
                <div class="info-value">{{ $company->legal_name ?? 'Empresa S.A. de C.V.' }}</div>
                <p class="info-text"><strong>RFC:</strong> {{ $company->rfc ?? 'XAXX010101000' }}</p>
                <p class="info-text"><strong>Régimen Fiscal:</strong> {{ $company->tax_regime_code ?? '601 - General de Ley Personas Morales' }}</p>
                <p class="info-text"><strong>Lugar de Expedición (C.P.):</strong> {{ $company->zip_code ?? '29950' }}</p>
                <p class="info-text"><strong>Registro Patronal IMSS:</strong> No capturado</p>
            </td>

            <!-- Columna Derecha: El Empleado (Receptor) -->
            <td style="width: 48%; padding-left: 2%;">
                <div class="info-title">Datos del Empleado (Receptor)</div>
                <div class="info-value">{{ $detail->employee->full_name }}</div>

                <!-- Tabla anidada para dividir los datos del empleado a dos mini-columnas para ahorrar espacio -->
                <table style="width: 100%; border-collapse: collapse; margin-top: 3px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <p class="info-text"><strong>RFC:</strong> {{ $detail->employee->rfc }}</p>
                            <p class="info-text"><strong>CURP:</strong> {{ $detail->employee->curp }}</p>
                            <p class="info-text"><strong>NSS:</strong> {{ $detail->employee->nss }}</p>
                            <p class="info-text"><strong>Puesto:</strong> {{ $detail->employee->position ?? 'No especificado' }}</p>
                        </td>
                        <td style="width: 50%; vertical-align: top;">
                            <p class="info-text"><strong>C.P. Fiscal:</strong> {{ $detail->employee->cp ?? '29950' }}</p>
                            <p class="info-text"><strong>Uso CFDI:</strong> CN01 - Nómina</p>
                            <p class="info-text"><strong>Ingreso:</strong> {{ $detail->employee->hire_date ? \Carbon\Carbon::parse($detail->employee->hire_date)->format('d/m/Y') : 'No capturada' }}</p>
                            <p class="info-text"><strong>CLABE:</strong> {{ $detail->employee->clabe ?? 'No capturada' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Reglas de Cobro (Específicas de Nómina) -->
    <div class="payment-info">
        <table>
            <tr>
                <td width="25%"><strong>Moneda:</strong> MXN</td>
                <td width="35%"><strong>Forma de Pago:</strong> 99 - Por definir</td>
                <td width="40%"><strong>Método de Pago:</strong> PUE - Pago en una sola exhibición</td>
            </tr>
        </table>
    </div>

    <!-- Detalle de Percepciones y Deducciones -->
    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 48%; vertical-align: top; padding-right: 2%;">
                <table class="calc-table">
                    <thead>
                        <tr>
                            <th>PERCEPCIONES</th>
                            <th class="text-right">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sueldo Base (Mensual)</td>
                            <td class="text-right">${{ number_format($detail->gross_salary, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 48%; vertical-align: top; padding-left: 2%;">
                <table class="calc-table">
                    <thead>
                        <tr>
                            <th>DEDUCCIONES</th>
                            <th class="text-right">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$isrBreakdown['is_minimum_wage'])
                        <tr>
                            <td>002 - ISR (Art. 96)</td>
                            <td class="text-right text-red-600">-${{ number_format($detail->isr_retention, 2) }}</td>
                        </tr>
                        @endif
                        @if($detail->imss_employee > 0)
                        <tr>
                            <td>001 - Cuota IMSS Trabajador</td>
                            <td class="text-right text-red-600">-${{ number_format($detail->imss_employee, 2) }}</td>
                        </tr>
                        @endif
                        @if($isrBreakdown['is_minimum_wage'])
                        <tr>
                            <td><span style="color: #10b981;">Subsidio / Apoyo Salario Mínimo</span></td>
                            <td class="text-right">$0.00</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="net-box">
        <div class="net-title">Neto a Pagar</div>
        <div class="net-amount">${{ number_format($detail->net_salary, 2) }} MXN</div>
    </div>
    <div style="clear: both;"></div>

    <!-- Bloque Criptográfico CFDI 4.0 -->
    <table class="crypto-table">
        <tr>
            <!-- Columna del QR -->
            <td style="width: 25%; text-align: center; vertical-align: top;">
                @if(isset($qrCode))
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="Código QR SAT" style="width: 130px; height: 130px;">
                @else
                <div style="width: 130px; height: 130px; border: 1px solid #ccc; line-height: 130px; font-size: 10px; margin: 0 auto;">QR NO DISPONIBLE</div>
                @endif
            </td>

            <!-- Columna de los Sellos -->
            <td style="width: 75%; vertical-align: top; padding-left: 15px;">
                <div style="font-size: 8px; margin-bottom: 5px;">
                    <strong>No. de Serie del Certificado Emisor:</strong> 00001000000504465028<br>
                    <strong>No. de Serie del Certificado SAT:</strong> 00001000000505142236
                </div>

                <div class="crypto-title">Sello Digital del Emisor:</div>
                <div class="crypto-string">q8H7fO/dG6+rX9eZ1mN4pQ2wV5sT8uY3iI6oP9aA4sD7fG0hJ3kL6zX9cV2bN5mQ8wE1rT4yU7iO0pP3aA6sD9fG2hJ5kL8zX1cV4bN7mQ0wE3rT6yU9iO2pP5aA8sD1fG4hJ7kL0zX3cV6bN9mQ2wE5rT8yU1iO4pP7aA0sD3fG6hJ9kL2zX5cV8bN1mQ4wE7rT0yU3iO6p==</div>

                <div class="crypto-title">Sello Digital del SAT:</div>
                <div class="crypto-string">bN5mQ8wE1rT4yU7iO0pP3aA6sD9fG2hJ5kL8zX1cV4bN7mQ0wE3rT6yU9iO2pP5aA8sD1fG4hJ7kL0zX3cV6bN9mQ2wE5rT8yU1iO4pP7aA0sD3fG6hJ9kL2zX5cV8bN1mQ4wE7rT0yU3iO6pA9sD2fG5hJ8kL1zX4cV7bN0mQ3wE6rT9yU2iO5pP8aA1sD4fG7hJ0kL==</div>

                <div class="crypto-title">Cadena Original del Complemento de Certificación Digital del SAT:</div>
                <div class="crypto-string">||1.1|N0M1N4A0-C4E9-49B3-B0E2-{{ str_pad($detail->id, 12, '0', STR_PAD_LEFT) }}|{{ now()->format('Y-m-d\TH:i:s') }}|SAT970701NN3|q8H7fO/dG6+rX9eZ1mN4pQ2wV5sT8uY3iI6oP9aA4sD7fG0hJ3kL6zX9cV2bN5mQ8wE1rT4yU7iO0pP3aA6sD9fG2hJ5kL8zX1cV4bN7mQ0wE3rT6yU9iO2pP5aA8sD1fG4hJ7kL0zX3cV6bN9mQ2wE5rT8yU1iO4pP7aA0sD3fG6hJ9kL2zX5cV8bN1mQ4wE7rT0yU3iO6p==|00001000000505142236||</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        ESTE DOCUMENTO ES UNA REPRESENTACIÓN IMPRESA DE UN CFDI DE NÓMINA (SIMULADO)
    </div>

</body>

</html>