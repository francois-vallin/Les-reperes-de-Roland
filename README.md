# Les repères de Roland

> Des repères simples, au bon moment, pour aider une personne à rester chez elle — et permettre aux proches d’être présents autrement.

**Les repères de Roland** est une aide à l’autonomie à domicile : une tablette affiche en continu l’heure, la consigne utile et les messages du jour. À distance, un proche prépare simplement les repères, les informations et les routines.

Ce projet est né en 2018 pour Roland, le grand-père de François. Très âgé, encore autonome physiquement, il vivait avec des troubles de la mémoire et une démence. Il souhaitait rester chez lui ; sa famille voulait l’y aider sans le réduire à une succession de risques ou de contrôles. Une première page, écrite en quelques minutes un soir difficile, est devenue un petit compagnon de journée.

> À la mémoire de **Roland Vallin**, décédé le 18 février 2023.

Cette ultime version est réalisée avec Codex, parce que porter seul cette reprise était trop difficile émotionnellement.

## Ce que la v5 apporte

| Pour la personne accompagnée | Pour les proches et intervenants |
| --- | --- |
| Heure et date toujours visibles | Administration simple, adaptée au téléphone comme à l’ordinateur |
| Messages courts, concrets et rassurants | Tâches, horaires, couleurs et routines configurables |
| Écran passif : aucune action tactile nécessaire | Informations ponctuelles, y compris pour les intervenants de passage |
| Appel visio intégré à décrochage automatique, une fois configuré | Journal des actions et modèles de messages modifiables |
| Couleurs de fond servant de repères visuels | Panneau d’intégrations domotiques, volontairement inactif dans la démo publique |

La tablette était posée sur la table principale et restait allumée 24 h/24. Elle continuait à fonctionner sur batterie en cas de coupure. Roland n’avait jamais à la toucher : elle était un terminal de repères, pas une interface à apprendre.

## Une technologie au service de la liberté

Le projet ne remplace ni les proches, ni les professionnels. Il sert à rendre le quotidien plus lisible : « Le kiné viendra cet après-midi », « Tes chaussettes sont sur ton lit », « Tu peux avoir confiance en toi, nous avons confiance en toi ».

Pendant les confinements, ces repères ont pris une place encore plus importante. Avec les passages des infirmières, du kinésithérapeute et des aides à domicile — qui ne pouvaient pas toujours venir comme prévu — Roland a pu rester chez lui, heureux, pendant plusieurs années. François et son épouse sont passés de dix à vingt vérifications quotidiennes à deux vraies visites, davantage consacrées à être avec lui.

Les automatisations de la maison, utilisées dans l’installation réelle, permettaient de veiller discrètement : éclairage de repère, extinction de la télévision, limitation d’un appareil de cuisine, et caméras limitées aux pièces de vie, jamais aux toilettes ou à la salle de bains. Elles pouvaient aussi aider à guider, sans intrusion : « Ce que tu cherches est sur ton lit. »

## Démarrer localement

Pré-requis : PHP 8.1+ avec les extensions `pdo_sqlite` et `sqlite3`. Aucune base de données externe n’est nécessaire.

```powershell
composer install
php -S localhost:8080 -t public
```

Puis ouvrez :

- écran tablette : `http://localhost:8080/` ;
- administration : `http://localhost:8080/?view=admin`.

La base SQLite est créée automatiquement dans `data/` avec des exemples anonymisés. Elle est ignorée par Git. L’application ne demande aucun mot de passe : elle est conçue pour une installation privée, sur un appareil ou réseau familial de confiance, et ne doit pas être exposée directement sur Internet. Accordez une fois les autorisations caméra/microphone à la tablette si la visio est utilisée.

## Visio et intégrations

La visio utilise WebRTC directement dans le navigateur. L’administration demande l’appel ; la tablette l’ouvre automatiquement. La signalisation passe par SQLite, sans fournisseur de visio tiers. Les jetons techniques d’une session sont générés automatiquement, sans mot de passe ou secret à configurer. Une connexion directe est surtout adaptée au même réseau local ; un déploiement entre réseaux distincts doit prévoir ses propres services STUN/TURN.

Le panneau « Domotique à connecter » est une carte de ce qui a existé ou peut être relié selon le domicile : lumière, télévision/VLC, chaîne Freebox, signal visuel, cuisine, volets, température, contacts, appel d’urgence et cadre photo/vidéo. Tous ces boutons sont volontairement grisés dans la version publique : aucun équipement, aucune caméra et aucun contact n’est piloté par défaut.

## Documentation

- [Documentation complète](DOCUMENTATION.md) — histoire, usages, architecture et limites ;
- [Guide de contribution](CONTRIBUTING.md) — comment proposer une amélioration avec délicatesse ;
- [Respect de la vie privée](PRIVACY.md) — principes pour une installation réelle ;
- [Charte de contribution](CODE_OF_CONDUCT.md) — le cadre humain du projet ;
- [Support](SUPPORT.md) — où demander de l’aide ou proposer une idée ;
- [Wiki de démarrage](docs/wiki/Home.md) — guides courts à publier dans le wiki GitHub ;
- [Journal des versions](CHANGELOG.md) — repères dans l’évolution du projet.

Ce dépôt est une édition neuve et autonome : il ne contient aucun historique technique antérieur. Seul `public/` doit être exposé par le serveur web.

## Contact

Pour partager une expérience, poser une question ou contribuer : [fjvallin2024@gmail.com](mailto:fjvallin2024@gmail.com).

> Les exemples publics sont volontairement anonymisés. N’ajoutez jamais de données de santé, de coordonnées ou d’images identifiantes d’une personne accompagnée dans un dépôt public.
