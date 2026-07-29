<?php

namespace App\Services;

use Exception;

class XmlParserService
{
    public function parse(string $xmlContent): array
    {
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml === false) {
            throw new Exception("El archivo subido no es un XML válido o está corrupto.");
        }

        $namespaces = $xml->getNamespaces(true);
        $cfdiNamespace = $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
        $cfdi = $xml->children($cfdiNamespace);
        
        if (!isset($cfdi->Emisor) || !isset($cfdi->Receptor)) {
            throw new Exception("El archivo XML no tiene la estructura de una factura del SAT.");
        }

        $comprobanteAttrs = $xml->attributes();
        $tipoDeComprobante = (string) $comprobanteAttrs['TipoDeComprobante'];
        $fecha = (string) $comprobanteAttrs['Fecha'];
        $subtotal = (float) $comprobanteAttrs['SubTotal'];
        $total = (float) $comprobanteAttrs['Total'];
        $serie = isset($comprobanteAttrs['Serie']) ? (string) $comprobanteAttrs['Serie'] : null;
        $folio = isset($comprobanteAttrs['Folio']) ? (string) $comprobanteAttrs['Folio'] : null;
        $moneda = isset($comprobanteAttrs['Moneda']) ? (string) $comprobanteAttrs['Moneda'] : 'MXN';
        $metodoPago = isset($comprobanteAttrs['MetodoPago']) ? (string) $comprobanteAttrs['MetodoPago'] : null;
        $formaPago = isset($comprobanteAttrs['FormaPago']) ? (string) $comprobanteAttrs['FormaPago'] : null;

        // Emisor y Receptor
        $emisorAttrs = $cfdi->Emisor->attributes();
        $receptorAttrs = $cfdi->Receptor->attributes();
        
        $rfcEmisor = (string) $emisorAttrs['Rfc'];
        $nombreEmisor = isset($emisorAttrs['Nombre']) ? (string) $emisorAttrs['Nombre'] : 'S/N';
        
        $rfcReceptor = (string) $receptorAttrs['Rfc'];
        $nombreReceptor = isset($receptorAttrs['Nombre']) ? (string) $receptorAttrs['Nombre'] : 'S/N';
        $usoCfdi = isset($receptorAttrs['UsoCFDI']) ? (string) $receptorAttrs['UsoCFDI'] : null;

        // IVA
        $iva = 0.00;
        if (isset($cfdi->Impuestos)) {
            $impuestosAttrs = $cfdi->Impuestos->attributes();
            if (isset($impuestosAttrs['TotalImpuestosTrasladados'])) {
                $iva = (float) $impuestosAttrs['TotalImpuestosTrasladados'];
            }
        }

        // Extracción de Conceptos (Productos/Servicios)
        $items = [];
        if (isset($cfdi->Conceptos->Concepto)) {
            foreach ($cfdi->Conceptos->Concepto as $concepto) {
                $cAttrs = $concepto->attributes();
                $items[] = [
                    'clave_prod_serv' => (string) $cAttrs['ClaveProdServ'], // <-- NUEVA LÍNEA CLAVE
                    'cantidad' => (float) $cAttrs['Cantidad'],
                    'unidad' => isset($cAttrs['Unidad']) ? (string) $cAttrs['Unidad'] : 'Unidad',
                    'descripcion' => (string) $cAttrs['Descripcion'],
                    'valor_unitario' => (float) $cAttrs['ValorUnitario'],
                    'importe' => (float) $cAttrs['Importe'],
                ];
            }
        }

        // UUID (Folio Fiscal)
        $uuid = null;
        if (isset($cfdi->Complemento)) {
            $tfdNamespace = $namespaces['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital';
            $tfd = $cfdi->Complemento->children($tfdNamespace);
            
            if (isset($tfd->TimbreFiscalDigital)) {
                $tfdAttrs = $tfd->TimbreFiscalDigital->attributes();
                $uuid = (string) $tfdAttrs['UUID'];
            }
        }

        if (empty($uuid)) {
            throw new Exception("La factura no tiene UUID. Parece que no está timbrada por el SAT.");
        }

        return [
            'uuid' => $uuid,
            'serie' => $serie,
            'folio' => $folio,
            'moneda' => $moneda,
            'metodo_pago' => $metodoPago,
            'forma_pago' => $formaPago,
            'uso_cfdi' => $usoCfdi,
            'issuer_rfc' => $rfcEmisor,
            'issuer_name' => $nombreEmisor,
            'receiver_rfc' => $rfcReceptor,
            'receiver_name' => $nombreReceptor,
            'type' => strtoupper($tipoDeComprobante),
            'issue_date' => $fecha,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'items' => $items,
        ];
    }
}