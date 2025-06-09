import { reactive, applyAndRegister, startReactiveDom } from './reactive.js';

// Objet réactif simple comme dans le TD7
let evenement = reactive({
    // Données de base
    depenses: [],
    membres: [],
    nbDepenses: 0,
    nbMembres: 0,
    montantsDepenses: {}, // Stocker les montants par ID
    
    // Méthode pour supprimer une dépense
    supprimerDepense: function(button) {
        let id = button.dataset.idDepense;
        
        if (confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')) {
            fetch(`/web/api/depenses/${id}`, {method: 'DELETE'})
                .then(response => {
                    if (response.status === 204) {
                        button.closest("div.depense").remove();
                        // Décrémenter le nombre de dépenses
                        this.nbDepenses = this.nbDepenses - 1;
                        // Supprimer le montant
                        delete this.montantsDepenses[id];
                        // Forcer la mise à jour
                        this.montantsDepenses = {...this.montantsDepenses};
                    }
                });
        }
    },
    
    // Méthode pour supprimer un membre
    supprimerMembre: function(button) {
        let login = button.dataset.loginMembre;
        let idEvenement = button.dataset.idEvenement;
        
        if (confirm('Êtes-vous sûr de vouloir retirer ce membre ?')) {
            fetch(`/web/api/evenements/${idEvenement}/membres/${login}`, {method: 'DELETE'})
                .then(response => {
                    if (response.status === 204) {
                        button.closest("div.membre").remove();
                        // Décrémenter le nombre de membres
                        this.nbMembres = this.nbMembres - 1;
                    }
                });
        }
    },
    
    // Méthode pour calculer le coût total
    calculerCoutTotal: function() {
        let total = 0;
        for (let id in this.montantsDepenses) {
            total += this.montantsDepenses[id];
        }
        return total.toFixed(2) + '€';
    },
    
    // Méthode pour afficher/masquer le formulaire
    toggleFormulaireAjout: function() {
        const form = document.getElementById('formulaire-ajout');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    },
    
    // Méthode pour ajouter une dépense
    ajouterDepense: function(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        const titre = formData.get('titre');
        const montant = parseFloat(formData.get('montant'));
        const payeur = formData.get('payeur');
        const participants = formData.getAll('participants');
        
        if (participants.length === 0) {
            alert('Veuillez sélectionner au moins un participant');
            return;
        }
        
        // Appel API
        fetch(`/web/api/evenements/${window.evenementId}/depenses`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                titre: titre,
                montant: montant,
                payeur: payeur,
                participants: participants
            })
        }).then(async response => {
            if (response.status === 201) {
                // Succès - ajouter la dépense dynamiquement
                const codeSecret = await response.json();
                
                // Créer un nouvel élément HTML pour la dépense
                const nouvelleDepenseHTML = `
                    <div class="depense box media">
                        <div class="media-content">
                            <div class="is-size-4">
                                <span><strong>${titre}</strong></span>
                            </div>
                            <div class="has-text-left is-size-5">
                                <span class="icon is-left"><ion-icon name="time"></ion-icon></span>
                                <span>Le ${new Date().toLocaleDateString('fr-FR')}</span>
                            </div>
                            <div class="has-text-left is-size-5">
                                <span class="icon is-left"><ion-icon name="card"></ion-icon></span>
                                <span>${montant.toFixed(2)}€ payé par ${payeur}</span>
                            </div>
                        </div>
                        <div class="media-right">
                            <button class="delete" onclick="evenement.supprimerDepense(this)" data-id-depense="999999"></button>
                        </div>
                    </div>
                `;
                
                // Ajouter au DOM
                document.getElementById('liste-depenses').insertAdjacentHTML('beforeend', nouvelleDepenseHTML);
                
                // Mettre à jour les compteurs
                this.nbDepenses = this.nbDepenses + 1;
                this.montantsDepenses[999999] = montant; // ID temporaire
                this.montantsDepenses = {...this.montantsDepenses};
                
                // Réinitialiser et fermer le formulaire
                form.reset();
                this.toggleFormulaireAjout();
            } else {
                alert('Erreur lors de l\'ajout');
            }
        });
    }
    
}, "evenement");

// Démarrer le système réactif
startReactiveDom();

// Exposer l'objet globalement pour onclick
window.evenement = evenement;