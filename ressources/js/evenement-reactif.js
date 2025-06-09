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
                        // Recharger les listes pour mettre à jour les dépenses et dettes
                        this.rechargerListes();
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
        return total.toFixed(2).replace('.', ',') + '€';
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
                // Succès — recharger les listes
                const codeSecret = await response.json();
                
                // Réinitialiser et fermer le formulaire
                form.reset();
                this.toggleFormulaireAjout();
                
                // Recharger les listes pour avoir les données à jour
                this.rechargerListes();
            } else {
                alert('Erreur lors de l\'ajout');
            }
        });
    },
    
    // Méthode pour recharger les listes (dépenses et membres avec dettes)
    rechargerListes: function() {
        // Recharger la page de l'événement pour obtenir les nouvelles données
        fetch(`/web/evenements/${window.location.pathname.split('/').pop()}`)
            .then(response => response.text())
            .then(html => {
                // Parser le HTML pour extraire les listes
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Remplacer la liste des dépenses
                const nouvelleListeDepenses = doc.getElementById('liste-depenses');
                if (nouvelleListeDepenses) {
                    document.getElementById('liste-depenses').innerHTML = nouvelleListeDepenses.innerHTML;
                    
                    // Mettre à jour les compteurs et montants
                    const depenses = nouvelleListeDepenses.querySelectorAll('.depense');
                    this.nbDepenses = depenses.length;
                    
                    // Reconstruire l'objet des montants
                    const nouveauxMontants = {};
                    depenses.forEach(dep => {
                        const montantText = dep.querySelector('.media-content .is-size-5:nth-child(3) span:nth-child(2)').textContent;
                        // Regex pour capturer le format français : 10,00€ ou 1 000,50€
                        const montantMatch = montantText.match(/([\d\s]+(?:,\d+)?)€/);
                        if (montantMatch) {
                            // Enlever les espaces et remplacer la virgule par un point
                            const montantStr = montantMatch[1].replace(/\s/g, '').replace(',', '.');
                            const montant = parseFloat(montantStr);
                            const deleteButton = dep.querySelector('button.delete');
                            if (deleteButton && deleteButton.dataset.idDepense) {
                                nouveauxMontants[deleteButton.dataset.idDepense] = montant;
                            }
                        }
                    });
                    // Remplacer complètement l'objet pour déclencher la réactivité
                    this.montantsDepenses = nouveauxMontants;
                    
                }
                
                // Remplacer la liste des membres (avec les dettes mises à jour)
                const nouvelleListeMembres = doc.getElementById('liste-membres');
                if (nouvelleListeMembres) {
                    document.getElementById('liste-membres').innerHTML = nouvelleListeMembres.innerHTML;
                }
            });
    }
    
}, "evenement");

// Démarrer le système réactif
startReactiveDom();

// Exposer l'objet globalement pour onclick
window.evenement = evenement;