<?php
session_start();

define('VIEWABLE',TRUE);
include_once("../cnf/cnfg.app.php");
include_once("../funct/funcionalidad.php");

$continue = TRUE;

$logs = glob(LOGS_PATH. '*.log');

$archivoSeleccionado = $_GET['log'] ?? '';
$contenido = '';

if ($archivoSeleccionado !== '') {

    // Solo permitimos el nombre del archivo, nunca una ruta completa
    $archivoSeleccionado = basename($archivoSeleccionado);

    $ruta = LOGS_PATH . $archivoSeleccionado;

    if (is_file($ruta) && is_readable($ruta)) {
        $contenido = file_get_contents($ruta);
    } else {
        $contenido = 'No se pudo leer el archivo.';
    }
}
?>
<form method="get">
    <label>Selecciona un log:</label>

    <select name="log">
        <option value="">-- Seleccionar --</option>

        <?php foreach ($logs as $log): ?>
            <?php $nombre = basename($log); ?>

            <option value="<?= htmlspecialchars($nombre) ?>"
                <?= $nombre === $archivoSeleccionado ? 'selected' : '' ?>>
                <?= htmlspecialchars($nombre) ?>
            </option>

        <?php endforeach; ?>
    </select>

    <button type="submit">Ver log</button>
</form>
<?php if ($contenido !== ''): ?>

    <hr>

    <pre style="
        background:#111;
        color:#eee;
        padding:15px;
        overflow:auto;
        max-height:700px;
        white-space:pre-wrap;
    "><?= htmlspecialchars($contenido) ?></pre>

<?php endif; ?>