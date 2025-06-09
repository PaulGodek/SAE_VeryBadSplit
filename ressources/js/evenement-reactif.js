import { reactive, applyAndRegister, startReactiveDom } from './reactive.js';

// Objet réactif simple comme dans le TD7
let evenement = reactive({
    // Données de base
    depenses: [],
    membres: [],
    nbDepenses: 0,
    nbMembres: 0,
    montantsDepenses: {}, // Stocker les montants par ID
    coutTotal: 0, // Stocker le coût total
    
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
                        // Mettre à jour le coût total
                        this.mettreAJourCoutTotal();
                        // Recharger la liste des membres pour les dettes
                        this.rechargerListeMembres();
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
        return this.coutTotal.toFixed(2).replace('.', ',') + '€';
    },
    
    // Méthode pour mettre à jour le coût total
    mettreAJourCoutTotal: function() {
        let total = 0;
        for (let id in this.montantsDepenses) {
            total += this.montantsDepenses[id];
        }
        this.coutTotal = total;
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
        // Utiliser le montant converti en EUR
        const montant = parseFloat(formData.get('montant'));
        const payeur = formData.get('payeur');
        const participants = formData.getAll('participants');
        
        console.log('Ajout dépense - Montant envoyé:', montant);
        
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
                // Succès
                const depenseId = await response.json();
                
                // Ajouter temporairement le montant
                this.montantsDepenses[depenseId] = montant;
                this.nbDepenses = this.nbDepenses + 1;
                this.mettreAJourCoutTotal();
                
                // Réinitialiser et fermer le formulaire
                form.reset();
                this.toggleFormulaireAjout();
                
                // Recharger les listes pour avoir l'affichage à jour
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
                    
                    // Mettre à jour les montants depuis le script
                    const scripts = doc.querySelectorAll('script');
                    scripts.forEach(script => {
                        if (script.textContent.includes('window.evenement.montantsDepenses')) {
                            const nouveauxMontants = {};
                            const matches = script.textContent.matchAll(/window\.evenement\.montantsDepenses\[(\d+)\]\s*=\s*([\d.]+)/g);
                            for (const match of matches) {
                                nouveauxMontants[match[1]] = parseFloat(match[2]);
                            }
                            this.montantsDepenses = nouveauxMontants;
                            this.mettreAJourCoutTotal();
                        }
                    });
                }
                
                // Remplacer la liste des membres
                this.rechargerListeMembres();
            });
    },
    
    // Méthode pour recharger seulement la liste des membres
    rechargerListeMembres: function() {
        fetch(`/web/evenements/${window.location.pathname.split('/').pop()}`)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
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