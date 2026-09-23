(function () {
  'use strict';
  var root = document.getElementById('visio');
  var callId = Number(root.dataset.call), role = root.dataset.role, token = root.dataset.token, after = 0, peer, stream, seenOffer = false;
  var status = document.getElementById('visio-status'), local = document.getElementById('local-video'), remote = document.getElementById('remote-video');
  function recipient() { return role === 'caller' ? 'tablet' : 'caller'; }
  function request(url, data) { return fetch(url, data).then(function (r) { return r.json(); }); }
  function signal(payload) { return request('api.php?action=video-signal', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: new URLSearchParams({call_id: callId, role: role, token: token, recipient: recipient(), payload: JSON.stringify(payload)}) }); }
  async function handle(message) {
    if (message.type === 'offer' && role === 'tablet' && !seenOffer) { seenOffer = true; await peer.setRemoteDescription(message.sdp); var answer = await peer.createAnswer(); await peer.setLocalDescription(answer); await signal({type: 'answer', sdp: peer.localDescription}); status.textContent = 'Appel vidéo en cours.'; }
    if (message.type === 'answer' && role === 'caller') { await peer.setRemoteDescription(message.sdp); status.textContent = 'Appel vidéo en cours.'; }
    if (message.type === 'candidate' && message.candidate) await peer.addIceCandidate(message.candidate);
  }
  function poll() { request('api.php?action=video-signals&call_id=' + callId + '&role=' + encodeURIComponent(role) + '&token=' + encodeURIComponent(token) + '&after=' + after).then(function (data) { (data.signals || []).forEach(function (item) { after = Math.max(after, item.id); handle(item.payload).catch(function () { status.textContent = 'Connexion visio interrompue.'; }); }); setTimeout(poll, 900); }).catch(function () { status.textContent = 'Appel terminé.'; }); }
  async function start() {
    if (!navigator.mediaDevices || !window.RTCPeerConnection) { status.textContent = 'La visio nécessite un navigateur moderne et une connexion HTTPS.'; return; }
    stream = await navigator.mediaDevices.getUserMedia({video: true, audio: true}); local.srcObject = stream;
    peer = new RTCPeerConnection();
    stream.getTracks().forEach(function (track) { peer.addTrack(track, stream); }); peer.ontrack = function (event) { remote.srcObject = event.streams[0]; }; peer.onicecandidate = function (event) { if (event.candidate) signal({type: 'candidate', candidate: event.candidate.toJSON()}); };
    poll();
    if (role === 'caller') { var offer = await peer.createOffer(); await peer.setLocalDescription(offer); await signal({type: 'offer', sdp: peer.localDescription}); status.textContent = 'Appel envoyé à la tablette…'; } else status.textContent = 'Prêt à décrocher automatiquement.';
  }
  start().catch(function () { status.textContent = 'La caméra ou le microphone doivent être autorisés une première fois sur cet appareil.'; });
}());
