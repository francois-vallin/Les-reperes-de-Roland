(function ($) {
  'use strict';

  function clock() {
    var now = new Date();
    $('#clock').text(now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }));
    $('#date').text(now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
  }

  function escapeHtml(value) {
    return $('<div>').text(value || '').html();
  }

  function tablet() {
    if (!$('#tablet').length) return;
    $.getJSON('api.php', { action: 'state' }).done(function (payload) {
      var state = payload.state;
      var task = state.task;
      var note = state.roland_note;
      var staffNote = state.staff_note;
      var color = (task && task.color) || (note && note.color) || 'calm';
      $('body').removeClass('calm morning meal alert night staff info').addClass(color);
      $('#message-kind').text(state.forced ? 'Consigne prioritaire' : (task ? 'Repère de la journée' : 'Tout va bien'));
      $('#message-title').text(task ? task.title : (note && note.audience === 'roland' ? note.title : 'Bonjour Roland'));
      $('#message-body').text(task ? task.details : (note && note.audience === 'roland' ? note.body : 'Il n’y a pas de raison de s’inquiéter.'));
      if (task && task.image_path) {
        $('#task-image').attr('src', task.image_path).attr('alt', 'Illustration : ' + task.title).prop('hidden', false);
      } else {
        $('#task-image').attr('src', '').attr('alt', '').prop('hidden', true);
      }
      $('#message-routine').text(task && task.routine ? 'Routine : ' + task.routine : '');
      if (staffNote) {
        $('#staff-note').prop('hidden', false);
        $('#staff-title').text(staffNote.title);
        $('#staff-body').text(staffNote.body);
      } else {
        $('#staff-note').prop('hidden', true);
      }
      if (state.video_call) {
        var videoUrl = 'visio.php?call=' + encodeURIComponent(state.video_call.id) + '&role=tablet&token=' + encodeURIComponent(state.video_call.tablet_token);
        if ($('#video-frame').attr('src') !== videoUrl) $('#video-frame').attr('src', videoUrl);
        $('#video-call').prop('hidden', false);
      } else {
        $('#video-call').prop('hidden', true);
        $('#video-frame').attr('src', '');
      }
      var refresh = 10000;
      window.clearTimeout(window.tabletRefresh);
      window.tabletRefresh = window.setTimeout(tablet, refresh);
    }).fail(function () { $('#message-title').text('La tablette se reconnecte…'); });
  }

  function dashboard() {
    if (!$('#admin').length) return;
    $.getJSON('api.php', { action: 'dashboard' }).done(function (payload) {
      $('#tasks').html(payload.tasks.map(function (task) {
        var done = Number(task.completed_today) === 1;
        return '<article class="task ' + (!Number(task.active) ? 'inactive ' : '') + (Number(task.forced) ? 'forced' : '') + '"><h3>' + escapeHtml(task.name) + '</h3><p class="meta">' + escapeHtml(task.start_time) + '–' + escapeHtml(task.end_time) + ' · ' + escapeHtml(task.routine || 'Sans routine') + ' · actualisation ' + Number(task.refresh_seconds) + ' s' + (task.image_path ? ' · illustration' : '') + '</p><p>' + escapeHtml(task.title) + '</p><div class="actions"><button data-task="' + task.id + '" data-action="' + (done ? 'uncomplete' : 'complete') + '">' + (done ? 'Pas fait' : 'Fait') + '</button><button class="warn" data-task="' + task.id + '" data-action="force">Afficher maintenant</button><button class="muted" data-task="' + task.id + '" data-action="' + (Number(task.active) ? 'deactivate' : 'activate') + '">' + (Number(task.active) ? 'Désactiver' : 'Activer') + '</button></div></article>';
      }).join(''));
      $('#notes').html(payload.notes.map(function (note) {
        return '<article class="note ' + escapeHtml(note.color) + '"><h3>' + escapeHtml(note.title) + '</h3><p class="meta">' + escapeHtml(note.audience) + ' · ' + escapeHtml(note.start_time) + '–' + escapeHtml(note.end_time) + (note.display_date ? ' · ' + escapeHtml(note.display_date) : ' · tous les jours') + '</p><p>' + escapeHtml(note.body) + '</p></article>';
      }).join(''));
      $('#activity').html(payload.activity.map(function (entry) {
        return '<div class="activity-item"><strong>' + escapeHtml(entry.category) + '</strong> · ' + escapeHtml(entry.message) + '<br><span class="meta">' + escapeHtml(entry.happened_at) + '</span></div>';
      }).join('') || '<p>Aucune action enregistrée pour le moment.</p>');
    });
  }

  function feedback(message, error) { $('#feedback').text(message).css('color', error ? '#b22222' : '#0a6138'); }
  $(document).on('click', '[data-task]', function () {
    $.post('api.php?action=task-action', { id: $(this).data('task'), task_action: $(this).data('action') }).done(function () { dashboard(); }).fail(function (xhr) { feedback(xhr.responseJSON && xhr.responseJSON.error || 'Action impossible.', true); });
  });
  $('#task-form').on('submit', function (event) { event.preventDefault(); $.post('api.php?action=save-task', $(this).serialize()).done(function () { this.reset(); dashboard(); feedback('Tâche ajoutée.'); }.bind(this)).fail(function (xhr) { feedback(xhr.responseJSON && xhr.responseJSON.error || 'Enregistrement impossible.', true); }); });
  $('#note-form').on('submit', function (event) { event.preventDefault(); $.post('api.php?action=save-note', $(this).serialize()).done(function () { this.reset(); dashboard(); feedback('Information ajoutée.'); }.bind(this)).fail(function (xhr) { feedback(xhr.responseJSON && xhr.responseJSON.error || 'Enregistrement impossible.', true); }); });
  $(document).on('click', '[data-template]', function () {
    var templates = {
      reassure: { audience: 'roland', title: 'Tu peux avoir confiance en toi', body: 'Il n’y a pas de problème, pas de raison de s’inquiéter. Nous avons confiance en toi.', start: '10:00', end: '10:15', color: 'calm' },
      appointment: { audience: 'intervenant', title: 'Rendez-vous médical', body: 'Rendez-vous médical aujourd’hui. Merci de vérifier avec Roland qu’il est prêt et de nous signaler toute difficulté.', start: '15:30', end: '16:30', color: 'staff' },
      fasting: { audience: 'roland', title: 'Préparation d’examen', body: 'Ne pas manger ou boire, sauf de l’eau, selon les consignes du médecin pour l’examen.', start: '18:00', end: '20:30', color: 'alert' },
      find: { audience: 'roland', title: 'Petit repère', body: 'Ce que tu cherches est sur ton lit. Va voir tranquillement.', start: '08:00', end: '08:20', color: 'info' }
    };
    var template = templates[$(this).data('template')];
    var form = $('#note-form');
    form.find('[name=audience]').val(template.audience); form.find('[name=title]').val(template.title); form.find('[name=body]').val(template.body); form.find('[name=start_time]').val(template.start); form.find('[name=end_time]').val(template.end); form.find('[name=color]').val(template.color); form.find('[name=display_date]').val('');
    feedback('Modèle chargé : adaptez-le puis ajoutez-le.');
  });
  $(document).on('click', '[data-task-template]', function () {
    var templates = {
      'nurse-blonde': { name: 'Passage de l’infirmière', title: 'L’infirmière va arriver', details: 'L’infirmière va bientôt sonner à la porte. Tu n’as rien à préparer, tout va bien.', start: '09:30', end: '10:00', color: 'info', routine: 'Soin', image: 'assets/img/cadu-bl.png' },
      'nurse-brunette': { name: 'Passage de l’infirmière', title: 'L’infirmière va arriver', details: 'L’infirmière va bientôt sonner à la porte. Tu n’as rien à préparer, tout va bien.', start: '09:30', end: '10:00', color: 'info', routine: 'Soin', image: 'assets/img/cadu-br.png' }
    };
    var template = templates[$(this).data('task-template')];
    var form = $('#task-form');
    form.find('[name=name]').val(template.name); form.find('[name=title]').val(template.title); form.find('[name=details]').val(template.details); form.find('[name=start_time]').val(template.start); form.find('[name=end_time]').val(template.end); form.find('[name=color]').val(template.color); form.find('[name=refresh_seconds]').val('60'); form.find('[name=routine]').val(template.routine); form.find('[name=image_path]').val(template.image);
    feedback('Modèle chargé : adaptez-le puis ajoutez-le.');
  });
  $(document).on('click', '[data-video-action]', function () {
    var action = $(this).data('video-action') === 'start' ? 'start-video-call' : 'end-video-call';
    $.post('api.php?action=' + action).done(function (payload) { if (action === 'start-video-call') window.open('visio.php?call=' + encodeURIComponent(payload.call.id) + '&role=caller&token=' + encodeURIComponent(payload.call.caller_token), '_blank', 'noopener'); dashboard(); feedback(action === 'start-video-call' ? 'Appel visio envoyé à la tablette.' : 'Appel visio terminé.'); }).fail(function (xhr) { feedback(xhr.responseJSON && xhr.responseJSON.error || 'Action visio impossible.', true); });
  });

  clock(); window.setInterval(clock, 1000); tablet(); dashboard();
}(jQuery));
