# Contribution Guidelines

Merci de votre intérêt pour contribuer à RLIFE !

## Comment contribuer

### 1. Signaler un bug
- Utilisez le [Bug Report Template](.github/ISSUE_TEMPLATE/bug_report.md)
- Décrivez le problème clairement
- Incluez les étapes pour reproduire

### 2. Proposer une fonctionnalité
- Ouvrez une issue avec le label `enhancement`
- Décrivez la fonctionnalité proposée
- Expliquez pourquoi elle serait utile

### 3. Soumettre une Pull Request
1. Fork le projet
2. Créez une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## Standards de code

- Suivez les standards PSR-12 pour PHP
- Utilisez ESLint pour JavaScript
- Комментарии en français ou anglais
- Tout le code doit passer PHPStan niveau 5

## Tests

- Ajoutez des tests pour les nouvelles fonctionnalités
- Assurez-vous que tous les tests passent :
```bash
php bin/phpunit
vendor/bin/phpstan analyse
```

## Convention de nommage

- **Branches**: `feature/`, `bugfix/`, `hotfix/`
- **Commits**: Conventional Commits (français ou anglais)
- **Issues**: Décrivez clairement le problème

---

Merci de votre contribution ! 🎉
