# Contribuer aux repères de Roland

Merci de vouloir aider. Ici, une amélioration technique n’a de valeur que si elle rend une journée plus claire, plus calme ou plus libre pour la personne accompagnée.

## Avant de proposer une modification

- privilégiez les mots courts, concrets et bienveillants ;
- gardez l’écran tablette lisible de loin et utilisable sans interaction ;
- préservez le caractère local et configurable des intégrations de maison ;
- n’ajoutez jamais de données personnelles, médicales ou d’images identifiantes réelles ;
- expliquez l’usage humain que votre modification cherche à améliorer.

## Développement local

Suivez les instructions du [README](README.md), puis vérifiez au minimum la syntaxe PHP :

```powershell
composer install
composer lint
Get-ChildItem src -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## Ce qui mérite une discussion avant d’être développé

Les fonctions qui touchent aux caméras, à la domotique, aux urgences, à la visio ou aux informations de santé doivent être discutées avant toute implémentation. Elles dépendent du domicile, du consentement de la personne et de ses proches, ainsi que des appareils réellement disponibles.

Vous pouvez écrire à [fjvallin@gmail.com](mailto:fjvallin@gmail.com) ou ouvrir une issue avec un exemple entièrement fictif.
