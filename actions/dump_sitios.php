<?php
require_once __DIR__ . '/../config/app.php';
$db = getDB();
$rows = $db->query('SELECT id, name, code FROM sitios ORDER BY id ASC')->fetchAll();
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Code: {$r['code']}\n";
}

