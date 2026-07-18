<?php
/**
 * config_caldav.php — Identifiants CalDAV iCloud
 * ═══════════════════════════════════════════════
 * ⚠️  NE PAS PARTAGER ce fichier (credentials)
 * ⚠️  L'ajouter à .gitignore si vous utilisez Git
 *
 * GÉNÉRER UN MOT DE PASSE D'APPLICATION APPLE :
 * ──────────────────────────────────────────────
 * 1. Aller sur https://appleid.apple.com
 * 2. Section "Connexion et sécurité"
 *    → "Mots de passe spécifiques aux apps"
 * 3. Cliquer le "+" et nommer : "Dashboard"
 * 4. Copier le mot de passe (format: xxxx-xxxx-xxxx-xxxx)
 *    ⚠️  Il ne s'affiche qu'une seule fois
 *
 * Ce mot de passe est DIFFÉRENT de votre vrai mot de passe Apple.
 * Il peut être révoqué à tout moment sans affecter votre compte.
 */

// Votre identifiant Apple (adresse e-mail)
define('CALDAV_USER', 'pierrelouisfruleux@yahoo.fr');

// Mot de passe d'application (format: xxxx-xxxx-xxxx-xxxx)
define('CALDAV_PASS', 'eiko-ghed-uiba-tbrj');