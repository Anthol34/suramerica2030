<?php
ob_start();
session_start();

// 1. Cargar dependencias de Composer (PHPMailer, DomPDF)
if(file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

// 2. AUTOLOADER MÁGICO: Carga automáticamente cualquier archivo de la carpeta "src/"
spl_autoload_register(function ($class_name) {
    // Usamos __DIR__ para garantizar que la ruta sea absoluta y no falle en AppServ
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Procesar la URL solicitada
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url_parts = explode('/', $url);

$moduloName = ucfirst(strtolower($url_parts[0])); // Ej: 'Auth' o 'Encomiendas'
$controllerName = $moduloName . 'Controller';     // Ej: 'AuthController'
$methodName = isset($url_parts[1]) ? strtolower(str_replace('-', '', $url_parts[1])) : 'index'; 

// Alias rápido para escaneo directo /suramerica2030/escanear
if ($moduloName === 'Escanear') {
    $moduloName = 'Ventas';
    $controllerName = 'VentasController';
    $methodName = 'escanear';
}

// 4. Candado de Seguridad Global (Manejo de Sesiones)
// Rutas públicas autorizadas para consulta y verificación de comprobantes con código QR
$isPublicRoute = ($moduloName === 'Auth') || 
                 ($moduloName === 'Ventas' && in_array($methodName, ['ticket', 'consultar', 'escanear']));

if (!isset($_SESSION['user_id']) && !$isPublicRoute) {
    header("Location: /suramerica2030/auth");
    exit();
}

// Sincronizar permiso de edición de precios si falta en sesión activa
if (isset($_SESSION['user_id']) && !isset($_SESSION['p_editar_precios'])) {
    if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) {
        $_SESSION['p_editar_precios'] = 1;
    } else {
        try {
            $dbCheck = (new \Platform\Storage\Database())->getConnection();
            $stmtR = $dbCheck->prepare("SELECT P_EDITAR_PRECIOS FROM ROL WHERE ID_ROL = ?");
            $stmtR->execute([$_SESSION['rol_id'] ?? 0]);
            $_SESSION['p_editar_precios'] = (int)$stmtR->fetchColumn();
        } catch (\Exception $e) {
            $_SESSION['p_editar_precios'] = 0;
        }
    }
}

// 5. Ubicar y ejecutar el Controlador dentro de la nueva arquitectura Vertical Slice
$controllerFile = '../src/Modules/' . $moduloName . '/' . $controllerName . '.php';
$controllerClass = "Modules\\$moduloName\\$controllerName";

if (file_exists($controllerFile)) {
    // Instanciamos usando su Namespace
    $controller = new $controllerClass();
    
    if (method_exists($controller, $methodName)) {
        call_user_func_array([$controller, $methodName], array_slice($url_parts, 2));
    } else {
        echo "<h1>Error 404: Método '{$methodName}' no encontrado en el módulo '{$moduloName}'.</h1>";
    }
} else {
    // Redirección de seguridad si el módulo solicitado no existe
    if (isset($_SESSION['user_id'])) {
        header("Location: /suramerica2030/dashboard");
    } else {
        header("Location: /suramerica2030/auth");
    }
    exit();
}
?>