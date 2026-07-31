<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #334155; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .header { background-color: #0f172a; color: #ffffff; padding: 15px; text-align: center; border-radius: 6px 6px 0 0; font-weight: bold; }
        .content { padding: 20px; background-color: #f8fafc; }
        .footer { font-size: 11px; color: #94a3b8; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Departamento de Recursos Humanos
        </div>
        <div class="content">
            <p>Hola, <strong>{{ $employee->full_name }}</strong>,</p>
            <p>Adjunto a este correo encontrarás tu recibo de nómina correspondiente al periodo: <strong>{{ $periodName }}</strong>.</p>
            <p>Te recordamos que este documento es un Comprobante Fiscal Digital por Internet (CFDI) válido y contiene la cadena criptográfica del SAT.</p>
            <p>Si tienes alguna duda con tus percepciones o deducciones, por favor contacta al departamento de finanzas.</p>
            <br>
            <p>Saludos cordiales,<br><strong>Vera</strong></p>
        </div>
        <div class="footer">
            Este es un correo generado automáticamente por el sistema Vera ERP. Por favor, no respondas a esta dirección.
        </div>
    </div>
</body>
</html>