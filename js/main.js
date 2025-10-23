// ============================================
// GESTION DES SIGNATURES AVEC XMLHttpRequest
// ============================================

/**
 * Toggle l'affichage des signatures d'une pétition
 * Utilise XMLHttpRequest pour charger les données de manière asynchrone
 */
function toggleSignatures(petitionId) {
    const signaturesZone = document.getElementById('signatures-' + petitionId);
    const signaturesList = signaturesZone.querySelector('.signatures-list');
    const loading = signaturesZone.querySelector('.loading');

    // Si déjà visible, on cache
    if (signaturesZone.style.display !== 'none') {
        signaturesZone.style.display = 'none';
        return;
    }

    // Afficher la zone
    signaturesZone.style.display = 'block';
    loading.style.display = 'inline';
    signaturesList.innerHTML = '<div class="spinner"></div>';

    // Créer l'objet XMLHttpRequest
    const xhr = new XMLHttpRequest();

    // Configurer la requête GET vers l'API
    xhr.open('GET', 'get_recent_signatures.php?petition_id=' + encodeURIComponent(petitionId), true);

    // Définir le gestionnaire d'événement onreadystatechange
    xhr.onreadystatechange = function() {
        // Vérifier les états de readyState
        if (xhr.readyState === 0) {
            console.log('XMLHttpRequest: État UNSENT (0)');
        } else if (xhr.readyState === 1) {
            console.log('XMLHttpRequest: État OPENED (1) - Connexion ouverte');
        } else if (xhr.readyState === 2) {
            console.log('XMLHttpRequest: État HEADERS_RECEIVED (2) - En-têtes reçus');
        } else if (xhr.readyState === 3) {
            console.log('XMLHttpRequest: État LOADING (3) - Chargement en cours...');
        } else if (xhr.readyState === 4) {
            // État DONE (4) - La requête est terminée
            console.log('XMLHttpRequest: État DONE (4) - Requête terminée');
            
            loading.style.display = 'none';

            // Vérifier le code de statut HTTP
            if (xhr.status === 200) {
                // Succès (200)
                console.log('Statut HTTP: 200 OK');
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.success && response.signatures.length > 0) {
                        displaySignatures(response.signatures, signaturesList);
                    } else {
                        signaturesList.innerHTML = '<p class="no-signatures">📝 Aucune signature pour le moment</p>';
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON:', e);
                    signaturesList.innerHTML = '<p class="no-signatures error">❌ Erreur lors du chargement des données</p>';
                }
            } else if (xhr.status === 404) {
                // Fichier non trouvé (404)
                console.error('Statut HTTP: 404 - Fichier non trouvé');
                signaturesList.innerHTML = '<p class="no-signatures error">❌ Fichier API non trouvé (404)</p>';
            } else if (xhr.status === 500) {
                // Erreur serveur (500)
                console.error('Statut HTTP: 500 - Erreur serveur');
                signaturesList.innerHTML = '<p class="no-signatures error">❌ Erreur serveur (500)</p>';
            } else if (xhr.status === 0) {
                // Impossible de contacter le serveur (0)
                console.error('Statut HTTP: 0 - Impossible de contacter le serveur');
                signaturesList.innerHTML = '<p class="no-signatures error">❌ Impossible de contacter le serveur</p>';
            } else {
                // Autre erreur
                console.error('Statut HTTP:', xhr.status);
                signaturesList.innerHTML = '<p class="no-signatures error">❌ Erreur HTTP ' + xhr.status + '</p>';
            }
        }
    };

    // Gestionnaire d'erreur réseau
    xhr.onerror = function() {
        console.error('Erreur réseau XMLHttpRequest');
        loading.style.display = 'none';
        signaturesList.innerHTML = '<p class="no-signatures error">❌ Erreur de connexion réseau</p>';
    };

    // Gestionnaire de timeout (optionnel)
    xhr.ontimeout = function() {
        console.error('Timeout XMLHttpRequest');
        loading.style.display = 'none';
        signaturesList.innerHTML = '<p class="no-signatures error">⏱️ Délai d\'attente dépassé</p>';
    };

    // Définir un timeout de 10 secondes
    xhr.timeout = 10000;

    // Envoyer la requête
    xhr.send();
}

/**
 * Afficher les signatures dans le DOM
 */
function displaySignatures(signatures, container) {
    let html = '';

    signatures.forEach(function(sig) {
        var name = '';
        var country = '';
        var dateStr = '';

        // Support old response shape (DateS, HeureS, PrenomS, NomS, PaysS)
        if (sig.PrenomS || sig.NomS) {
            name = (sig.PrenomS || '') + ' ' + (sig.NomS || '');
            country = sig.PaysS || '';
            try {
                var d = new Date(sig.DateS);
                var formattedDate = d.toLocaleDateString('fr-FR');
                var formattedTime = (sig.HeureS || '').substring(0,5);
                dateStr = formattedDate + ' à ' + formattedTime;
            } catch (e) {
                dateStr = sig.DateS || '';
            }
        }

        // Support new response shape (prenom, nom, pays, date)
        else if (sig.prenom || sig.nom) {
            name = (sig.prenom || '') + ' ' + (sig.nom || '');
            country = sig.pays || '';
            try {
                var d2 = new Date(sig.date);
                dateStr = d2.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                dateStr = sig.date || '';
            }
        }

        html += '<div class="signature-item">';
        html += '  <div class="signature-info">';
        html += '    <span class="signature-name">' + escapeHtml(name.trim()) + '</span>';
        html += '    <span class="signature-country"><i class="bi bi-globe"></i> ' + escapeHtml(country) + '</span>';
        html += '  </div>';
        html += '  <span class="signature-date"><i class="bi bi-calendar3"></i> ' + escapeHtml(dateStr) + '</span>';
        html += '</div>';
    });

    container.innerHTML = html;
}

/**
 * Échapper les caractères HTML pour sécurité XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// NOTIFICATION NOUVELLES PÉTITIONS (LONG POLLING)
// ============================================

let lastNotificationId = 0;
let notificationCheckInterval = null;
let isCheckingNotifications = false;

/**
 * Vérifier les nouvelles notifications via la table BDD
 * Utilise XMLHttpRequest avec readyState et status HTTP
 */
function checkNotifications() {
    // Éviter les appels simultanés
    if (isCheckingNotifications) {
        console.log('⏸️ Vérification déjà en cours, attente...');
        return;
    }
    
    isCheckingNotifications = true;
    
    const xhr = new XMLHttpRequest();
    
    // Construire l'URL avec le dernier ID de notification
    const url = 'get_notifications.php?last_id=' + lastNotificationId;
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        // État 1: OPENED - Connexion établie
        if (xhr.readyState === 1) {
            console.log('📡 XMLHttpRequest État OPENED (1) - Connexion établie');
        }
        
        // État 2: HEADERS_RECEIVED - En-têtes reçus
        else if (xhr.readyState === 2) {
            console.log('📨 XMLHttpRequest État HEADERS_RECEIVED (2) - En-têtes HTTP reçus');
        }
        
        // État 3: LOADING - Réception des données
        else if (xhr.readyState === 3) {
            console.log('⏳ XMLHttpRequest État LOADING (3) - Chargement des données...');
        }
        
        // État 4: DONE - Requête terminée
        else if (xhr.readyState === 4) {
            console.log('✅ XMLHttpRequest État DONE (4) - Requête terminée');
            
            isCheckingNotifications = false;
            
            // Vérifier le code de statut HTTP
            if (xhr.status === 200) {
                // ✅ SUCCÈS (200)
                console.log('✅ Statut HTTP: 200 OK - Données reçues');
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success && response.count > 0) {
                        console.log('🔔 ' + response.count + ' nouvelle(s) notification(s)');
                        
                        // Mettre à jour le dernier ID
                        lastNotificationId = response.last_id;
                        
                        // Afficher chaque notification
                        response.notifications.forEach(function(notif) {
                            displayNotification(notif);
                        });
                        
                        // Marquer les notifications comme lues après affichage
                        setTimeout(function() {
                            markNotificationsAsRead(response.notifications);
                        }, 3000);
                        
                    } else {
                        console.log('📭 Aucune nouvelle notification');
                    }
                } catch (e) {
                    console.error('❌ Erreur parsing JSON:', e);
                    console.error('Réponse brute:', xhr.responseText);
                }
                
            } else if (xhr.status === 404) {
                // ❌ FICHIER NON TROUVÉ (404)
                console.error('❌ Statut HTTP: 404 - Fichier get_notifications.php non trouvé');
                console.error('Vérifiez que le fichier existe à la racine du projet');
                
            } else if (xhr.status === 500) {
                // ❌ ERREUR SERVEUR (500)
                console.error('❌ Statut HTTP: 500 - Erreur serveur interne');
                console.error('Vérifiez les logs PHP et la syntaxe de get_notifications.php');
                
            } else if (xhr.status === 0) {
                // ❌ SERVEUR INACCESSIBLE (0)
                console.error('❌ Statut HTTP: 0 - Impossible de contacter le serveur');
                console.error('Vérifiez que Apache/XAMPP/WAMP est démarré');
                
            } else {
                // ❌ AUTRE ERREUR
                console.error('❌ Statut HTTP inattendu:', xhr.status);
            }
        }
    };
    
    // Gestionnaire d'erreur réseau
    xhr.onerror = function() {
        console.error('❌ ERREUR RÉSEAU XMLHttpRequest');
        console.error('Impossible d\'établir une connexion réseau');
        isCheckingNotifications = false;
    };
    
    // Gestionnaire de timeout
    xhr.ontimeout = function() {
        console.error('⏱️ TIMEOUT XMLHttpRequest');
        console.error('Le serveur met trop de temps à répondre (> 10 secondes)');
        isCheckingNotifications = false;
    };
    
    // Définir un timeout de 10 secondes
    xhr.timeout = 10000;
    
    // Envoyer la requête
    xhr.send();
}

/**
 * Afficher une notification dans le DOM
 */
function displayNotification(notification) {
    const notificationContainer = document.getElementById('notification');
    if (!notificationContainer) {
        console.error('❌ Element #notification non trouvé dans le DOM');
        return;
    }
    
    // Créer le HTML de la notification
    let message = '';
    
    if (notification.TypeN === 'nouvelle_petition') {
        message = '<i class="bi bi-bell-fill"></i> ' + escapeHtml(notification.MessageN);
    } else if (notification.TypeN === 'nouvelle_signature') {
        message = '<i class="bi bi-pen-fill"></i> ' + escapeHtml(notification.MessageN);
    } else {
        message = '<i class="bi bi-info-circle-fill"></i> ' + escapeHtml(notification.MessageN);
    }
    
    notificationContainer.innerHTML = message;
    notificationContainer.className = 'notification notification-new';
    notificationContainer.style.display = 'block';
    
    // Ajouter un bouton pour recharger
    const existingBtn = notificationContainer.querySelector('.btn-reload');
    if (!existingBtn) {
        const btn = document.createElement('button');
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Recharger';
        btn.className = 'btn-reload';
        btn.onclick = function() {
            location.reload();
        };
        notificationContainer.appendChild(btn);
    }
    
    // Animation d'apparition
    notificationContainer.style.animation = 'slideDown 0.3s ease';
    
    // Recharger automatiquement après 5 secondes
    setTimeout(function() {
        console.log('🔄 Rechargement automatique de la page...');
        location.reload();
    }, 5000);
}

/**
 * Marquer les notifications comme lues
 * Utilise XMLHttpRequest avec méthode POST
 */
function markNotificationsAsRead(notifications) {
    if (!notifications || notifications.length === 0) return;
    
    // Extraire les IDs
    const ids = notifications.map(function(n) { return n.IDN; }).join(',');
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'mark_notifications_read.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log('✅ Notifications marquées comme lues:', ids);
            } else {
                console.error('❌ Erreur lors du marquage comme lu. Statut HTTP:', xhr.status);
            }
        }
    };
    
    xhr.onerror = function() {
        console.error('❌ Erreur réseau lors du marquage des notifications');
    };
    
    // Envoyer les IDs
    xhr.send('ids=' + encodeURIComponent(ids));
}

/**
 * Démarrer la vérification périodique des notifications
 * Intervalle de 10 secondes (moins fréquent que le polling)
 */
function startNotificationCheck() {
    console.log('🚀 Démarrage du système de notifications');
    
    // Vérification immédiate
    // NOTE: replaced by a simpler file-based polling verifierNouvellesPetitions()
}

/**
 * Arrêter la vérification des notifications
 */
function stopNotificationCheck() {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
        notificationCheckInterval = null;
        console.log('⏹️ Arrêt du système de notifications');
    }
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

/**
 * Afficher un message (succès ou erreur)
 */
function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;

    messageDiv.innerHTML = message;
    messageDiv.className = 'message ' + type;
    messageDiv.style.display = 'block';

    // Faire disparaître après 5 secondes
    setTimeout(function() {
        messageDiv.style.opacity = '0';
        setTimeout(function() {
            messageDiv.style.display = 'none';
            messageDiv.style.opacity = '1';
        }, 500);
    }, 5000);
}

/**
 * Masquer automatiquement les alertes après 5 secondes
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
}

/**
 * Afficher la pétition avec le plus de signatures
 * Utilise XMLHttpRequest avec gestion complète des états
 */
function checkTopPetition() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_petition_top.php', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status !== 200) {
            console.error('Erreur checkTopPetition - Statut:', xhr.status);
            return;
        }

        try {
            var response = JSON.parse(xhr.responseText);
            if (!response || !response.success || !response.petition) return;

            // Update the notification area with a concise top-petition message
            var notification = document.getElementById('notification');
            if (notification) {
                notification.innerHTML = '<i class="bi bi-star-fill"></i> Pétition la plus signée : <strong>' +
                    escapeHtml(response.petition.TitreP) + '</strong> (' + (response.petition.nb_signatures || '0') + ' signatures)';
                notification.style.display = 'block';
            }

            // Update petition cards: remove existing marker, add to the new one
            var prev = document.querySelector('.petition-card.top-petition');
            if (prev) {
                prev.classList.remove('top-petition');
                var oldBadge = prev.querySelector('.badge-top');
                if (oldBadge) oldBadge.remove();
            }

            var card = document.querySelector('.petition-card[data-id="' + response.petition.IDP + '"]');
            if (card) {
                card.classList.add('top-petition');
                // Insert a visible badge if not present
                var header = card.querySelector('.petition-header');
                if (header && !header.querySelector('.badge-top')) {
                    var span = document.createElement('span');
                    span.className = 'badge-top';
                    span.textContent = 'Plus populaire';
                    // place it after the title (append to header)
                    header.appendChild(span);
                }
            } else {
                // Top petition is not present in the DOM (maybe list is stale).
                // Offer a simple reload button in the notification area to let the user refresh.
                if (notification) {
                    var existingBtn = notification.querySelector('.btn-top-reload');
                    if (!existingBtn) {
                        var btn = document.createElement('button');
                        btn.className = 'btn-top-reload';
                        btn.textContent = 'Voir la pétition';
                        btn.style.marginLeft = '10px';
                        btn.onclick = function() { window.location.reload(); };
                        notification.appendChild(btn);
                    }
                }
            }

        } catch (e) {
            console.error('Erreur parsing JSON (top petition):', e, xhr.responseText);
        }
    };

    xhr.onerror = function() { console.error('Erreur réseau checkTopPetition'); };
    xhr.send();
}

// ============================================
// INITIALISATION AU CHARGEMENT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('═══════════════════════════════════════════════');
    console.log('✅ Application chargée avec XMLHttpRequest');
    console.log('📡 Utilisation de XMLHttpRequest (pas de Fetch API)');
    console.log('🔔 Système de notifications avec table BDD');
    console.log('═══════════════════════════════════════════════');

    // Vérifier la pétition la plus populaire
    checkTopPetition();

    // Vérification périodique de la pétition la plus populaire (toutes les 10s)
    try {
        setInterval(checkTopPetition, 10000);
    } catch (e) { console.error('Impossible de démarrer interval checkTopPetition', e); }

    // Valider le formulaire
    validateSignatureForm();

    // Auto-masquer les alertes
    autoHideAlerts();

    // ✅ DÉMARRER LE SYSTÈME DE NOTIFICATIONS (polling simple)
    // New: verifierNouvellesPetitions() polls get_notifications.php which compares
    // the DB petition count with last_petition_count.txt and returns JSON when
    // a new petition is detected.
    if (typeof verifierNouvellesPetitions === 'function') {
        // Run immediately then every 8 seconds
        try { verifierNouvellesPetitions(); } catch (e) { console.error(e); }
        setInterval(function() {
            try { verifierNouvellesPetitions(); } catch (e) { console.error(e); }
        }, 8000);
    }

    // Gérer les erreurs dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('error') === 'duplicate') {
        showMessage('<i class="bi bi-exclamation-triangle-fill"></i> Vous avez déjà signé cette pétition avec cet email', 'error');
    } else if (urlParams.get('error') === 'db') {
        showMessage('<i class="bi bi-x-circle-fill"></i> Erreur lors de l\'enregistrement dans la base de données', 'error');
    } else if (urlParams.get('error') === 'validation') {
        showMessage('<i class="bi bi-exclamation-triangle-fill"></i> Erreur de validation des données', 'error');
    } else if (urlParams.get('error') === 'petition_not_found') {
        showMessage('<i class="bi bi-x-circle-fill"></i> Pétition non trouvée', 'error');
    }
    
    // Afficher un message si une pétition vient d'être créée
    if (urlParams.get('success') === 'petition_created') {
        const petitionId = urlParams.get('id');
        showMessage('<i class="bi bi-check-circle-fill"></i> Votre pétition a été créée avec succès ! (ID: ' + petitionId + ')', 'success');
    }
});

// Arrêter le système de notifications quand on quitte la page
window.addEventListener('beforeunload', function() {
    stopNotificationCheck();
});
// ============================================
// NOTIFICATION DE CRÉATION DE PÉTITION

// Écouter les événements storage pour recevoir des notifications cross-tab en temps réel
window.addEventListener('storage', function(e) {
    try {
        if (!e || !e.key) return;
        if (e.key === 'newPetition' && e.newValue) {
            var data = JSON.parse(e.newValue);
            if (data && data.id) {
                // Mettre à jour lastSeenPetitionId
                try { localStorage.setItem('lastSeenPetitionId', String(data.id)); } catch (err) {}

                // Afficher notification courte
                var zone = document.getElementById('notifications') || document.getElementById('notification');
                if (zone) {
                    var msg = '🆕 Nouvelle pétition : ' + (data.title || '—');
                    zone.innerHTML = '<div class="notification notification-new">' + escapeHtml(msg) + '</div>';
                    setTimeout(function() { zone.innerHTML = ''; }, 6000);
                }
                        // Rafraîchir immédiatement la pétition la plus populaire
                        try { checkTopPetition(); } catch (err) { console.error('Erreur lors du refresh top petition depuis storage event', err); }
            }
        }
        // Quand une signature est ajoutée dans un autre onglet, mettre à jour le compteur
        else if (e.key === 'signatureAdded' && e.newValue) {
            try {
                var s = JSON.parse(e.newValue);
                if (s && s.petition_id) {
                    var card = document.querySelector('.petition-card[data-id="' + s.petition_id + '"]');
                    if (card) {
                        var badge = card.querySelector('.badge');
                        if (badge) {
                            var total = parseInt(s.total_signatures, 10) || 0;
                            badge.textContent = total + ' ' + (total > 1 ? 'supporters' : 'supporter');
                        }
                    }

                    // If we're on the petition detail page, update the large badge too
                    var largeBadge = document.querySelector('.badge-large');
                    if (largeBadge && window.location.href.indexOf('signature.php') === -1) {
                        // do nothing when not on signature page
                    } else if (largeBadge && s.total_signatures) {
                        var tot = parseInt(s.total_signatures, 10) || 0;
                        largeBadge.textContent = tot + ' ' + (tot > 1 ? 'signatures' : 'signature');
                    }

                    // Optionally refresh top petition marker
                    try { checkTopPetition(); } catch (err) { console.error('Erreur checkTopPetition après signatureAdded', err); }
                }
            } catch (err) { console.error('Erreur parsing signatureAdded storage event', err); }
        }
    } catch (err) {
        console.error('Erreur traitement storage event:', err);
    }
});

// ============================================
// SOUMISSION AJAX DU FORMULAIRE DE SIGNATURE
// ============================================

function validateSignatureForm() {
    const form = document.getElementById('signatureForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Empêcher la soumission normale
        
        const nom = document.getElementById('nom').value.trim();
        const prenom = document.getElementById('prenom').value.trim();
        const email = document.getElementById('email').value.trim();
        const pays = document.getElementById('pays').value.trim();
        const petitionId = form.querySelector('[name="petition_id"]').value;

        let errors = [];

        // Validation nom
        if (nom.length < 2) {
            errors.push('Le nom doit contenir au moins 2 caractères');
        }
        if (nom.length > 100) {
            errors.push('Le nom ne peut pas dépasser 100 caractères');
        }

        // Validation prénom
        if (prenom.length < 2) {
            errors.push('Le prénom doit contenir au moins 2 caractères');
        }
        if (prenom.length > 100) {
            errors.push('Le prénom ne peut pas dépasser 100 caractères');
        }

        // Validation email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errors.push('Email invalide');
        }
        if (email.length > 100) {
            errors.push('L\'email ne peut pas dépasser 100 caractères');
        }

        // Validation pays
        if (pays.length < 2) {
            errors.push('Le pays doit contenir au moins 2 caractères');
        }
        if (pays.length > 100) {
            errors.push('Le pays ne peut pas dépasser 100 caractères');
        }

        // Si erreurs, afficher et arrêter
        if (errors.length > 0) {
            showMessage(errors.join('<br>'), 'error');
            return false;
        }

        // Tout est valide, envoyer en AJAX
        submitSignatureAjax(form);
    });
}

/**
 * Soumettre le formulaire de signature en AJAX
 */
function submitSignatureAjax(form) {
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('loadingSpinner');
    const messageDiv = document.getElementById('message');
    
    // Désactiver le bouton et afficher le spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Envoi en cours...';
    if(spinner) spinner.style.display = 'block';
    
    // Préparer les données du formulaire
    const formData = new FormData(form);
    
    // Créer l'objet XMLHttpRequest
    const xhr = new XMLHttpRequest();
    
    // Construire les paramètres POST
    const params = new URLSearchParams(formData).toString();
    
    xhr.open('POST', 'ajouter_signature.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // Identifier comme AJAX
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Envoyer ma signature';
            if(spinner) spinner.style.display = 'none';
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // ✅ SUCCÈS
                        showMessage('<i class="bi bi-check-circle-fill"></i> ' + response.message + '! Vous allez être redirigé...', 'success');
                        
                        // Réinitialiser le formulaire
                        form.reset();
                        
                        // If a page provides a handler for signature addition (e.g. signature.php)
                        // call it so that page can refresh the recent signatures in-place.
                        if (typeof window.onSignatureAdded === 'function') {
                            try { window.onSignatureAdded(response); } catch (e) { console.error(e); }
                            // do not redirect away from the page; let the page update itself
                        } else {
                            // Rediriger après 2 secondes (fallback behavior)
                            setTimeout(function() {
                                window.location.href = 'index.php?success=1';
                            }, 2000);
                        }

                        // Broadcast the signature addition to other tabs via localStorage
                        try {
                            var petitionIdVal = null;
                            try {
                                petitionIdVal = form.querySelector('[name="petition_id"]').value;
                            } catch (e) { petitionIdVal = null; }

                            var payload = {
                                petition_id: petitionIdVal || (response.petition_id || response.IDP || null),
                                total_signatures: response.total_signatures || null,
                                signature_id: response.signature_id || null,
                                ts: Date.now()
                            };
                            try { localStorage.setItem('signatureAdded', JSON.stringify(payload)); } catch (e) { /* ignore */ }
                        } catch (e) { console.error('Erreur lors du broadcast signatureAdded', e); }
                        
                    } else {
                        // ❌ ERREUR
                        if (response.error === 'duplicate') {
                            showMessage('<i class="bi bi-exclamation-triangle-fill"></i> Vous avez déjà signé cette pétition avec cet email', 'error');
                        } else if (response.errors) {
                            showMessage('<i class="bi bi-x-circle-fill"></i> ' + response.errors.join('<br>'), 'error');
                        } else {
                            showMessage('<i class="bi bi-x-circle-fill"></i> ' + (response.message || 'Erreur lors de l\'ajout de la signature'), 'error');
                        }
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON:', e);
                    showMessage('<i class="bi bi-x-circle-fill"></i> Erreur lors du traitement de la réponse', 'error');
                }
            } else {
                console.error('Erreur HTTP:', xhr.status);
                showMessage('<i class="bi bi-x-circle-fill"></i> Erreur serveur (' + xhr.status + ')', 'error');
            }
        }
    };
    
    xhr.onerror = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Envoyer ma signature';
        if(spinner) spinner.style.display = 'none';
        showMessage('<i class="bi bi-x-circle-fill"></i> Erreur de connexion réseau', 'error');
    };
    
    xhr.send(params);
}

/**
 * Poller simple pour détecter les nouvelles pétitions.
 * Appelle `get_notifications.php` et affiche le message texte retourné dans #notifications
 */
function verifierNouvellesPetitions() {
    var xhr = null;
    if (window.XMLHttpRequest) xhr = new XMLHttpRequest();
    else if (window.ActiveXObject) xhr = new ActiveXObject('Microsoft.XMLHTTP');
    else return;

    // Use localStorage to persist the last seen petition ID across tabs
    var lastSeen = 0;
    try {
        lastSeen = parseInt(localStorage.getItem('lastSeenPetitionId') || '0', 10);
        if (isNaN(lastSeen)) lastSeen = 0;
    } catch (e) {
        lastSeen = 0;
    }

    var url = 'get_notifications.php';
    // If we already have a lastSeen ID, ask server whether there's a newer petition
    if (lastSeen && lastSeen > 0) {
        url += '?last_seen=' + encodeURIComponent(lastSeen);
    }

    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (!resp || !resp.success) return;

                    // If client didn't provide lastSeen (initial sync), server returns latest_id and new=false
                    if (!lastSeen || lastSeen === 0) {
                        if (resp.latest_id && resp.latest_id > 0) {
                            try { localStorage.setItem('lastSeenPetitionId', resp.latest_id); } catch (e) {}
                        }
                        return; // initial sync, do not display as "new"
                    }

                    // If server indicates a new petition, display it and update lastSeen
                    if (resp.new && resp.petition) {
                        var p = resp.petition;
                        var zone = document.getElementById('notifications') || document.getElementById('notification');
                        if (zone) {
                            var msg = '🆕 Nouvelle pétition : ' + (p.TitreP || '—');
                            zone.innerHTML = '<div class="notification notification-new">' + escapeHtml(msg) + '</div>';
                            // fade out after 6s
                            setTimeout(function() { zone.innerHTML = ''; }, 6000);
                        }

                        // Update last seen id in localStorage
                        try { localStorage.setItem('lastSeenPetitionId', resp.latest_id); } catch (e) {}
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON get_notifications:', e, xhr.responseText);
                }
            }
        }
    };
    xhr.send(null);
}