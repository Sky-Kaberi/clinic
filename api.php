<?php

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    listPatients();
    exit;
}

if ($method === 'POST') {
    createPatient();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function listPatients(): void
{
    $pdo = getPdo();

    $stmt = $pdo->query('SELECT id, full_name, email, phone, created_at FROM patients ORDER BY id DESC');
    $patients = $stmt->fetchAll();

    echo json_encode(['data' => $patients]);
}

function createPatient(): void
{
    $input = json_decode(file_get_contents('php://input'), true);

    $fullName = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if ($fullName === '' || $email === '') {
        http_response_code(422);
        echo json_encode(['error' => 'full_name and email are required']);
        return;
    }

    $pdo = getPdo();

    $stmt = $pdo->prepare('INSERT INTO patients (full_name, email, phone) VALUES (:full_name, :email, :phone)');
    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':phone' => $phone,
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'Patient created']);
}
