<?php
declare(strict_types=1);

namespace App;

use PDO;

final class V5Database
{
    public static function connection(): PDO
    {
        $directory = dirname(__DIR__) . '/data';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $directory . '/help-for-dependent-people.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        self::migrate($pdo);
        self::seed($pdo);

        return $pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    title TEXT NOT NULL,
    details TEXT NOT NULL DEFAULT '',
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    color TEXT NOT NULL DEFAULT 'calm',
    refresh_seconds INTEGER NOT NULL DEFAULT 60,
    routine TEXT NOT NULL DEFAULT '',
    image_path TEXT DEFAULT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    completed_on TEXT DEFAULT NULL,
    forced INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL);
        self::ensureColumn($pdo, 'tasks', 'image_path', 'TEXT DEFAULT NULL');
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    audience TEXT NOT NULL CHECK(audience IN ('roland', 'intervenant')),
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    color TEXT NOT NULL DEFAULT 'info',
    display_date TEXT DEFAULT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL);
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS activity_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    happened_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    category TEXT NOT NULL,
    message TEXT NOT NULL
)
SQL);
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS video_calls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_url TEXT NOT NULL DEFAULT '',
    caller_token TEXT DEFAULT NULL,
    tablet_token TEXT DEFAULT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL);
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS video_signals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    call_id INTEGER NOT NULL,
    recipient_role TEXT NOT NULL,
    payload TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(call_id) REFERENCES video_calls(id) ON DELETE CASCADE
)
SQL);
        self::ensureColumn($pdo, 'notes', 'display_date', 'TEXT DEFAULT NULL');
        self::ensureColumn($pdo, 'video_calls', 'caller_token', 'TEXT DEFAULT NULL');
        self::ensureColumn($pdo, 'video_calls', 'tablet_token', 'TEXT DEFAULT NULL');
    }

    private static function seed(PDO $pdo): void
    {
        $task = $pdo->prepare(
            'INSERT INTO tasks (name, title, details, start_time, end_time, color, refresh_seconds, routine, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if ((int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn() === 0) {
            $examples = [
                ['Réveil', 'Bonjour Papy', 'Il est l’heure de se lever tranquillement. Tes chaussettes sont sur ton lit.', '08:00', '08:25', 'morning', 60, 'Matin', null],
                ['Toilette', 'Toilette et vêtements', 'Lave-toi, puis mets ton pantalon avant ton pull.', '08:25', '09:00', 'morning', 60, 'Matin', null],
                ['Lunettes', 'Pense à mettre tes lunettes', 'Elles sont là pour t’aider à bien voir aujourd’hui.', '09:00', '19:00', 'calm', 300, 'Repères', null],
                ['Déjeuner', 'C’est l’heure de manger', 'Ton repas est dans le micro-ondes. Chauffe-le 3 minutes. Bon appétit Papy.', '12:10', '12:45', 'meal', 30, 'Repas', null],
                ['Dîner', 'C’est l’heure du dîner', 'Regarde la consigne du repas et prends ton temps.', '19:00', '19:40', 'meal', 30, 'Repas', null],
                ['Bonne nuit', 'Bonne nuit', 'Tout va bien. Nous pensons à toi. Tu peux te reposer tranquillement.', '21:00', '23:59', 'night', 300, 'Soir', null],
            ];
            foreach ($examples as $example) {
                $task->execute($example);
            }
        }
        $earTaskExists = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE name = ?');
        $earTaskExists->execute(['Oreilles et appareils auditifs']);
        if ((int) $earTaskExists->fetchColumn() === 0) {
            $task->execute([
                'Oreilles et appareils auditifs',
                'Avant tes appareils auditifs',
                'Lave-toi les oreilles, puis mets tes appareils auditifs. Prends ton temps.',
                '08:40',
                '08:55',
                'morning',
                60,
                'Matin',
                'assets/img/laver-oreilles1.jpg',
            ]);
        }
        $breakfastTaskExists = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE name = ?');
        $breakfastTaskExists->execute(['Petit-déjeuner']);
        if ((int) $breakfastTaskExists->fetchColumn() === 0) {
            $task->execute([
                'Petit-déjeuner',
                'C’est l’heure du petit-déjeuner',
                'Le lait est dans le frigo. Prends aussi le café, les sucres et les gâteaux. Bon appétit Papy.',
                '09:00',
                '09:30',
                'meal',
                60,
                'Repas',
                'assets/img/tidej.jpg',
            ]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn() > 0) {
            return;
        }
        $note = $pdo->prepare(
            'INSERT INTO notes (audience, title, body, start_time, end_time, color) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $note->execute([
            'intervenant',
            'Information pour l’intervenant',
            "Rendez-vous médical à 16 h 00. Roland se plaint de vertiges ou d’un mal de tête — ce n’était pas très clair. Par prudence, la douche a été reportée. Ses appareils auditifs sont de retour et il les porte. Merci et bonne soirée.",
            '15:30',
            '17:00',
            'staff',
        ]);
        $note->execute([
            'roland',
            'Tu peux avoir confiance en toi',
            'Il n’y a pas de problème, pas de raison de s’inquiéter. Nous avons confiance en toi.',
            '10:00',
            '10:15',
            'calm',
        ]);
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        $columns = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array($column, $columns, true)) {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}
