<?php
/**
 * Ajustes de operación guardados en la base de datos.
 *
 * Son los valores que el administrador cambia sin tocar código: el interruptor
 * del tutor con avatar y su presupuesto mensual de minutos. Los valores por
 * omisión viven en config.php, así el sitio funciona antes del primer guardado.
 */

/** Lee un ajuste. Se memoriza en la petición para no consultar dos veces lo mismo. */
function setting(string $name, string|int|float|bool $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db_all('SELECT name, value FROM app_settings') as $row) {
                $cache[$row['name']] = $row['value'];
            }
        } catch (Throwable $e) {
            $cache = [];                                  // tabla aún sin crear
        }
    }
    if (array_key_exists($name, $cache)) {
        return $cache[$name];
    }
    return is_bool($default) ? ($default ? '1' : '0') : (string) $default;
}

function setting_int(string $name, int $default = 0): int
{
    $v = setting($name, $default);
    return $v === '' ? $default : (int) $v;
}

function setting_on(string $name, bool $default = false): bool
{
    return setting($name, $default) === '1';
}

/** Guarda un ajuste y deja constancia de quién lo cambió. */
function setting_save(string $name, string $value, ?int $userId = null): void
{
    db_exec(
        'INSERT INTO app_settings (name, value, updated_by) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by)',
        [$name, $value, $userId]
    );
}
