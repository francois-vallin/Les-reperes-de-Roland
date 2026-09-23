<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\V5Database;
use App\V5Repository;

header('Content-Type: application/json; charset=utf-8');

try {
    $repository = new V5Repository(V5Database::connection());
    $action = $_REQUEST['action'] ?? 'state';

    if ($action === 'state') {
        echo json_encode(['ok' => true, 'now' => date(DATE_ATOM), 'state' => $repository->tabletState(date('H:i'))], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'dashboard') {
        echo json_encode(['ok' => true, 'tasks' => $repository->tasks(), 'notes' => $repository->notes(), 'activity' => $repository->activity()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'task-action') {
        $repository->toggleTask((int) ($_POST['id'] ?? 0), (string) ($_POST['task_action'] ?? ''));
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'save-task') {
        $repository->saveTask($_POST);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'save-note') {
        $repository->saveNote($_POST);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'start-video-call') {
        echo json_encode(['ok' => true, 'call' => $repository->startVideoCall()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'end-video-call') {
        $repository->endVideoCall();
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'video-signal') {
        $payload = json_decode((string) ($_POST['payload'] ?? ''), true);
        if (!is_array($payload)) throw new InvalidArgumentException('Signal visio invalide.');
        $repository->addVideoSignal((int) ($_POST['call_id'] ?? 0), (string) ($_POST['role'] ?? ''), (string) ($_POST['token'] ?? ''), (string) ($_POST['recipient'] ?? ''), $payload);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'video-signals') {
        echo json_encode(['ok' => true, 'signals' => $repository->videoSignals((int) ($_GET['call_id'] ?? 0), (string) ($_GET['role'] ?? ''), (string) ($_GET['token'] ?? ''), (int) ($_GET['after'] ?? 0))], JSON_UNESCAPED_UNICODE);
        exit;
    }
    throw new InvalidArgumentException('Action inconnue.');
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
