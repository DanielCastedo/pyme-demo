<?php

namespace App\Http\Controllers;

use App\Models\estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class verificarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('verificador.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'registro' => 'required|digits:9',
        ]);

        $reg = $request->input('registro');
        $url = "https://caja.uagrm.edu.bo/listado.aspx?idper={$reg}&tipoper=1&sem=&anio=";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                if (strpos($response->body(), 'NO EXISTE PERSONA') !== false) {
                    return redirect()->route('verificar.index')->with('error', 'La persona con el registro proporcionado no existe');
                } else {
                    $nombre = $this->extractNombreFromHtml($response->body());

                    if (! $nombre) {
                        return redirect()->route('verificar.index')->with('error', 'No se pudo obtener el nombre de la persona');
                    }
                    // Verificar si el estudiante ya est¨¢ registrado
                    $estudiante = estudiante::where('registro', $reg)->first();
                    if (! $estudiante) {
                        // Si el estudiante no existe, crearlo
                        $estudiante = new estudiante;
                        $estudiante->registro = $reg;
                        $estudiante->nombre = $nombre;
                        $estudiante->save();
                    }
                    $encryptedReg = Crypt::encryptString($reg);

                    return redirect()->route('grupos.index2', ['reg' => $encryptedReg])->with([
                        'success' => 'Inicio correctamente',
                        'nombre' => $nombre,
                        'registro' => $reg,
                    ]);
                }
            } else {
                return redirect()->route('verificar.index')->with('error', 'Error: No se pudo conectar al servidor');
            }
        } catch (\Exception $e) {
            return redirect()->route('verificar.index')->with('error', 'Error de conexi¨®n');
        }
    }

    // Function to extract nombre from HTML using regex or DOM parsing
    private function extractNombreFromHtml($html)
    {
        // Example regex pattern to match the Nombre line in the HTML
        preg_match('/<span id="lNombre".*?>(.*?)<\/span>/', $html, $matches);

        if (isset($matches[1])) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
