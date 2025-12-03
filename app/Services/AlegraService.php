<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlegraService
{
    private string $baseUrl;
    private string $authorization;

    public function __construct()
    {
        $this->baseUrl = config('services.alegra.base_url', 'https://api.alegra.com/api/v1');
        $this->authorization = 'Basic ' . config('services.alegra.authorization');
    }

    /**
     * Buscar contactos por número de identificación
     *
     * @param string $identification
     * @return array|null
     */
    public function buscarContactoPorIdentificacion(string $identification): ?array
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . '/contacts', [
                'order_direction' => 'ASC',
                'identification' => $identification,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Retornar el primer resultado si existe
                if (is_array($data) && count($data) > 0) {
                    return $data[0];
                }
            } else {
                Log::error('Error al buscar contacto en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al buscar contacto en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mapear datos de Alegra al formato de Cliente
     *
     * @param array $alegraData
     * @return array
     */
    public function mapearDatosCliente(array $alegraData): array
    {
        $direccion = '';
        if (isset($alegraData['address'])) {
            $addressParts = [];
            if (!empty($alegraData['address']['address'])) {
                $addressParts[] = $alegraData['address']['address'];
            }
            if (!empty($alegraData['address']['city'])) {
                $addressParts[] = $alegraData['address']['city'];
            }
            $direccion = implode(', ', $addressParts);
        }

        $telefono = '';
        if (!empty($alegraData['phonePrimary'])) {
            $telefono = $alegraData['phonePrimary'];
        } elseif (!empty($alegraData['mobile'])) {
            $telefono = $alegraData['mobile'];
        }

        return [
            'nombre' => $alegraData['name'] ?? '',
            'correo_electronico' => $alegraData['email'] ?? '',
            'telefono' => $telefono,
            'direccion' => $direccion,
            'numero_identificacion' => $alegraData['identification'] ?? '',
        ];
    }

    /**
     * Buscar cotización por número
     *
     * @param string $number
     * @return array|null
     */
    public function buscarCotizacionPorNumero(string $number): ?array
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . '/estimates', [
                'number' => $number,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Retornar el primer resultado si existe
                if (is_array($data) && count($data) > 0) {
                    return $data[0];
                }
            } else {
                Log::error('Error al buscar cotización en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al buscar cotización en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener PDF de una cotización
     *
     * @param string $id
     * @return string|null URL del PDF
     */
    public function obtenerPdfCotizacion(string $id): ?string
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . "/estimates/{$id}", [
                'fields' => 'pdf',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['pdf'] ?? null;
            } else {
                Log::error('Error al obtener PDF de cotización en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al obtener PDF de cotización en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar orden de compra por número
     *
     * @param string $number
     * @return array|null
     */
    public function buscarOrdenCompraPorNumero(string $number): ?array
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . '/purchase-orders', [
                'number' => $number,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Retornar el primer resultado si existe
                if (is_array($data) && count($data) > 0) {
                    return $data[0];
                }
            } else {
                Log::error('Error al buscar orden de compra en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al buscar orden de compra en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener PDF de una orden de compra
     *
     * @param string $id
     * @return string|null URL del PDF
     */
    public function obtenerPdfOrdenCompra(string $id): ?string
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . "/purchase-orders/{$id}", [
                'fields' => 'pdf',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['pdf'] ?? null;
            } else {
                Log::error('Error al obtener PDF de orden de compra en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al obtener PDF de orden de compra en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener cotización completa por ID
     *
     * @param string $id
     * @return array|null
     */
    public function obtenerCotizacion(string $id): ?array
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . "/estimates/{$id}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error al obtener cotización en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al obtener cotización en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar factura por número
     *
     * @param string $number
     * @return array|null
     */
    public function buscarFacturaPorNumero(string $number): ?array
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . '/invoices', [
                'number' => $number,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Retornar el primer resultado si existe
                if (is_array($data) && count($data) > 0) {
                    return $data[0];
                }
            } else {
                Log::error('Error al buscar factura en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al buscar factura en Alegra: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener PDF de una factura
     *
     * @param string $id
     * @return string|null URL del PDF
     */
    public function obtenerPdfFactura(string $id): ?string
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => $this->authorization,
            ])->get($this->baseUrl . "/invoices/{$id}", [
                'fields' => 'pdf',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['pdf'] ?? null;
            } else {
                Log::error('Error al obtener PDF de factura en Alegra: ' . $response->status() . ' - ' . $response->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error al obtener PDF de factura en Alegra: ' . $e->getMessage());
            return null;
        }
    }
}
