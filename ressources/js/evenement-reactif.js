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
    }
    
}, "evenement");

// Démarrer le système réactif
startReactiveDom();

// Exposer l'objet globalement pour onclick
window.evenement = evenement;