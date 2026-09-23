<?php
declare(strict_types=1);

namespace App;

use PDO;

final class V5Repository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function tabletState(string $time): array
    {
        $today = date('Y-m-d');
        $forced = $this->one('SELECT * FROM tasks WHERE forced = 1 AND active = 1 AND (completed_on IS NULL OR completed_on != ?) ORDER BY updated_at DESC LIMIT 1', [$today]);
        $task = $forced ?: $this->one('SELECT * FROM tasks WHERE active = 1 AND start_time <= ? AND end_time > ? AND (completed_on IS NULL OR completed_on != ?) ORDER BY start_time DESC, id DESC LIMIT 1', [$time, $time, $today]);
        $rolandNote = $this->one("SELECT * FROM notes WHERE active = 1 AND audience = 'roland' AND start_time <= ? AND end_time > ? AND (display_date IS NULL OR display_date = ?) ORDER BY id DESC LIMIT 1", [$time, $time, $today]);
        $staffNote = $this->one("SELECT * FROM notes WHERE active = 1 AND audience = 'intervenant' AND start_time <= ? AND end_time > ? AND (display_date IS NULL OR display_date = ?) ORDER BY id DESC LIMIT 1", [$time, $time, $today]);

        return ['task' => $task ?: null, 'roland_note' => $rolandNote ?: null, 'staff_note' => $staffNote ?: null, 'forced' => (bool) $forced, 'video_call' => $this->videoCall()];
    }

    public function tasks(): array
    {
        $today = date('Y-m-d');
        $statement = $this->pdo->prepare('SELECT *, CASE WHEN completed_on = ? THEN 1 ELSE 0 END AS completed_today FROM tasks ORDER BY start_time, id');
        $statement->execute([$today]);
        return $statement->fetchAll();
    }

    public function notes(): array
    {
        return $this->pdo->query('SELECT * FROM notes ORDER BY start_time, id')->fetchAll();
    }

    public function activity(): array
    {
        return $this->pdo->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 15')->fetchAll();
    }

    public function startVideoCall(): array
    {
        $this->pdo->exec('UPDATE video_calls SET active = 0');
        $callerToken = bin2hex(random_bytes(24));
        $tabletToken = bin2hex(random_bytes(24));
        $this->pdo->prepare('INSERT INTO video_calls (room_url, caller_token, tablet_token, active) VALUES (?, ?, ?, 1)')->execute(['internal', $callerToken, $tabletToken]);
        $this->log('Visio', 'Appel visio à décrochage automatique demandé.');
        return ['id' => (int) $this->pdo->lastInsertId(), 'caller_token' => $callerToken];
    }

    public function endVideoCall(): void
    {
        $this->pdo->exec('UPDATE video_calls SET active = 0');
        $this->log('Visio', 'Appel visio terminé depuis l’administration.');
    }

    public function toggleTask(int $id, string $action): void
    {
        $today = date('Y-m-d');
        $map = [
            'complete' => ['UPDATE tasks SET completed_on = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$today, $id]],
            'uncomplete' => ['UPDATE tasks SET completed_on = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$id]],
            'activate' => ['UPDATE tasks SET active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$id]],
            'deactivate' => ['UPDATE tasks SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$id]],
            'force' => ['UPDATE tasks SET forced = CASE WHEN id = ? THEN 1 ELSE 0 END, updated_at = CURRENT_TIMESTAMP', [$id]],
            'unforce' => ['UPDATE tasks SET forced = 0, updated_at = CURRENT_TIMESTAMP', []],
        ];
        if (!isset($map[$action])) {
            throw new \InvalidArgumentException('Action inconnue.');
        }
        [$sql, $values] = $map[$action];
        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
        $this->log('Tâche', 'Action « ' . $action . ' » appliquée à la tâche #' . $id . '.');
    }

    public function saveTask(array $data): void
    {
        $color = $this->color((string) ($data['color'] ?? 'calm'));
        $this->time((string) ($data['start_time'] ?? ''));
        $this->time((string) ($data['end_time'] ?? ''));
        $values = [
            trim((string) $data['name']), trim((string) $data['title']), trim((string) $data['details']),
            $data['start_time'], $data['end_time'], $color, max(15, (int) $data['refresh_seconds']), trim((string) $data['routine']), $this->imagePath((string) ($data['image_path'] ?? '')),
        ];
        if ($values[0] === '' || $values[1] === '') {
            throw new \InvalidArgumentException('Le nom et le titre sont obligatoires.');
        }
        if (!empty($data['id'])) {
            $values[] = (int) $data['id'];
            $this->pdo->prepare('UPDATE tasks SET name=?, title=?, details=?, start_time=?, end_time=?, color=?, refresh_seconds=?, routine=?, image_path=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute($values);
            $this->log('Tâche', 'Tâche mise à jour : ' . $values[0] . '.');
            return;
        }
        $this->pdo->prepare('INSERT INTO tasks (name, title, details, start_time, end_time, color, refresh_seconds, routine, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute($values);
        $this->log('Tâche', 'Nouvelle tâche ajoutée : ' . $values[0] . '.');
    }

    public function saveNote(array $data): void
    {
        $audience = (string) ($data['audience'] ?? '');
        if (!in_array($audience, ['roland', 'intervenant'], true)) {
            throw new \InvalidArgumentException('Destinataire invalide.');
        }
        $this->time((string) ($data['start_time'] ?? ''));
        $this->time((string) ($data['end_time'] ?? ''));
        $displayDate = trim((string) ($data['display_date'] ?? ''));
        if ($displayDate !== '' && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $displayDate)) {
            throw new \InvalidArgumentException('Date invalide.');
        }
        $values = [$audience, trim((string) $data['title']), trim((string) $data['body']), $data['start_time'], $data['end_time'], $this->color((string) ($data['color'] ?? 'info')), $displayDate ?: null];
        if ($values[1] === '' || $values[2] === '') {
            throw new \InvalidArgumentException('Le titre et le message sont obligatoires.');
        }
        $this->pdo->prepare('INSERT INTO notes (audience, title, body, start_time, end_time, color, display_date) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute($values);
        $this->log('Information', 'Nouvelle information ajoutée : ' . $values[1] . '.');
    }

    private function one(string $sql, array $values): array|false
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
        return $statement->fetch();
    }

    public function addVideoSignal(int $callId, string $role, string $token, string $recipient, array $payload): void
    {
        $this->verifyVideoRole($callId, $role, $token);
        if (!in_array($recipient, ['caller', 'tablet'], true)) throw new \InvalidArgumentException('Destinataire visio invalide.');
        $this->pdo->prepare('INSERT INTO video_signals (call_id, recipient_role, payload) VALUES (?, ?, ?)')->execute([$callId, $recipient, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    }

    public function videoSignals(int $callId, string $role, string $token, int $after): array
    {
        $this->verifyVideoRole($callId, $role, $token);
        $statement = $this->pdo->prepare('SELECT id, payload FROM video_signals WHERE call_id = ? AND recipient_role = ? AND id > ? ORDER BY id');
        $statement->execute([$callId, $role, $after]);
        return array_map(fn(array $signal) => ['id' => (int) $signal['id'], 'payload' => json_decode($signal['payload'], true)], $statement->fetchAll());
    }

    private function videoCall(): array|null
    {
        $statement = $this->pdo->query('SELECT id, tablet_token, requested_at FROM video_calls WHERE active = 1 ORDER BY id DESC LIMIT 1');
        return $statement->fetch() ?: null;
    }

    private function verifyVideoRole(int $callId, string $role, string $token): void
    {
        $call = $this->one('SELECT caller_token, tablet_token, active FROM video_calls WHERE id = ?', [$callId]);
        $expected = $role === 'caller' ? ($call['caller_token'] ?? '') : ($role === 'tablet' ? ($call['tablet_token'] ?? '') : '');
        if (!$call || !(int) $call['active'] || $expected === '' || !hash_equals($expected, $token)) throw new \RuntimeException('Session visio invalide ou terminée.');
    }

    private function time(string $value): void
    {
        if (!preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $value)) {
            throw new \InvalidArgumentException('Horaire invalide.');
        }
    }

    private function color(string $value): string
    {
        $colors = ['calm', 'morning', 'meal', 'alert', 'night', 'staff', 'info'];
        if (!in_array($value, $colors, true)) {
            throw new \InvalidArgumentException('Couleur invalide.');
        }
        return $value;
    }

    private function imagePath(string $value): ?string
    {
        $images = ['', 'assets/img/laver-oreilles1.jpg', 'assets/img/tidej.jpg', 'assets/img/cadu-bl.png', 'assets/img/cadu-br.png'];
        if (!in_array($value, $images, true)) {
            throw new \InvalidArgumentException('Illustration invalide.');
        }
        return $value === '' ? null : $value;
    }

    private function log(string $category, string $message): void
    {
        $this->pdo->prepare('INSERT INTO activity_log (category, message) VALUES (?, ?)')->execute([$category, $message]);
    }
}
