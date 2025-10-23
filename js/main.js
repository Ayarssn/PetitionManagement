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
    xhr.open('GET', 'get_signatures.php?id=' + encodeURIComponent(petitionId), true);

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
        const date = new Date(sig.DateS);
        const formattedDate = date.toLocaleDateString('fr-FR');
        const formattedTime = sig.HeureS.substring(0, 5); // HH:MM

        html += '<div class="signature-item">';
        html += '  <div class="signature-info">';
        html += '    <span class="signature-name">' + escapeHtml(sig.PrenomS) + ' ' + escapeHtml(sig.NomS) + '</span>';
        html += '    <span class="signature-country"><i class="bi bi-globe"></i> ' + escapeHtml(sig.PaysS) + '</span>';
        html += '  </div>';
        html += '  <span class="signature-date"><i class="bi bi-calendar3"></i> ' + formattedDate + ' à ' + formattedTime + '</span>';
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
// NOTIFICATION PÉTITION POPULAIRE
// ============================================

/**
 * Afficher la pétition avec le plus de signatures
 * Utilise XMLHttpRequest avec gestion complète des états
 */
function checkTopPetition() {
    const xhr = new XMLHttpRequest();

    xhr.open('GET', 'get_petition_top.php', true);

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.success && response.petition) {
                        const notification = document.getElementById('notification');
                        if (notification) {
                            notification.innerHTML = 
                                '<i class="bi bi-star-fill"></i> Pétition la plus signée : <strong>' + 
                                escapeHtml(response.petition.TitreP) + 
                                '</strong> (' + response.petition.nb_signatures + ' signatures)';
                            notification.style.display = 'block';
                        }

                        // Ajouter la classe "top-petition" à la carte
                        const card = document.querySelector('.petition-card[data-id="' + response.petition.IDP + '"]');
                        if (card) {
                            card.classList.add('top-petition');
                        }
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON (top petition):', e);
                }
            } else {
                console.error('Erreur checkTopPetition - Statut:', xhr.status);
            }
        }
    };

    xhr.onerror = function() {
        console.error('Erreur réseau checkTopPetition');
    };

    xhr.send();
}

// ============================================
// NOTIFICATION NOUVELLES PÉTITIONS
// ============================================

let lastCheck = null;
let checkInterval = null;

/**
 * Vérifier s'il y a de nouvelles pétitions
 * Utilise XMLHttpRequest avec tous les états et codes HTTP
 */
function checkNewPetitions() {
    const grid = document.querySelector('.petitions-grid');
    if (!grid) return;

    // Initialiser lastCheck au premier appel
    if (!lastCheck) {
        lastCheck = grid.getAttribute('data-last-check');
    }

    const xhr = new XMLHttpRequest();
    
    // Construire l'URL avec le paramètre
    const url = 'check_new_petitions.php?last_check=' + encodeURIComponent(lastCheck);
    xhr.open('GET', url, true);

    xhr.onreadystatechange = function() {
        // Afficher tous les états pour le debugging
        if (xhr.readyState === 1) {
            console.log('Check nouvelles pétitions - État OPENED');
        } else if (xhr.readyState === 2) {
            console.log('Check nouvelles pétitions - État HEADERS_RECEIVED');
        } else if (xhr.readyState === 3) {
            console.log('Check nouvelles pétitions - État LOADING');
        } else if (xhr.readyState === 4) {
            console.log('Check nouvelles pétitions - État DONE');

            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.success && response.new_count > 0) {
                        // Afficher la notification
                        showNewPetitionNotification(response.new_count, response.latest_petition);
                        
                        // Mettre à jour le timestamp
                        lastCheck = response.current_time;
                    } else {
                        console.log('Aucune nouvelle pétition');
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON (nouvelles pétitions):', e);
                }
            } else if (xhr.status === 404) {
                console.error('API check_new_petitions.php non trouvée (404)');
            } else if (xhr.status === 500) {
                console.error('Erreur serveur lors de la vérification (500)');
            } else if (xhr.status === 0) {
                console.error('Impossible de contacter le serveur pour vérifier les nouvelles pétitions');
            } else {
                console.error('Erreur HTTP lors de la vérification:', xhr.status);
            }
        }
    };

    xhr.onerror = function() {
        console.error('Erreur réseau lors de la vérification des nouvelles pétitions');
    };

    xhr.ontimeout = function() {
        console.error('Timeout lors de la vérification des nouvelles pétitions');
    };

    xhr.timeout = 8000; // 8 secondes de timeout

    xhr.send();
}

/**
 * Afficher une notification de nouvelle pétition
 */
function showNewPetitionNotification(count, petition) {
    const notification = document.getElementById('notification');
    if (!notification) return;

    // Message selon le nombre
    let message = '';
    if (count === 1 && petition) {
        message = '<i class="bi bi-bell-fill"></i> Nouvelle pétition : <strong>' + 
                  escapeHtml(petition.TitreP) + 
                  '</strong> par ' + escapeHtml(petition.NomPorteurP);
    } else {
        message = '<i class="bi bi-bell-fill"></i> ' + count + ' nouvelles pétitions ajoutées !';
    }

    notification.innerHTML = message;
    notification.className = 'notification notification-new';
    notification.style.display = 'block';

    // Ajouter un bouton pour recharger
    const reloadBtn = notification.querySelector('.btn-reload');
    if (!reloadBtn) {
        const btn = document.createElement('button');
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Recharger la page';
        btn.className = 'btn-reload';
        btn.onclick = function() {
            location.reload();
        };
        notification.appendChild(btn);
    }

    // Jouer un son de notification
    playNotificationSound();
    
    // Recharger automatiquement après 3 secondes
    setTimeout(function() {
        location.reload();
    }, 3000);
}

/**
 * Jouer un son de notification simple avec Web Audio API
 */
function playNotificationSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const audioContext = new AudioContext();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (e) {
        // Le son n'est pas critique, on ignore les erreurs
        console.log('Son de notification non disponible');
    }
}

/**
 * Démarrer la vérification automatique des nouvelles pétitions
 */
function startPetitionPolling() {
    // Vérifier immédiatement
    checkNewPetitions();
    
    // Puis vérifier toutes les 15 secondes
    checkInterval = setInterval(function() {
        checkNewPetitions();
        console.log('🔍 Vérification automatique des nouvelles pétitions...');
    }, 15000); // 15 secondes
}

/**
 * Arrêter la vérification automatique
 */
function stopPetitionPolling() {
    if (checkInterval) {
        clearInterval(checkInterval);
        checkInterval = null;
        console.log('⏹️ Arrêt de la vérification automatique');
    }
}

// ============================================
// VALIDATION FORMULAIRE (SUPPRIMÉE - voir version AJAX plus bas)
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

// ============================================
// AUTO-HIDE ALERTS
// ============================================

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

// ============================================
// INITIALISATION AU CHARGEMENT
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Application chargée avec XMLHttpRequest');
    console.log('📡 Utilisation de XMLHttpRequest (pas de Fetch API)');

    // Vérifier la pétition la plus populaire
    checkTopPetition();

    // Valider le formulaire
    validateSignatureForm();

    // Auto-masquer les alertes
    autoHideAlerts();

    // Démarrer la vérification des nouvelles pétitions
    startPetitionPolling();

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
});

// Arrêter le polling quand on quitte la page
window.addEventListener('beforeunload', function() {
    stopPetitionPolling();
});

// ============================================
// FONCTIONS UTILITAIRES POUR DEBUG
// ============================================

/**
 * Afficher l'état de XMLHttpRequest dans la console
 */
function logXHRState(xhr) {
    const states = {
        0: 'UNSENT',
        1: 'OPENED',
        2: 'HEADERS_RECEIVED',
        3: 'LOADING',
        4: 'DONE'
    };
    console.log('État XMLHttpRequest:', states[xhr.readyState], '(' + xhr.readyState + ')');
}

/**
 * Afficher le statut HTTP dans la console
 */
function logHTTPStatus(status) {
    const statuses = {
        200: 'OK - Succès',
        404: 'Not Found - Fichier non trouvé',
        500: 'Internal Server Error - Erreur serveur',
        0: 'Network Error - Impossible de contacter le serveur'
    };
    const message = statuses[status] || 'Code HTTP: ' + status;
    console.log('Statut HTTP:', message);
}

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
                        
                        // Rediriger après 2 secondes
                        setTimeout(function() {
                            window.location.href = 'index.php?success=1';
                        }, 2000);
                        
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