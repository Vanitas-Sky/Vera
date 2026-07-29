<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura_{{ $invoice->uuid }}</title>
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

        /* Tablas estructurales (Bulletproof para DomPDF) */
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

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th {
            background-color: #f8fafc;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        /* Totales blindados */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 4px 0;
            font-size: 11px;
        }

        .totals-label {
            text-align: right;
            font-weight: bold;
            color: #64748b;
            padding-right: 15px;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
            color: #1e293b;
        }

        .total-final .totals-label,
        .total-final .totals-value {
            font-size: 14px;
            color: #0f172a;
            padding-top: 8px;
            border-top: 2px solid #e2e8f0;
        }

        /* Bloque Criptográfico (table-layout: fixed evita que las letras se desborden) */
        .crypto-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        /* word-wrap es vital aquí */
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

    <!-- Encabezado -->
    <div class="header">
        <div class="title">Factura Electrónica (CFDI 4.0)</div>
        <div class="uuid">Folio Fiscal (UUID): {{ $invoice->uuid }}</div>
        <div class="uuid">Fecha y Hora de Emisión: {{ $invoice->issue_date->format('d/m/Y H:i:s') }} | Tipo de Comprobante: I - Ingreso</div>
    </div>

    <!-- Datos de Emisor y Receptor (Ahora usando tabla) -->
    <table class="layout-table">
        <tr>
            <td style="width: 48%; padding-right: 2%;">
                <div class="info-title">Datos del Emisor</div>
                <div class="info-value">{{ $invoice->issuer_name }}</div>
                <p class="info-text"><strong>RFC:</strong> {{ $invoice->issuer_rfc }}</p>
                <p class="info-text"><strong>Régimen Fiscal:</strong> {{ $invoice->issuer_regimen ?? '601 - General de Ley Personas Morales' }}</p>
                <p class="info-text"><strong>Lugar de Expedición (C.P.):</strong> {{ $invoice->issuer_cp ?? '29950' }}</p>
            </td>
            <td style="width: 48%; padding-left: 2%;">
                <div class="info-title">Datos del Receptor</div>
                <div class="info-value">{{ $invoice->receiver_name }}</div>
                <p class="info-text"><strong>RFC:</strong> {{ $invoice->receiver_rfc }}</p>
                <p class="info-text"><strong>Régimen Fiscal:</strong> {{ $invoice->receiver_regimen ?? '605 - Sueldos y Salarios e Ingresos Asimilados' }}</p>
                <p class="info-text"><strong>Domicilio Fiscal (C.P.):</strong> {{ $invoice->receiver_cp ?? '29950' }}</p>
                <p class="info-text"><strong>Uso de CFDI:</strong> {{ $invoice->uso_cfdi ?? 'G03 - Gastos en general' }}</p>
            </td>
        </tr>
    </table>

    <!-- Reglas de Cobro -->
    <div class="payment-info">
        <table>
            <tr>
                <td width="25%"><strong>Moneda:</strong> {{ $invoice->moneda ?? 'MXN' }}</td>
                <td width="35%"><strong>Forma de Pago:</strong> {{ $invoice->forma_pago ?? '01' }}</td>
                <td width="40%"><strong>Método de Pago:</strong> {{ $invoice->metodo_pago ?? 'PUE' }}</td>
            </tr>
        </table>
    </div>

    <!-- Tabla de Conceptos -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="12%">Clave SAT</th>
                <th width="8%">Cant.</th>
                <th width="10%">Unidad</th>
                <th width="40%">Descripción</th>
                <th width="15%" class="text-right">Precio Unit.</th>
                <th width="15%" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @if(is_array($invoice->items) || is_object($invoice->items))
            @foreach($invoice->items as $item)
            <tr>
                <td style="font-family: monospace;">{{ $item['clave_prod_serv'] ?? '80111600' }}</td>
                <td>{{ $item['cantidad'] ?? 1 }}</td>
                <td>H87 - Pieza</td>
                <td>{{ $item['descripcion'] ?? 'Sin descripción' }}</td>
                <td class="text-right">${{ number_format($item['valor_unitario'] ?? 0, 2) }}</td>
                <td class="text-right">${{ number_format($item['importe'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>

    <!-- Totales (Obligado a la derecha mediante tabla anidada) -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 50%;"></td> <!-- Espacio vacío a la izquierda -->
            <td style="width: 50%;">
                <table class="totals-table">
                    <tr>
                        <td class="totals-label">Subtotal:</td>
                        <td class="totals-value">${{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">IVA Trasladado (16%):</td>
                        <td class="totals-value">${{ number_format($invoice->iva, 2) }}</td>
                    </tr>
                    <tr class="total-final">
                        <td class="totals-label">TOTAL MXN:</td>
                        <td class="totals-value">${{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

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
                <div class="crypto-string">||1.1|{{ $invoice->uuid }}|{{ $invoice->issue_date->format('Y-m-d\TH:i:s') }}|SAT970701NN3|q8H7fO/dG6+rX9eZ1mN4pQ2wV5sT8uY3iI6oP9aA4sD7fG0hJ3kL6zX9cV2bN5mQ8wE1rT4yU7iO0pP3aA6sD9fG2hJ5kL8zX1cV4bN7mQ0wE3rT6yU9iO2pP5aA8sD1fG4hJ7kL0zX3cV6bN9mQ2wE5rT8yU1iO4pP7aA0sD3fG6hJ9kL2zX5cV8bN1mQ4wE7rT0yU3iO6p==|00001000000505142236||</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        ESTE DOCUMENTO ES UNA REPRESENTACIÓN IMPRESA DE UN CFDI (SIMULADO)
    </div>

</body>

</html>