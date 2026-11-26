<?php

declare(strict_types=1);

/**
 * A tiny web UI for the Product CRUD, used for MANUAL testing.
 *
 * Start it:  composer serve
 *       or:  php -S localhost:8000 -t public public/index.php
 * Then open: http://localhost:8000
 *
 * It uses the SAME ProductService that the unit tests cover,
 * with the real PdoProductRepository and a SQLite file (database/app.sqlite).
 */

use App\PdoProductRepository;
use App\Product;
use App\ProductNotFoundException;
use App\ProductService;
use App\ValidationException;

// Autoload App\ classes (works even before "composer install").
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

// ---------- Database (created on first run) ----------
$dbFile = __DIR__ . '/../database/app.sqlite';
$isNew = !is_file($dbFile);
$pdo = new PDO('sqlite:' . $dbFile);
if ($isNew) {
    $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
}
$service = new ProductService(new PdoProductRepository($pdo));

// ---------- Routing ----------
$method = $_SERVER['REQUEST_METHOD'];
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

try {
    if ($method === 'GET' && $path === '/') {
        redirect('/products');
    } elseif ($method === 'GET' && $path === '/products') {
        listPage($service->list(), $_GET['msg'] ?? null);
    } elseif ($method === 'GET' && $path === '/products/create') {
        formPage('Add product', '/products', ['name' => '', 'price' => '', 'stock' => '0']);
    } elseif ($method === 'POST' && $path === '/products') {
        $input = readInput();
        try {
            $service->create($input['name'], (int) $input['price'], (int) $input['stock']);
            redirect('/products?msg=Product+created');
        } catch (ValidationException $e) {
            http_response_code(422);
            formPage('Add product', '/products', $input, $e->getMessage());
        }
    } elseif ($method === 'GET' && preg_match('#^/products/(\d+)/edit$#', $path, $m)) {
        $p = $service->get((int) $m[1]);
        formPage('Edit product', "/products/{$p->id}", ['name' => $p->name, 'price' => (string) $p->price, 'stock' => (string) $p->stock]);
    } elseif ($method === 'POST' && preg_match('#^/products/(\d+)$#', $path, $m)) {
        $input = readInput();
        try {
            $service->update((int) $m[1], $input['name'], (int) $input['price'], (int) $input['stock']);
            redirect('/products?msg=Product+updated');
        } catch (ValidationException $e) {
            http_response_code(422);
            formPage('Edit product', "/products/{$m[1]}", $input, $e->getMessage());
        }
    } elseif ($method === 'POST' && preg_match('#^/products/(\d+)/delete$#', $path, $m)) {
        $service->delete((int) $m[1]);
        redirect('/products?msg=Product+deleted');
    } else {
        notFound('Page not found.');
    }
} catch (ProductNotFoundException $e) {
    notFound($e->getMessage());
}

// ---------- Helpers ----------
function readInput(): array
{
    return [
        'name' => (string) ($_POST['name'] ?? ''),
        'price' => (string) ($_POST['price'] ?? ''),
        'stock' => (string) ($_POST['stock'] ?? '0'),
    ];
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function notFound(string $message): void
{
    http_response_code(404);
    layout('Not found', '<p class="error">' . e($message) . '</p><p><a href="/products">Back to products</a></p>');
}

/** @param Product[] $products */
function listPage(array $products, ?string $msg): void
{
    $html = $msg ? '<p class="ok">' . e($msg) . '</p>' : '';
    $html .= '<p><a class="btn" href="/products/create">+ Add product</a></p>';

    if ($products === []) {
        $html .= '<p>No products yet.</p>';
    } else {
        $html .= '<table><tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th></th></tr>';
        foreach ($products as $p) {
            $html .= '<tr>'
                . '<td>' . $p->id . '</td>'
                . '<td>' . e($p->name) . '</td>'
                . '<td>' . number_format($p->price) . '</td>'
                . '<td>' . $p->stock . '</td>'
                . '<td class="actions"><a href="/products/' . $p->id . '/edit">Edit</a>'
                . '<form method="post" action="/products/' . $p->id . '/delete" onsubmit="return confirm(\'Delete this product?\')">'
                . '<button type="submit">Delete</button></form></td>'
                . '</tr>';
        }
        $html .= '</table>';
    }

    layout('Products', $html);
}

function formPage(string $title, string $action, array $values, ?string $error = null): void
{
    $html = $error ? '<p class="error">' . e($error) . '</p>' : '';
    $html .= '<form method="post" action="' . e($action) . '">'
        . '<label>Name <input name="name" value="' . e($values['name']) . '"></label>'
        . '<label>Price <input name="price" type="number" value="' . e($values['price']) . '"></label>'
        . '<label>Stock <input name="stock" type="number" value="' . e($values['stock']) . '"></label>'
        . '<button type="submit" class="btn">Save</button> <a href="/products">Cancel</a>'
        . '</form>';

    layout($title, $html);
}

function layout(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($title) . '</title><style>'
        . 'body{font-family:Calibri,Carlito,Arial,sans-serif;max-width:760px;margin:40px auto;padding:0 16px;color:#14213D;background:#F7F6F1}'
        . 'table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:8px;border-bottom:1px solid #ddd}'
        . 'th{background:#14213D;color:#fff}label{display:block;margin:12px 0}input{display:block;padding:6px;width:300px}'
        . '.btn{background:#1F7A50;color:#fff;padding:8px 14px;border:0;border-radius:6px;text-decoration:none;cursor:pointer}'
        . '.error{background:#F6E3D6;color:#8A2E17;padding:10px;border-radius:6px}'
        . '.ok{background:#DDF1E6;color:#155C3B;padding:10px;border-radius:6px}'
        . '.actions{display:flex;gap:12px}.actions form{margin:0}'
        . '</style></head><body><h1>' . e($title) . '</h1>' . $body . '</body></html>';
}
