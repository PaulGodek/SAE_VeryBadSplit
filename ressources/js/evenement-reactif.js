import { reactive, applyAndRegister, startReactiveDom } from './reactive.js';

// Objet réactif simple comme dans le TD7
let evenement = reactive({
    // Données de base
    depenses: [],
    membres: [],
    
    // Méthode pour supprimer une dépense
    supprimerDepense: function(button) {
        let id = button.dataset.idDepense;
        
        fetch(`/web/api/depenses/${id}`, {method: 'DELETE'})
            .then(
                response => {
                    if (response.status === 204) {
                        button.closest("div.depense").remove();
                    }
                }
            );
    }
    
}, "evenement");

// Démarrer le système réactif
startReactiveDom();

// Exposer l'objet globalement pour onclick
window.evenement = evenement;