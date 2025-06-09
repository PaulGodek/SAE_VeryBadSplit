const ROOT_URL = "https://v6.exchangerate-api.com/v6/52d7662a97c4ef6e4b7f4adb/pair/"


let montantConverti = null;
let minuteur = 0;

async function toEuros(montant, devise){
    try {
        devise = devise.trim().toUpperCase();
        if (!devise || devise === "EUR") {
            montantConverti = parseFloat(montant); // pas besoin de conversion
            if (montantConverti < 1) {
                messageFlash("La dépense doit couter au moins 1 euro");
            }
            else if ( montantConverti > 10000) {
                messageFlash("La dépense ne doit pas couter plus de 10000 euros");
            }
            return;
        }
        let req = await fetch(`${ROOT_URL}${devise}/EUR/${montant}`);
        let data = await req.json();

        if (data["conversion_result"] !== undefined) {
            montantConverti = parseFloat(data["conversion_result"].toFixed(2));
            if (montantConverti < 1) {
                messageFlash("La dépense doit couter au moins 1 euro");
            }
            else if ( montantConverti > 10000) {
                messageFlash("La dépense ne doit pas couter plus de 10000 euros");
            }
        } else {
            console.warn("Conversion échouée : résultat invalide");
            montantConverti = null;

            if (data["error-type"] === "unsupported-code") {
                messageFlash("La devise n'est pas correcte !")
            }
        }
    } catch (error) {
        console.log(error);
    }
}

// Ajouter les listeners
document.addEventListener('DOMContentLoaded', () => {
    const montantInput = document.getElementById("montantPasConverti");
    const deviseInput = document.getElementById("devise");
    const montantVar = document.getElementById("montant");
    function handleBlur() {
        clearTimeout(minuteur);
        minuteur = setTimeout(function () {
            const montant = parseFloat(montantInput.value) ?? 0;
            const devise = deviseInput.value;
            if (!isNaN(montant)) {
                toEuros(montant, devise).then(() => {
                    montantVar.value = montantConverti ?? 0;
                });
            }
        }, 500 );
    }

    montantInput.addEventListener("keyup", handleBlur);
    deviseInput.addEventListener("keyup", handleBlur);
    console.log(montantInput, deviseInput);

});

function messageFlash(content) {
    const messagesFlash = document.getElementById("flashes-container");

// Créer le conteneur <div>
    const flashDiv = document.createElement("div");
    flashDiv.className = "flash notification mt-5 is-warning";

// Créer le bouton <button>
    const deleteButton = document.createElement("button");
    deleteButton.className = "delete";
    deleteButton.addEventListener("click", () => {
        flashDiv.remove();
    });

// Créer le <p> avec le message
    const messageP = document.createElement("p");
    messageP.className = "is-size-4";
    messageP.textContent = content;

// Ajouter les enfants à la div
    flashDiv.appendChild(deleteButton);
    flashDiv.appendChild(messageP);

    messagesFlash.appendChild(flashDiv);
}