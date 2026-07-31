<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ConciliationController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $company = \Illuminate\Support\Facades\Auth::user()->companies()->first();

        if (!$company) {
            return redirect()->route('companies.create')->with('error', 'Primero debes registrar tu empresa.');
        }

        // ==========================================
        // VÍA A: MATEMÁTICA Y SEMÁFORO MENSUAL
        // ==========================================
        $period = $request->input('period', now()->format('Y-m'));
        $currentYear = (int) substr($period, 0, 4);
        $currentMonth = (int) substr($period, 5, 2);

        $satExpenses = \App\Models\Invoice::where('company_id', $company->id)
            ->where('type', 'E')
            ->where('is_canceled', false)
            ->whereMonth('issue_date', $currentMonth)
            ->whereYear('issue_date', $currentYear)
            ->sum('total');

        $bankWithdrawals = \App\Models\BankTransaction::where('company_id', $company->id)
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('withdrawal');

        $discrepancy = $bankWithdrawals - $satExpenses;

        // ==========================================
        // VÍA B: FILTROS Y PAGINACIÓN PARA LA TABLA BANCARIA
        // ==========================================
        $search = $request->input('search');
        $type = $request->input('type', 'todos'); // Retiros vs Depósitos

        $query = \App\Models\BankTransaction::where('company_id', $company->id)
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear);

        // Filtro de Tipo de Movimiento
        if ($type === 'retiros') {
            $query->where('withdrawal', '>', 0);
        } elseif ($type === 'depositos') {
            $query->where('deposit', '>', 0);
        }

        // Buscador de Texto Libre (Concepto o Referencia Bancaria)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('concept', 'LIKE', "%{$search}%")
                    ->orWhere('reference', 'LIKE', "%{$search}%");
            });
        }

        // Paginación persistente
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate(15) // El banco suele tener más renglones, 15 es un buen número
            ->withQueryString();

        return view('conciliations.index', compact(
            'satExpenses',
            'bankWithdrawals',
            'discrepancy',
            'transactions',
            'period',
            'search',
            'type'
        ));
    }

    // 2. Previsualizar el CSV y extraer los encabezados
    public function preview(Request $request)
    {
        $request->validate([
            'bank_file' => 'required|file|mimes:csv,txt|max:5120', // Solo CSV hasta 5MB
        ]);

        $file = $request->file('bank_file');

        // Guardamos el archivo temporalmente
        $path = $file->storeAs('temp', 'bank_import_' . Auth::id() . '_' . time() . '.csv');
        $fullPath = storage_path('app/' . $path);

        // Abrimos el archivo para leer solo la fila 1
        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            return redirect()->back()->with('error', 'No se pudo abrir el archivo subido.');
        }

        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle);

        $headers = fgetcsv($handle, 1000, $delimiter);
        fclose($handle);

        if (!$headers) {
            Storage::delete($path);
            return redirect()->back()->with('error', 'No se pudieron leer las columnas del archivo. Verifica que no esté vacío.');
        }

        // Mandamos los encabezados y la ruta temporal a la vista de mapeo
        return view('conciliations.map', compact('headers', 'path'));
    }

    // 3. Procesar e importar los datos basándose en el mapeo del usuario
    public function import(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'col_date' => 'required|integer',
            'col_description' => 'required|integer',
            'col_withdrawal' => 'required|integer',
            'col_deposit' => 'required|integer',
        ]);

        $company = Auth::user()->companies()->first();
        $fullPath = storage_path('app/' . $request->path);

        if (!file_exists($fullPath)) {
            return redirect()->route('conciliations.index')->with('error', 'El archivo expiró o fue eliminado. Súbelo de nuevo.');
        }

        $handle = fopen($fullPath, 'r');
        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle);

        // Saltamos la fila 1 (encabezados)
        fgetcsv($handle, 1000, $delimiter);

        $importedCount = 0;

        // Recorremos el archivo fila por fila
        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {

            // Evitar filas vacías
            if (!isset($data[$request->col_date]) || empty(trim($data[$request->col_date]))) {
                continue;
            }

            // Limpiador matemático: Quita signos $, comas y espacios
            $rawWithdrawal = $data[$request->col_withdrawal] ?? '0';
            $rawDeposit = $data[$request->col_deposit] ?? '0';

            $withdrawal = floatval(preg_replace('/[^-0-9\.]/', '', $rawWithdrawal));
            $deposit = floatval(preg_replace('/[^-0-9\.]/', '', $rawDeposit));

            if ($withdrawal == 0 && $deposit == 0) {
                continue;
            }

            // Formato de fecha seguro
            try {
                $rawDate = str_replace('/', '-', trim($data[$request->col_date]));
                $date = Carbon::parse($rawDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $date = now()->format('Y-m-d');
            }

            BankTransaction::create([
                'company_id' => $company->id,
                'transaction_date' => $date,
                'description' => trim($data[$request->col_description] ?? 'Sin descripción'),
                'withdrawal' => abs($withdrawal),
                'deposit' => abs($deposit),
            ]);

            $importedCount++;
        }

        fclose($handle);
        Storage::delete($request->path); // Limpieza del archivo temporal

        return redirect()->route('conciliations.index')->with('success', "Conciliación lista. Se ingresaron {$importedCount} movimientos del banco correctamente.");
    }
}
