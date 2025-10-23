// ============================================
// VALIDATION FORMULAIRE NOUVELLE PÉTITION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const petitionForm = document.getElementById('petitionForm');
    
    if (petitionForm) {
        petitionForm.addEventListener('submit', function(e) {
            const titre = document.getElementById('titre').value.trim();
            const description = document.getElementById('description').value.trim();
            const nom_porteur = document.getElementById('nom_porteur').value.trim();
            const email = document.getElementById('email').value.trim();
            const date_fin = document.getElementById('date_fin').value;

            let errors = [];

            // Validation titre
            if (titre.length < 5) {
                errors.push('Le titre doit contenir au moins 5 caractères');
            }
            if (titre.length > 255) {
                errors.push('Le titre ne peut pas dépasser 255 caractères');
            }

            // Validation description
            if (description.length < 20) {
                errors.push('La description doit contenir au moins 20 caractères');
            }
            if (description.length > 5000) {
                errors.push('La description ne peut pas dépasser 5000 caractères');
            }

            // Validation nom porteur
            if (nom_porteur.length < 2) {
                errors.push('Le nom du porteur doit contenir au moins 2 caractères');
            }

            // Validation email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Email invalide');
            }

            // Validation date
            if (date_fin) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(date_fin);
                
                if (selectedDate < today) {
                    errors.push('La date limite doit être dans le futur');
                }
            }

            // Si erreurs, empêcher la soumission
            if (errors.length > 0) {
                e.preventDefault();
                
                // ✅ Afficher les erreurs directement
                const messageDiv = document.getElementById('message');
                if (messageDiv) {
                    messageDiv.innerHTML = '<i class="bi bi-x-circle-fill"></i> ' + errors.join('<br>');
                    messageDiv.className = 'message error';
                    messageDiv.style.display = 'block';
                } else {
                    alert(errors.join('\n'));
                }
                
                return false;
            }

            return true;
        });
    }

    // Gérer les erreurs dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const messageDiv = document.getElementById('message');
    
    if (messageDiv) {
        if (urlParams.get('error') === 'duplicate') {
            messageDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Une pétition avec ce titre existe déjà';
            messageDiv.className = 'message error';
            messageDiv.style.display = 'block';
        } else if (urlParams.get('error') === 'db') {
            messageDiv.innerHTML = '<i class="bi bi-x-circle-fill"></i> Erreur lors de la création de la pétition';
            messageDiv.className = 'message error';
            messageDiv.style.display = 'block';
        } else if (urlParams.get('error') === 'validation') {
            messageDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Erreur de validation des données';
            messageDiv.className = 'message error';
            messageDiv.style.display = 'block';
        }
    }
});