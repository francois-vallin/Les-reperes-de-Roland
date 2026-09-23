<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$view = $_GET['view'] ?? 'tablet';
if (!in_array($view, ['tablet', 'admin'], true)) {
    $view = 'tablet';
}
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Les repères de Roland — rester chez soi, entouré</title>
    <link rel="stylesheet" href="assets/v5.css">
</head>
<body class="v5 <?= $view === 'admin' ? 'admin-page' : 'tablet-page calm' ?>">
<?php if ($view === 'tablet'): ?>
    <main id="tablet" aria-live="polite" aria-label="Terminal de repères de Roland, sans action tactile requise">
        <header class="tablet-header">
            <p id="date" class="date"></p>
            <time id="clock" class="clock"></time>
        </header>
        <section id="tablet-message" class="tablet-message">
            <p class="eyebrow" id="message-kind">Repère de la journée</p>
            <h1 id="message-title">Bonjour Roland</h1>
            <p id="message-body">La journée se prépare…</p>
            <img id="task-image" class="task-image" hidden alt="" />
            <p id="message-routine" class="routine"></p>
        </section>
        <section id="staff-note" class="staff-note" hidden>
            <p class="eyebrow">Information pour un intervenant</p>
            <h2 id="staff-title"></h2>
            <p id="staff-body"></p>
        </section>
        <section id="video-call" class="video-call" hidden aria-label="Appel vidéo en cours">
            <iframe id="video-frame" title="Appel vidéo" allow="camera; microphone; autoplay; fullscreen; display-capture" allowfullscreen></iframe>
        </section>
        <footer><span>Avec toi, même quand nous ne sommes pas là.</span><span>À la mémoire de Roland Vallin.</span><a href="mailto:fjvallin2024@gmail.com">Contact</a></footer>
    </main>
<?php else: ?>
    <main id="admin" class="admin-shell">
        <header class="admin-header">
            <div><p class="eyebrow">Les repères de Roland</p><h1>Préparer des repères, simplement.</h1><p class="admin-intro">Une consigne claire au bon moment peut rendre une journée plus sereine.</p></div>
            <div class="header-actions"><a href="index.php" class="secondary-link">Voir l’écran tablette</a></div>
        </header>
        <p id="feedback" role="status"></p>
        <section class="admin-grid">
            <section class="panel"><p class="section-kicker">Le rythme du jour</p><h2>Tâches de la journée</h2><div id="tasks"></div></section>
            <section class="panel"><p class="section-kicker">Ajouter un repère</p><h2>Nouvelle tâche</h2>
                <form id="task-form">
                    <label>Nom<input name="name" required placeholder="Déjeuner"></label>
                    <label>Titre<input name="title" required placeholder="C’est l’heure de manger"></label>
                    <label>Détails<textarea name="details" rows="4" placeholder="Consigne claire pour Roland"></textarea></label>
                    <div class="form-row"><label>Début<input type="time" name="start_time" value="12:00" required></label><label>Fin<input type="time" name="end_time" value="12:30" required></label></div>
                    <div class="form-row"><label>Couleur<select name="color"><option value="calm">Calme</option><option value="morning">Matin</option><option value="meal">Repas</option><option value="alert">Alerte</option><option value="night">Soir</option></select></label><label>Actualisation (s)<input type="number" name="refresh_seconds" value="60" min="15"></label></div>
                    <label>Routine<input name="routine" placeholder="Matin, repas, soir…"></label>
                    <label>Illustration <select name="image_path"><option value="">Aucune</option><option value="assets/img/laver-oreilles1.jpg">Oreilles et appareils auditifs</option><option value="assets/img/tidej.jpg">Petit-déjeuner : lait, café et gâteaux</option><option value="assets/img/cadu-bl.png">Passage de l’infirmière blonde</option><option value="assets/img/cadu-br.png">Passage de l’infirmière brune</option></select></label>
                    <button>Ajouter la tâche</button>
                </form>
            </section>
            <section class="panel"><p class="section-kicker">Ce qu’il faut savoir</p><h2>Notes et informations</h2><div id="notes"></div></section>
            <section class="panel"><p class="section-kicker">Un message ponctuel</p><h2>Nouvelle information</h2>
                <form id="note-form">
                    <label>Destinataire<select name="audience"><option value="roland">Roland</option><option value="intervenant">Intervenant</option></select></label>
                    <label>Titre<input name="title" required placeholder="Information pour un intervenant"></label>
                    <label>Message<textarea name="body" rows="6" required placeholder="Message court et utile"></textarea></label>
                    <div class="form-row"><label>Début<input type="time" name="start_time" value="15:30" required></label><label>Fin<input type="time" name="end_time" value="16:00" required></label></div>
                    <label>Date précise <input type="date" name="display_date"><span class="hint">Laissez vide pour un message récurrent.</span></label>
                    <label>Couleur<select name="color"><option value="info">Information</option><option value="calm">Rassurant</option><option value="staff">Intervenant</option><option value="alert">Alerte</option></select></label>
                    <button>Ajouter l’information</button>
                </form>
            </section>
            <section class="panel"><p class="section-kicker">Gagner du temps</p><h2>Modèles prêts à adapter</h2>
                <div class="template-actions">
                    <button type="button" data-template="reassure">Rassurer Roland</button>
                    <button type="button" data-template="appointment">Rendez-vous médical</button>
                    <button type="button" data-template="fasting">Préparation d’examen</button>
                    <button type="button" data-template="find">Objet à retrouver</button>
                    <button type="button" data-task-template="nurse-blonde">Passage infirmière blonde</button>
                    <button type="button" data-task-template="nurse-brunette">Passage infirmière brune</button>
                </div>
                <p class="hint">Un clic remplit le formulaire concerné ; le contenu et l’horaire restent modifiables avant l’ajout.</p>
            </section>
            <section class="panel"><p class="section-kicker">Garder le fil</p><h2>Journal de la journée</h2><div id="activity"></div></section>
            <section class="panel future-panel" aria-labelledby="future-title">
                <h2 id="future-title">Domotique <span class="future-label">à connecter</span></h2>
                <p>Ces commandes rappellent les fonctions qui existaient autour de Roland. Elles sont désactivées dans la version publique tant qu’aucune maison n’est reliée.</p>
                <div class="template-actions">
                    <button type="button" disabled>Allumer la lumière</button>
                    <button type="button" disabled>Éteindre la lumière</button>
                    <button type="button" disabled>Éclairage de repère</button>
                    <button type="button" disabled>Éteindre la télévision</button>
                    <button type="button" disabled>Sécuriser la cuisine</button>
                    <button type="button" data-video-action="start">Appel visio à décrochage automatique</button>
                    <button type="button" data-video-action="end">Terminer l’appel visio</button>
                    <button type="button" disabled>Déclencher un signal visuel</button>
                    <button type="button" disabled>Voir les caméras autorisées</button>
                    <button type="button" disabled>Lancer VLC sur la télévision</button>
                    <button type="button" disabled>Choisir une chaîne Freebox</button>
                    <button type="button" disabled>Appeler un proche</button>
                    <button type="button" disabled>Déclencher un appel d’urgence</button>
                    <button type="button" disabled>Signaler « j’ai besoin d’aide »</button>
                    <button type="button" disabled>Confirmer le passage d’un professionnel</button>
                    <button type="button" disabled>Préparer la journée de demain</button>
                    <button type="button" disabled>Ouvrir / fermer les volets</button>
                    <button type="button" disabled>Vérifier portes et fenêtres</button>
                    <button type="button" disabled>Voir la température du logement</button>
                    <button type="button" disabled>Cadre photo / vidéo souvenir</button>
                </div>
            </section>
        </section>
    </main>
<?php endif; ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEztfH1prD8f3Bz7bU=" crossorigin="anonymous"></script>
<script src="assets/v5.js"></script>
</body>
</html>
