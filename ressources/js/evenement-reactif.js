import { reactive, startReactiveDom } from './reactive.js';

// Objet réactif comme dans le TD7
let evenement = reactive({
    // Compteurs
    nbDepenses: 0,
    nbMembres: 0,
    coutTotal: 0,
    montantsDepenses: {},
    
    // Calcul du coût total pour l'affichage
    calculerCoutTotal: function() {
        return this.coutTotal.toFixed(2).replace('.', ',') + '€';
    },
    
    // Mise à jour du coût total
    mettreAJourCoutTotal: function() {
        let total = 0;
        for (let id in this.montantsDepenses) {
            total += this.montantsDepenses[id];
        }
        this.coutTotal = total;
    },
    
    // Toggle formulaire d'ajout
    toggleFormulaireAjout: function() {
        const form = document.getElementById('formulaire-ajout');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    },
    
    // Suppression d'une dépense
    supprimerDepense: function(button) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')) {
            const id = button.dataset.idDepense;
            
            fetch(`/web/api/depenses/${id}`, {method: 'DELETE'})
                .then(response => {
                    if (response.status === 204) {
                        // Mise à jour visuelle et des données
                        button.closest('.depense').remove();
                        this.nbDepenses--;
                        delete this.montantsDepenses[id];
                        this.mettreAJourCoutTotal();
                        
                        // Recharger les dettes
                        this.actualiserDettes();
                    }
                });
        }
    },
    
    // Suppression d'un membre
    supprimerMembre: function(button) {
        if (confirm('Êtes-vous sûr de vouloir retirer ce membre ?')) {
            const login = button.dataset.loginMembre;
            const idEvenement = button.dataset.idEvenement;
            
            fetch(`/web/api/evenements/${idEvenement}/membres/${login}`, {method: 'DELETE'})
                .then(response => {
                    if (response.status === 204) {
                        button.closest('.membre').remove();
                        this.nbMembres--;
                    }
                });
        }
    },
    
    // Ajout d'une dépense
    ajouterDepense: function(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        
        const depense = {
            titre: formData.get('titre'),
            montant: parseFloat(formData.get('montant')),
            payeur: formData.get('payeur'),
            participants: formData.getAll('participants')
        };
        
        if (depense.participants.length === 0) {
            alert('Veuillez sélectionner au moins un participant');
            return;
        }
        
        fetch(`/web/api/evenements/${window.evenementId}/depenses`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(depense)
        }).then(response => {
            if (response.status === 201) {
                form.reset();
                this.toggleFormulaireAjout();
                this.actualiserListes();
            }
        });
    },
    
    // Actualisation des listes après modification
    actualiserListes: function() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                
                // Actualiser la liste des dépenses
                const listeDepenses = doc.getElementById('liste-depenses');
                if (listeDepenses) {
                    document.getElementById('liste-depenses').innerHTML = listeDepenses.innerHTML;
                    
                    // Récupérer les nouveaux montants
                    const script = Array.from(doc.querySelectorAll('script'))
                        .find(s => s.textContent.includes('montantsDepenses'));
                    
                    if (script) {
                        this.montantsDepenses = {};
                        const regex = /montantsDepenses\[(\d+)\]\s*=\s*([\d.]+)/g;
                        let match;
                        while ((match = regex.exec(script.textContent))) {
                            this.montantsDepenses[match[1]] = parseFloat(match[2]);
                        }
                        this.nbDepenses = Object.keys(this.montantsDepenses).length;
                        this.mettreAJourCoutTotal();
                    }
                }
                
                // Actualiser les dettes
                this.actualiserDettes();
            });
    },
    
    // Actualisation des dettes uniquement
    actualiserDettes: function() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const listeMembres = doc.getElementById('liste-membres');
                if (listeMembres) {
                    document.getElementById('liste-membres').innerHTML = listeMembres.innerHTML;
                }
            });
    }
    
}, "evenement");

// Démarrage
startReactiveDom();
window.evenement = evenement;