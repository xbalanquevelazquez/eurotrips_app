<?php
if(!defined('VIEWABLE')){ header('HTTP/1.0 404 Not Found'); exit; }

const ROOT_DIR = __DIR__;
const MAX_FILE_SIZE = 50 * 1024 * 1024;
$blocked_extensions = ['php3','php4','php5','php7','php8','phtml','phar','cgi','pl','py','sh','exe','bat','cmd','com'];
function clean_path($path) {
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');
    if ($path === '') return '';
    $parts = explode('/', $path);
    $safe = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') {
            array_pop($safe);
            continue;
        }
        $safe[] = $part;
    }
    return implode('/', $safe);
}
function get_full_path($relative) {
    $relative = clean_path($relative);
    $full = ROOT_DIR . ($relative !== '' ? '/' . $relative : '');
    $real_root = realpath(ROOT_DIR);
    $real_path = realpath($full);
    if ($real_path === false) return false;
    if ($real_path !== $real_root && strpos($real_path, $real_root . DIRECTORY_SEPARATOR) !== 0) return false;
    return $real_path;
}
function format_size($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return number_format($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return number_format($bytes / 1048576, 1) . ' MB';
    return number_format($bytes / 1073741824, 1) . ' GB';
}
$action = $_POST['fm_action'] ?? '';
$current = clean_path($_POST['fm_path'] ?? '');
$current_full = get_full_path($current);
if ($current_full === false || !is_dir($current_full)) {
    $current = '';
    $current_full = ROOT_DIR;
}
if ($action === 'download') {
    $file_path = clean_path($_POST['fm_file'] ?? '');
    $file = get_full_path($file_path);
    if ($file === false || !is_file($file)) {
        http_response_code(404);
        exit('Archivo no encontrado.');
    }
    $filename = basename($file);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . filesize($file));
    header('X-Content-Type-Options: nosniff');
    readfile($file);
    exit;
}
if ($action === 'delete') {
    $file_path = clean_path($_POST['fm_file'] ?? '');
    $file = get_full_path($file_path);
    if ($file === false || !is_file($file)) {
        $upload_error = 'El archivo no existe o no se puede eliminar.';
    } elseif (unlink($file)) {
        $upload_message = 'Archivo eliminado correctamente.';
    } else {
        $upload_error = 'No fue posible eliminar el archivo.';
    }
}
$upload_message = '';
$upload_error = '';
if ($action === 'upload') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'No se pudo recibir el archivo.';
    } elseif ($_FILES['file']['size'] > MAX_FILE_SIZE) {
        $upload_error = 'El archivo supera el límite permitido de 50 MB.';
    } else {
        $original_name = basename($_FILES['file']['name']);
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if (in_array($extension, $blocked_extensions, true)) {
            $upload_error = 'Este tipo de archivo no está permitido.';
        } else {
            $filename = preg_replace('/[^A-Za-z0-9._-]/u', '_', $original_name);
            $destination = $current_full . DIRECTORY_SEPARATOR . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                $upload_message = 'Archivo subido correctamente.';
            } else {
                $upload_error = 'No fue posible guardar el archivo.';
            }
        }
    }
}
$items = scandir($current_full);
$directories = [];
$files = [];
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $full = $current_full . DIRECTORY_SEPARATOR . $item;
    if (is_link($full)) continue;
    if (is_dir($full)) {
        $directories[] = $item;
    } elseif (is_file($full)) {
        $files[] = $item;
    }
}
natcasesort($directories);
natcasesort($files);
$parent = '';
if ($current !== '') {
    $parts = explode('/', $current);
    array_pop($parts);
    $parent = implode('/', $parts);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrador de archivos | Eurotrips</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
:root {
    --et-blue: #1976D2;
    --et-blue-dark: #125EA8;
    --et-blue-light: #EAF4FD;
    --et-bg: #F5F7FA;
    --et-border: #E2E7EC;
    --et-text: #263238;
    --et-muted: #7A8792;
    --et-success: #2E7D32;
    --et-success-bg: #E8F5E9;
    --et-error: #C62828;
    --et-error-bg: #FFEBEE;
}
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    background: var(--et-bg);
    font-family: Arial, Helvetica, sans-serif;
    color: var(--et-text);
}
.file-manager {
    width: 100%;
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px 40px;
}
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.page-title {
    display: flex;
    align-items: center;
    gap: 14px;
}
.page-title-icon {
    width: 46px;
    height: 46px;
    border-radius: 10px;
    background: var(--et-blue-light);
    color: var(--et-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.page-title h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}
.page-title p {
    margin: 4px 0 0;
    color: var(--et-muted);
    font-size: 13px;
}
.upload-card {
    background: #fff;
    border: 1px solid var(--et-border);
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 18px;
}
.breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;
    margin-bottom: 18px;
    font-size: 14px;
}
.breadcrumb button {
    border: 0;
    background: transparent;
    color: var(--et-blue);
    cursor: pointer;
    padding: 0;
    font-size: 14px;
}
.breadcrumb button:hover {
    color: var(--et-blue-dark);
}
.breadcrumb .separator {
    color: #B0B8BF;
}
.current-folder {
    color: var(--et-text);
    font-weight: 500;
}
.upload-area {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}
.upload-form {
    display: flex;
    align-items: center;
    gap: 10px;
}
.file-input {
    border: 1px solid var(--et-border);
    border-radius: 6px;
    padding: 9px;
    background: #FAFBFC;
    font-size: 13px;
}
.btn-upload {
    border: 0;
    background: var(--et-blue);
    color: #fff;
    border-radius: 6px;
    padding: 10px 16px;
    cursor: pointer;
    font-size: 13px;
    transition: background .2s ease;
}
.btn-upload:hover {
    background: var(--et-blue-dark);
}
.btn-upload i {
    margin-right: 6px;
}
.btn-back {
    border: 0;
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--et-blue);
    text-decoration: none;
    font-size: 13px;
    padding: 0;
    cursor: pointer;
}
.btn-back:hover {
    color: var(--et-blue-dark);
}
.alert {
    border-radius: 7px;
    padding: 11px 15px;
    margin-bottom: 18px;
    font-size: 13px;
}
.alert-success {
    background: var(--et-success-bg);
    color: var(--et-success);
}
.alert-error {
    background: var(--et-error-bg);
    color: var(--et-error);
}
.file-list {
    background: #fff;
    border: 1px solid var(--et-border);
    border-radius: 10px;
    overflow: hidden;
}
.file-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 150px 170px;
    align-items: center;
    min-height: 58px;
    padding: 0 20px;
    border-bottom: 1px solid #EEF1F4;
}
.file-row:last-child {
    border-bottom: 0;
}
.file-row.header {
    min-height: 42px;
    background: #F8FAFC;
    color: var(--et-muted);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    font-weight: 600;
}
.file-name {
    display: flex;
    align-items: center;
    min-width: 0;
}
.file-icon {
    width: 34px;
    color: var(--et-blue);
    font-size: 17px;
    flex-shrink: 0;
}
.file-name button {
    border: 0;
    background: transparent;
    padding: 0;
    color: var(--et-text);
    cursor: pointer;
    font-size: 14px;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.file-name button:hover {
    color: var(--et-blue);
}
.folder-name {
    font-weight: 500;
}
.file-meta {
    color: var(--et-muted);
    font-size: 12px;
}
.download-form {
    justify-self: end;
}
.download-btn {
    border: 0;
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--et-blue);
    text-decoration: none;
    cursor: pointer;
    font-size: 12px;
}
.download-btn:hover {
    color: var(--et-blue-dark);
}
.empty {
    padding: 55px 20px;
    text-align: center;
    color: var(--et-muted);
}
.empty i {
    display: block;
    font-size: 30px;
    margin-bottom: 12px;
    color: #B8C5D0;
}
.empty span {
    font-size: 13px;
}
.hidden-form {
    display: none;
}
.file-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 14px;
}
.delete-form {
    display: inline;
}
.delete-btn {
    border: 0;
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #C62828;
    cursor: pointer;
    font-size: 12px;
}
.delete-btn:hover {
    color: #8E0000;
}
@media (max-width: 700px) {
    .file-manager {
        margin: 20px auto;
        padding: 0 12px 30px;
    }
    .page-header {
        align-items: flex-start;
    }
    .page-title h1 {
        font-size: 20px;
    }
    .upload-area {
        display: block;
    }
    .upload-form {
        display: block;
    }
    .file-input {
        width: 100%;
        margin-bottom: 10px;
    }
    .btn-upload {
        width: 100%;
    }
    .file-row {
        grid-template-columns: minmax(0, 1fr) 80px;
        padding: 0 14px;
    }
    .file-row .modified,
    .file-row.header .modified {
        display: none;
    }
    .download-form {
        display: inline;
    }
    .download-btn span {
        display: none;
    }
}
</style>
</head>
<body>
<div class="file-manager">
    <div class="page-header">
        <div class="page-title">
            <div class="page-title-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <div>
                <h1>Administrador de archivos</h1>
                <p>Gestión de archivos y directorios del sistema</p>
            </div>
        </div>
    </div>
    <?php if ($upload_message): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($upload_message) ?>
        </div>
    <?php endif; ?>
    <?php if ($upload_error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($upload_error) ?>
        </div>
    <?php endif; ?>
    <div class="upload-card">
        <div class="breadcrumb">
            <form method="post" class="hidden-form">
                <input type="hidden" name="fm_action" value="navigate">
                <input type="hidden" name="fm_path" value="">
            </form>
            <button type="submit" form="root-navigation">
                <i class="fa-solid fa-house"></i>
            </button>
            <form method="post" id="root-navigation" class="hidden-form">
                <input type="hidden" name="fm_action" value="navigate">
                <input type="hidden" name="fm_path" value="">
            </form>
            <?php if ($current !== ''): ?>
                <?php
                $crumb = '';
                foreach (explode('/', $current) as $part):
                    $crumb .= ($crumb !== '' ? '/' : '') . $part;
                ?>
                    <span class="separator"><i class="fa-solid fa-chevron-right"></i></span>
                    <form method="post">
                        <input type="hidden" name="fm_action" value="navigate">
                        <input type="hidden" name="fm_path" value="<?= htmlspecialchars($crumb) ?>">
                        <button type="submit"><?= htmlspecialchars($part) ?></button>
                    </form>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="separator"><i class="fa-solid fa-chevron-right"></i></span>
                <span class="current-folder">Directorio raíz</span>
            <?php endif; ?>
        </div>
        <div class="upload-area">
            <?php if ($current !== ''): ?>
                <form method="post">
                    <input type="hidden" name="fm_action" value="navigate">
                    <input type="hidden" name="fm_path" value="<?= htmlspecialchars($parent) ?>">
                    <button class="btn-back" type="submit">
                        <i class="fa-solid fa-arrow-left"></i>
                        Regresar
                    </button>
                </form>
            <?php else: ?>
                <span class="btn-back">
                    <i class="fa-solid fa-folder"></i>
                    Directorio raíz
                </span>
            <?php endif; ?>
            <form class="upload-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="fm_action" value="upload">
                <input type="hidden" name="fm_path" value="<?= htmlspecialchars($current) ?>">
                <input class="file-input" type="file" name="file" required>
                <button class="btn-upload" type="submit">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Subir archivo
                </button>
            </form>
        </div>
    </div>
    <div class="file-list">
        <div class="file-row header">
            <div>Nombre</div>
            <div>Tamaño</div>
            <div class="modified">Modificado</div>
        </div>
        <?php if ($current !== ''): ?>
            <div class="file-row">
                <div class="file-name">
                    <div class="file-icon">
                        <i class="fa-solid fa-arrow-turn-up"></i>
                    </div>
                    <form method="post">
                        <input type="hidden" name="fm_action" value="navigate">
                        <input type="hidden" name="fm_path" value="<?= htmlspecialchars($parent) ?>">
                        <button class="folder-name" type="submit">Directorio anterior</button>
                    </form>
                </div>
                <div class="file-meta">—</div>
                <div class="file-meta modified">—</div>
            </div>
        <?php endif; ?>
        <?php foreach ($directories as $directory): ?>
            <?php $directory_path = $current !== '' ? $current . '/' . $directory : $directory; ?>
            <div class="file-row">
                <div class="file-name">
                    <div class="file-icon">
                        <i class="fa-solid fa-folder"></i>
                    </div>
                    <form method="post">
                        <input type="hidden" name="fm_action" value="navigate">
                        <input type="hidden" name="fm_path" value="<?= htmlspecialchars($directory_path) ?>">
                        <button class="folder-name" type="submit">
                            <?= htmlspecialchars($directory) ?>
                        </button>
                    </form>
                </div>
                <div class="file-meta">Directorio</div>
                <div class="file-meta modified">—</div>
            </div>
        <?php endforeach; ?>
        <?php foreach ($files as $file): ?>
            <?php
            $full = $current_full . DIRECTORY_SEPARATOR . $file;
            $file_path = $current !== '' ? $current . '/' . $file : $file;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $file_icon = 'fa-file';
            if (in_array($extension, ['jpg','jpeg','png','gif','webp','svg'], true)) $file_icon = 'fa-file-image';
            elseif (in_array($extension, ['pdf'], true)) $file_icon = 'fa-file-pdf';
            elseif (in_array($extension, ['doc','docx'], true)) $file_icon = 'fa-file-word';
            elseif (in_array($extension, ['xls','xlsx','csv'], true)) $file_icon = 'fa-file-excel';
            elseif (in_array($extension, ['zip','rar','7z'], true)) $file_icon = 'fa-file-zipper';
            elseif (in_array($extension, ['txt','log'], true)) $file_icon = 'fa-file-lines';
            ?>
            <div class="file-row">
                <div class="file-name">
                    <div class="file-icon">
                        <i class="fa-solid <?= $file_icon ?>"></i>
                    </div>
                    <form method="post">
                        <input type="hidden" name="fm_action" value="download">
                        <input type="hidden" name="fm_file" value="<?= htmlspecialchars($file_path) ?>">
                        <button type="submit">
                            <?= htmlspecialchars($file) ?>
                        </button>
                    </form>
                </div>
                <div class="file-meta"><?= format_size(filesize($full)) ?></div>
                <div class="file-meta modified">
                    <?= date('d/m/Y H:i', filemtime($full)) ?>
                    <div class="file-actions">
                        <form method="post" class="download-form">
                            <input type="hidden" name="fm_action" value="download">
                            <input type="hidden" name="fm_file" value="<?= htmlspecialchars($file_path) ?>">
                            <button class="download-btn" type="submit" title="Descargar">
                                <i class="fa-solid fa-download"></i>
                                <span>Descargar</span>
                            </button>
                        </form>
                        <form method="post" class="delete-form" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este archivo?');">
                            <input type="hidden" name="fm_action" value="delete">
                            <input type="hidden" name="fm_file" value="<?= htmlspecialchars($file_path) ?>">
                            <button class="delete-btn" type="submit" title="Eliminar">
                                <i class="fa-solid fa-trash"></i>
                                <span>Eliminar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($directories) && empty($files)): ?>
            <div class="empty">
                <i class="fa-regular fa-folder-open"></i>
                <span>Este directorio está vacío.</span>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>