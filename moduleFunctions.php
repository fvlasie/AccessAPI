<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\System\SettingGateway;

function accessAPI_checkKey($container)
{
    $settings = $container->get('settings');
    $expected = $settings->getSettingByScope('AccessAPI', 'apiKeyId');

    $provided = $_SERVER['HTTP_X_ACCESSAPI_KEY'] ?? '';

    return $expected !== '' && hash_equals($expected, $provided);
}

/**
 * AccessAPI Authentication
 *
 * Validates:
 * - API Key ID
 * - HMAC signature
 * - timestamp window
 * - nonce replay protection
 */
function accessAPIAuthenticate($container) {
    $settingGateway = $container->get(SettingGateway::class);

    $apiKeyId  = $settingGateway->getSettingByScope('AccessAPI', 'apiKeyId');
    $apiSecret = $settingGateway->getSettingByScope('AccessAPI', 'apiSecret');

    if (empty($apiKeyId) || empty($apiSecret)) {
        return "API settings not configured";
    }

    // Required headers
    $headers = [
        'X-AccessAPI-KeyId',
        'X-AccessAPI-Timestamp',
        'X-AccessAPI-Nonce',
        'X-AccessAPI-Signature'
    ];

    foreach ($headers as $header) {
        if (!isset($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))])) {
            return "Missing header: $header";
        }
    }

    $clientKeyId  = $_SERVER['HTTP_X_ACCESSAPI_KEYID'];
    $timestamp    = $_SERVER['HTTP_X_ACCESSAPI_TIMESTAMP'];
    $nonce        = $_SERVER['HTTP_X_ACCESSAPI_NONCE'];
    $signature    = $_SERVER['HTTP_X_ACCESSAPI_SIGNATURE'];


    // Validate key ID
    if ($clientKeyId !== $apiKeyId) {
        return "Invalid API Key ID";
    }

    // Validate timestamp (5 minute window)
    if (abs(time() - (int)$timestamp) > 300) {
        return "Timestamp outside allowed window";
    }

    // Nonce replay protection
    $pdo = $container->get('db')->getConnection();

    // Cleanup old nonces (older than 10 minutes)
    $cutoff = time() - 600;

    try {
        $stmt = $pdo->prepare("
            DELETE FROM accessAPINonce
            WHERE timestamp < :cutoff
        ");
        $stmt->execute(['cutoff' => $cutoff]);
    } catch (\Exception $e) {
        // Table may not exist yet during install — safe to ignore
    }

    // Check if nonce exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accessAPINonce WHERE nonce = :nonce");
    $stmt->execute(['nonce' => $nonce]);

    if ($stmt->fetchColumn() > 0) {
        return "Nonce already used";
    }

    // Insert nonce
    $stmt = $pdo->prepare("INSERT INTO accessAPINonce (nonce, timestamp) VALUES (:nonce, :timestamp)");
    $stmt->execute(['nonce' => $nonce, 'timestamp' => time()]);

    // Validate signature
    $message = $apiKeyId . $timestamp . $nonce;
    $expectedSignature = hash_hmac('sha256', $message, $apiSecret);

    if (!hash_equals($expectedSignature, $signature)) {
        return "Invalid signature";
    }

    return true;
}
