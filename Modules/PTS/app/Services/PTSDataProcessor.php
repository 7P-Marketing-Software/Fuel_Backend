<?php

namespace Modules\PTS\Services;

use Modules\PTS\Models\PTSMeasurement;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PTSDataProcessor
{
    public function processMessage(array $data, string $ptsId)
    {
        foreach ($data['Packets'] ?? [] as $packet) {
            $this->processPacket($packet, $ptsId);
        }
    }

    private function processPacket(array $packet, string $ptsId)
    {
        $type = $packet['Type'] ?? 'Unknown';
        $packetData = $packet['Data'] ?? [];

        switch ($type) {
            case 'UploadStatus':
                $this->processUploadStatus($packetData, $ptsId);
                break;

            case 'UploadPumpTransaction':
                $this->processPumpTransaction($packetData, $ptsId);
                break;

            case 'UploadTankMeasurement':
                $this->processTankMeasurement($packetData, $ptsId);
                break;

            case 'UploadAlertRecord':
                $this->processAlertRecord($packetData, $ptsId);
                break;

            case 'UploadConfiguration':
                $this->processConfiguration($packetData, $ptsId);
                break;

            default:
                Log::channel('pts')->info("Unprocessed packet type: {$type}", [
                    'pts_id' => $ptsId
                ]);
        }
    }

    private function processUploadStatus(array $data, string $ptsId)
    {
        $measurements = $data['Probes']['OnlineStatus']['Measurements'] ?? [];

        foreach ($measurements as $measurement) {
            PTSMeasurement::create([
                'pts_id' => $ptsId,
                'probe_id' => $measurement[0] ?? 'unknown',
                'fuel_level' => $measurement[1] ?? null,
                'temperature_1' => $measurement[2] ?? null,
                'temperature_2' => $measurement[3] ?? null,
                'additional_data' => array_slice($measurement, 4),
                'measured_at' => Carbon::now(),
            ]);
        }

        Log::channel('pts')->info('Probe measurements stored', [
            'pts_id' => $ptsId,
            'measurement_count' => count($measurements)
        ]);
    }

    private function processPumpTransaction(array $data, string $ptsId)
    {
        Log::channel('pts')->info('Pump transaction received', [
            'pts_id' => $ptsId,
            'pump_id' => $data['PumpId'] ?? 'unknown',
            'volume' => $data['Volume'] ?? 0
        ]);
    }

    private function processTankMeasurement(array $data, string $ptsId)
    {
        Log::channel('pts')->info('Tank measurement received', [
            'pts_id' => $ptsId,
            'tank_id' => $data['TankId'] ?? 'unknown'
        ]);
    }

    private function processAlertRecord(array $data, string $ptsId)
    {
        Log::channel('pts')->info('Alert record received', [
            'pts_id' => $ptsId,
            'alert_type' => $data['AlertType'] ?? 'unknown'
        ]);
    }

    private function processConfiguration(array $data, string $ptsId)
    {
        Log::channel('pts')->info('Configuration received', [
            'pts_id' => $ptsId
        ]);
    }
}
